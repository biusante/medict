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
    /** SQL link */
    static public $pdo;
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
        list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
        $an1 = Web::par('an1', null);
        if ($an1 <=  $an_min) $an1 = null;
        $an2 = Web::par('an2', null);
        if ($an2 >=  $an_max) $an2 = null;
        if ($an1 !== null && $an2 !== null && $an2 < $an1) $an2 = $an1;
        $reqPars['an1'] = $an1;
        $reqPars['an2'] = $an2;
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
        // normaliser les espaces
        $s = preg_replace('/[\s\-]+/', ' ', trim($s));
        // decomposer lettres et accents
        $s = Normalizer::normalize($s, Normalizer::FORM_D);
        // ne conserver que les lettres et les espaces
        $s = preg_replace("/[^\pL\s]/u", '', $s);
        return $s;
    }

    /**
     * Affiche une entrée de dico
     */
    public static function entree(&$entree)
    {
        $url = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=' . $entree['cote_volume'] . '&amp;p=' . $entree['refimg'];

        $block = '';
        $block .= '<div class="entree">';
        $block .= '<a class="entree" target="facs" href="' . $url . '">';
        $block .= '<b>' . $entree['vedette'] . '</b>.';
        $block .= ' <i>' . $entree['nom_volume'] . '</i>, ' . $entree['annee_volume'] . ', ';
        if ($entree['page2'] != null) $block .= "pps. " . $entree['page'] . '-' . $entree['page2'];
        else $block .= "p. " . $entree['page'];
        $block .= ".</a>";
        $block .= "</div>";
        return $block;
    }
}
