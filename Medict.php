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

    public static function hilite($query, $vedette)
    {
        if (!$query) {
            return $vedette;
        }
        if (!isset(self::$hire[$query])) {
            $regs = preg_split("@[ ,]+@u", trim($query));
            $regs = preg_replace(
                array(
                    "@[\P{L}]+@u",
                    "@[aàâä]@ui",
                    "@[æ]@ui",
                    "@[cç]@ui",
                    "@[eéèêë]@ui",
                    "@[iîï]@ui",
                    "@[oôö]@ui",
                    "@[œ]@ui",
                    "@[uûü]@ui",
                    "@^.*$@ui",
                ),
                array(
                    "",
                    "[aàâä]",
                    "ae",
                    "[cç]",
                    "[eéèêë]",
                    "[iîï]",
                    "[oôö]",
                    "oe",
                    "[uûü]",
                    "@([^<>\p{L}])($0)@ui",
                ),
                $regs
            );
            self::$hire[$query] = $regs;
        }
        $regs = self::$hire[$query];
        $vedette = " " . $vedette;
    foreach ($regs as $re) {
            $vedette = preg_replace($re, "$1<mark>$2</mark>", $vedette);
        }
        return $vedette;
        /*
    return preg_replace_callback(
      $re,
      function ($matches) use ($terme_sort) {
        $test = Medict::sortable($matches[0]);
        if ($test == $terme_sort) return "<mark>".$matches[0]."</mark>";
        return $matches[0];
      },
      " ".$vedette
    );
    */
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
        $rels = "(reltype = $reltype_orth OR reltype = $reltype_term  OR (reltype = $reltype_foreign AND orth IS NULL ))";
        return $rels;
    }

    /**
     * Cette méthode doit être identique à celle utilisée à l’indexation
     */
    public static function deforme(string $s, bool $uvij=false)
    {
        // bas de casse
        $s = mb_convert_case($s, MB_CASE_FOLD, "UTF-8");
        // décomposer lettres et accents
        $s = Normalizer::normalize($s, Normalizer::FORM_D);
        // ne conserver que les lettres et les espaces, et les traits d’union
        $s = preg_replace("/[^\p{L}\-\s]/u", '', $s);

        $s = strtr($s,
            array(
                'œ' => 'oe',
                'æ' => 'ae',
                'j' => 'i',
                'u' => 'v',
            )
        );
        /*
        if ($uvij === true) {
            $s = strtr($s,
                array(
                    'œ' => 'e',
                    'æ' => 'e',
                    'j' => 'i',
                    'u' => 'v',
                )
            );
        } else {
            // ligatures
            $s = strtr(
                $s,
                array(
                    'œ' => 'oe',
                    'æ' => 'ae',
                )
            );
        }
        */
        // normaliser les espaces
        $s = preg_replace('/[\s\-]+/', ' ', trim($s));
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
