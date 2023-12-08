<?php declare(strict_types=1);

/**
 * Part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université de Paris, BIU Santé
 * MIT License https://opensource.org/licenses/mit-license.php
 */

include_once(__DIR__ . '/vendor/autoload.php');

use Oeuvres\Kit\{Http};

Medict::init();
class Medict
{
    /** Constantes */
    const EXTENSIONS = array(
        "pdo_mysql" => ["Connexion PDO à mysql", 'php-mysql'],
        'mbstring' => ["Fonctions de chaîne “nulti-bytes” (unicode)", 'php-mbstring'],
        'intl' => ["Fonctions d'“internationalisation” (Normalizer pour le grec ancien)", 'php-intl'],
    );
    const AN1 = "an1";
    const AN2 = "an2";
    const F = "f";
    const DICO_TITRE = "dico_titre";
    const TAGS = array(
        'med' => ['méd.', 'Sciences médicales'],
        'vet' => ['vétér.', 'Sciences vétérinaires'],
        'pharm' => ['pharm.', 'Pharmacie'],
        'gloss' => ['gloss.', 'Glossaires'],
        'biogr' => ['biogr.', 'Biographies'],
        'autres' => ['autres', 'Autres'],
        
        // 'sc' => ['sc.', 'Autres sciences'],
        // 'hist' => ['hist.', 'Histoire'],
    );
    static $langs = [null, 'fra', 'lat', 'grc', 'eng', 'deu', 'spa', 'ita'];

    /** SQL link */
    static public $pdo;
    /** requêtes préparées */
    static public $q;
    /** Les paramètres */
    static public $pars;
    /** Cache l’expression régulière de hilite */
    static private $hire = array();

    public static function init()
    {
        $ex = [];
        foreach(self::EXTENSIONS as $ext=>$mess) {
            if (extension_loaded($ext)) continue;
            echo "<p>
<b>$ext</b>, extension php requise (cf. php.ini)
<br/>{$mess[0]}
<br/><code>Ubuntu 22.04$ sudo apt install {$mess[1]}</code>
</p>
";
            $ex [] = $ext;
        }
        if (count($ex)) {
            throw new Exception("Au moins une extension php manquante : ".implode(", ", $ex));
        }
        $pars_file = __DIR__ . '/pars.php';
        if (!file_exists($pars_file)) {
            throw new Exception("Paramètres MySQL manquants (pars.php). Modèle : _pars.php");
        }
        self::$pars = include dirname(__FILE__) . '/pars.php';

        $keys = ['host', 'port', 'dbname', 'user', 'password'];
        $e = [];
        foreach($keys as $k) {
            if (isset(self::$pars[$k]) && self::$pars[$k]) continue;
            $e[] = $k;
            echo "<p>pars.php ['$k' => ???] paramètre requis</p>";
        }
        if (count($e)) {
            $count = count($e) . " paramètres manquants";
            if (count($e) == 1) $count = "1 paramètre manquant";
            throw new Exception("$pars_file, $count : ".implode(", ", $e));
        }


        self::$pdo =  new PDO(
            "mysql:host=" . self::$pars['host'] . ";port=" . self::$pars['port'] . ";dbname=" . self::$pars['dbname'],
            self::$pars['user'],
            self::$pars['password'],
            array(
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                // if true : big queries need memory
                // if false : multiple queries arre not allowed
                // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
            ),
        );
        // self::$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        mb_internal_encoding("UTF-8");
    }

    /**
     * Prépare des paramètres utiles pour les requêtes
     */
    public static function reqPars()
    {
        $reqPars = array();
        list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee), MAX(annee) FROM dico_titre")->fetch();
        $an1 = Http::par(self::AN1, null);
        if ($an1 <=  $an_min) $an1 = null;
        $an2 = Http::par(self::AN2, null);
        if ($an2 >=  $an_max) $an2 = null;
        if ($an1 !== null && $an2 !== null && $an2 < $an1) $an2 = $an1;
        $reqPars[self::AN1] = $an1;
        $reqPars[self::AN2] = $an2;
        // load filter by cote 
        $reqPars[self::DICO_TITRE] = null;
        $reqPars[self::F] = null;
        $fdic =  Http::pars(self::F);
        if (count($fdic)) {
            $reqPars[self::DICO_TITRE] = array();
            $reqPars[self::F] = array();
                if (!isset(self::$q['cote_id'])) {
                self::$q['cote_id'] = self::$pdo->prepare("SELECT id FROM dico_titre WHERE cote = ?");
            }
            $fdic_copy = $fdic;
            foreach($fdic as $cote) {
                self::$q['cote_id']->execute(array($cote));
                $row = self::$q['cote_id']->fetch(PDO::FETCH_ASSOC);
                if (!$row) continue;
                $reqPars[self::F][] = $cote;
                $reqPars[self::DICO_TITRE][] = $row['id'];
            }
            // renull s’il n’y a rien 
            if (count($reqPars[self::F]) < 1 || count($reqPars[self::DICO_TITRE]) < 1) {
                $reqPars[self::F] = null;
                $reqPars[self::DICO_TITRE] = null;
            }
        }
        return $reqPars;
    }

    static function hilite($query, $text) {
        $query = trim($query);
        // do not recompile for each word, cache
        if (!isset(self::$hire[$query])) {
            // simplification de la requête
            // preg_quote est une précaution mais ne devrait pas être nécessaire
            $query_desacc = trim(preg_quote(self::deforme($query)));
            $query_words = explode(" ", $query_desacc);
            $query_re = implode('|', array_map('preg_quote', $query_words));
            // suffix
            if ($query[0] == '*') {
                $query_re = "/($query_re)\P{L}/u";
            }
            // prefix
            else {
                $query_re = "/\P{L}($query_re)/u";
            }
            self::$hire[$query] = $query_re;
        }
        // char offset
        $pos = 0;
        $text_hilite = "";
        // vedette sans accents avec le même nombre de caractères
        // uvji ? \n ?
        $text_desacc = preg_replace(
            "/\p{Mn}+/u",
            "",
            Normalizer::normalize(
                mb_convert_case($text, MB_CASE_FOLD, "UTF-8"), 
                Normalizer::FORM_D
            ),
        );
        if (preg_match_all(self::$hire[$query], " $text_desacc ", $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $match) {
                $match_string = $match[0];
                $match_len = mb_strlen($match_string);
                // char offset of matched word, corrected of the char prefix
                $match_offset = intval($match[1]) - 1;
                $text_hilite .= mb_substr($text, $pos, $match_offset - $pos);
                $text_hilite .= "<mark>";
                $text_hilite .= mb_substr($text, $match_offset, $match_len);
                $text_hilite .= "</mark>";
                $pos = $match_offset + $match_len;
            }
        }
        $text_hilite .= mb_substr($text, $pos);
        return $text_hilite;
    }
    

    /**
     * Élément de requête SQL partagé entre colonne d’index et accès entrées
     */
    public static function rels_vedettes()
    {
        $reltype_orth = 1;
        $reltype_term = 2;
        $reltype_foreign = 3;
        // colonne index : vedettes, sous-vedettes (locutions), traductions
        // orth IS NULL ? sans doute un mauvais hack, à revoir
        $rels = "reltype IN (1, 2, 3)";
        return $rels;
    }

    /**
     * Cette méthode doit être identique à celle utilisée à l’indexation
     */
    public static function deforme(string $s, bool $nolig=false)
    {
        // bas de casse
        $s = mb_convert_case($s, MB_CASE_FOLD, "UTF-8");
        // décomposer lettres et accents
        $s = Normalizer::normalize($s, Normalizer::FORM_D);
        // ne conserver que les lettres et les espaces, et les traits d’union
        $s = preg_replace("/[^\p{L}\-\s]/u", '', $s);
        // normaliser les espaces
        $s = trim(preg_replace('/[\s\-]+/', ' ', trim($s)));
        // ligatures
        if (!$nolig) {
            $s = strtr(
                $s,
                array(
                    'œ' => 'oe',
                    'æ' => 'ae',
                )
            );
        }
        return $s;
    }

    /**
     * Affiche une entrée de dico
     */
    public static function entree(&$entree)
    {
        if (!$entree) return; // ????
        $cote = $entree['volume_cote'];
        $cote = strtok($cote, '~'); // 37020d~index
        $url = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=' 
        . $cote 
        . '&amp;p=' . $entree['refimg'];

        $block = '';
        $block .= '<div class="entree">';
        $block .= '<a class="entree" target="facs"' 
        . ' draggable="false"'
        . ' href="'. $url . '">';
        if (isset($entree['in']) && $entree['in']) {
            $block .= "« " . $entree['in'] . " » <i>in</i> ";
        }
        $block .= '<b>' . $entree['vedette'] . '</b>';
        $block .= '. <i>' . $entree['titre_nom'] . '</i>, ' 
        . $entree['volume_annee'];
        if ($entree['volume_soustitre']) {
            $block .= ", " . $entree['volume_soustitre'];
        }
        if ($entree['page2'] != null) {
            $block .= ", p. " . $entree['page'] . '-' . $entree['page2'];
        }
        else {
            $block .= ", p. " . $entree['page'];
        }
        $block .= ".</a>";
        $block .= "</div>";
        return $block;
    }
}
