<?php

/**
 * Part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université de Paris, BIU Santé
 * MIT License https://opensource.org/licenses/mit-license.php
 */

declare(strict_types=1);

include_once(__DIR__ . '/php/autoload.php');

use Oeuvres\Kit\{Web};

Medict::init();
class Medict
{
    /** Constantes */
    const AN1 = "an1";
    const AN2 = "an2";
    const F = "f";
    const DICO_TITRE = "dico_titre";
    const TAGS = array(
        'med' => ['méd.', 'Sciences médicales'],
        'vet' => ['vétér.', 'Sciences vétérinaires'],
        'pharm' => ['pharm.', 'Pharmacie'],
        'sc' => ['sc.', 'Autres sciences'],
        'hist' => ['hist.', 'Histoire'],
        'biogr' => ['biogr.', 'Biographies'],
    );
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
        self::$pars = include dirname(__FILE__) . '/pars.php';
        self::$pdo =  new PDO(
            "mysql:host=" . self::$pars['host'] . ";port=" . self::$pars['port'] . ";dbname=" . self::$pars['dbname'],
            self::$pars['user'],
            self::$pars['pass'],
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
        $an1 = Web::par(self::AN1, null);
        if ($an1 <=  $an_min) $an1 = null;
        $an2 = Web::par(self::AN2, null);
        if ($an2 >=  $an_max) $an2 = null;
        if ($an1 !== null && $an2 !== null && $an2 < $an1) $an2 = $an1;
        $reqPars[self::AN1] = $an1;
        $reqPars[self::AN2] = $an2;
        // load filter by cote 
        $reqPars[self::DICO_TITRE] = null;
        $reqPars[self::F] = null;
        $fdic =  Web::pars(self::F);
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

    /** Clé de simplification d’un terme */
    public static function sortable($s)
    {
        // bas de casse
        $s = mb_convert_case($s, MB_CASE_FOLD, "UTF-8");
        // ligatures
        $s = strtr(
            $s,
            array(
                'œ' => 'oe',
                'æ' => 'ae',
            )
        );
        // décomposer lettres et accents
        $s = Normalizer::normalize($s, Normalizer::FORM_D);
        // ne conserver que les lettres et les espaces, et les traits d’union
        $s = preg_replace("/[^\p{L}\-\s]/u", '', $s);
        // normaliser les espaces
        $s = preg_replace('/[\s\-]+/', ' ', trim($s));
        return $s;
    }

    /**
     * Affiche une entrée de dico
     */
    public static function entree(&$entree)
    {
        $cote = $entree['volume_cote'];
        $cote = strtok($cote, '~'); // 37020d~index
        $url = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=' 
        . $cote 
        . '&amp;p=' . $entree['refimg'];

        $block = '';
        $block .= '<div class="entree">';
        $block .= '<a class="entree" target="facs" href="' . $url . '">';
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
