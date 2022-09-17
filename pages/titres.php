<?php 

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
declare(strict_types=1);

$start_time = microtime(true);

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Route,Web};

// load available dico ids from base
$biblio = array();
$coteQ = Medict::$pdo->prepare("SELECT cote, annee, an_max FROM dico_titre");
$coteQ->execute();
while ($row = $coteQ->fetch(PDO::FETCH_ASSOC)) {
    $biblio[$row['cote']] = [$row['annee'], $row['an_max']];
}
$coteQ->fetchAll(PDO::FETCH_COLUMN, 0);

// nom de la sélection
$selname = "Tout sauf <u>Vidal</u>";
// corpus requested ?
$fdic = Web::pars(Medict::F);
if (0 == count($fdic)) { // tout sauf Vidal
    $fdic = $biblio;
    unset($fdic['pharma_p11247']);
}
else if (0 < count($fdic)) { // si cotes demandées, vérifier qu’elles existent
    $fdic = array_intersect($fdic, array_keys($biblio));
    $fdic = array_flip($fdic);
    // titrer la sélection
    $count = count($fdic);
    $min = 10000;
    $max = 0;
    foreach ($fdic as $cote => $blah) {
        $dates = $biblio[$cote];
        $min = min($min, $dates[0]);
        if ($dates[1]) $max = max($max, $dates[1]);
        else $max = max($max, $dates[0]);
    }
    $selname = $min;
    if ($min != $max) $selname .=  ' – ' . $max;
    if (1 == $count) $selname .= ' (1 titre)';
    else $selname .= ' (' . $count . ' titres)';
}

?>

<div>
    <div>Sélection de titres</div>
    <div title="Cliquer pour accéder à la liste des titres à sélectionner" id="titres_open"><?=  $selname ?></div>
</div>
<div id="titres_modal" class="modal">
    <span class="close">×</span>
    <div id="titres_body">
        <header>
            <label>Trier par
                <select id="sortitres">
                    <option value="annee, nom">année</option>
                    <option value="tags, nom">mots-clés</option>
                    <option value="nom, annee">nom</option>
                    <option value="npages-, annee">taille (nb. total de p.)</option>
                </select>
            </label>
            <div class="selector">
                <input class="titre_check" id="allF" type="checkbox"/>
                <label for="allF">Tout cocher / décocher</label>
            </div>
<?php
foreach (Medict::TAGS as $tag => $a) {
    echo '
        <div class="selector tag ' . $tag .'">
            <input class="titre_check" value="' . $tag . '" id="all' .$tag .'" type="checkbox"/>
            <label for="all' . $tag .'">' . $a[1] .'</label>
        </div>';
}

?>
        <!--
    <div class="bislide">
    <div>Limiter la recherche à une période</div>
    <input name="an1" step="1" value="<?= $an1 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
    <input name="an2" step="1" value="<?= $an2 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
    <div class="values"></div>
    </div>
    -->
        </header>
        <div id="titres_cols">

<?php

$sql = "SELECT * FROM dico_titre ";
$titreQ = Medict::$pdo->prepare($sql);
$titreQ->execute(array());
$sql = "SELECT id FROM dico_entree WHERE dico_titre = ? LIMIT 1 ";
$entreeQ = Medict::$pdo->prepare($sql);

echo '
';
while ($row = $titreQ->fetch(PDO::FETCH_ASSOC)) {
    if (!$row['cote']) continue; // buggy when a title has no cote
    $entreeQ->execute([$row['id']]);
    if (!$entreeQ->fetch()) continue;
    // tester s’il y a au moins une entrée (en cours de chargement)
    $checked = ($fdic && isset($fdic[$row['cote']]));
    echo titre($row, $checked);
}
?>
        </div>
    </div>
</div>

<?php

function titre(&$row, $checked = false)
{
    if ($checked) $checked = "\n".'      checked="checked"';
    else $checked = '';

    $badges = '';
    if ($row['class']) {
        foreach (preg_split("/\s+/", $row['class']) as $tag) {
            if (!$tag) continue;
            $badges .= ' <mark'
                . ' class="' . $tag . '"'
                . ' title="' . Medict::TAGS[$tag][1] . '"'
                . '>'
                . Medict::TAGS[$tag][0]
                . '</mark>'
            ;
        }
    }
    $extend = '';
    if ($row['vols'] > 1) $extend = ' ' . $row['vols']. ' vols.';
    else if ($row['pages']) $extend = ' ' . $row['pages']. ' p.';
    $div = '';
    $div .= '
<div class="titre"
    data-annee="'. $row['annee'] .'" 
    data-an_max="'. $row['an_max'] .'" 
    data-nom="'. strip_tags($row['nom']) .'"
    data-tags="'. $row['class'] .'"
    data-npages="'. $row['pages'] .'"
>
  <input type="checkbox"
    name="'. Medict::F . '" 
    value="' . $row['cote'] . '" 
    '. $checked .'
    id="check_' . $row['cote'] . '"
    class="' . $row['class'] . '"
  />
  <label for="check_' . $row['cote'] . '"
    title="' . strip_tags($row['bibl']) . '"
  >' . $row['nomdate'] . $extend . $badges . '
  </label>
</div>';
    return $div;
}

echo "<!-- " . number_format(microtime(true) - $start_time, 3) . " s. -->\n";

?>
