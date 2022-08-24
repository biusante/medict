<?php 

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

/* format de sortie
{  "data": [
    {"n":1, "id":0, "hits":262, "text":"Titre court"},
    {"n":2, "id":1, "hits":76, "text":"Forget, Joséphine de"}
], "meta": {"time": "0ms", "query": null, "cardinality": -1}}
*/

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

function titre(&$cotes, &$row)
{
    $div = '';
    $checked = '';
    if ($cotes && isset($cotes[$row['cote']])) {
        $checked = "\n".'      checked="checked"';
    }
    $div .= '
<div class="titre">
  <input type="checkbox"
    name="'. Medict::F . '" 
    value="' . $row['cote'] . '" 
    '. $checked .'
    id="check_' . $row['cote'] . '"
    data-annee="'. $row['annee'] .'" 
    data-nom="'. strip_tags($row['nom']) .'"
    class="' . $row['class'] . '"
  />
  <label for="check_' . $row['cote'] . '"
    title="' . strip_tags($row['bibl']) . '"
  >
    <span class="nom">' . $row['nom'] . '</span>
  </label>
</div>';
    return $div;
}
?>

<div id="titres_body">
    <header>
        <div class="selector">
            <input class="titre_check" id="allF" type="checkbox"/>
            <label for="allF" id="allFCheck">Tout cocher</label>
            <label for="allF" id="allFUncheck">Tout décocher</label>
        </div>
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
$cotes = Web::pars(Medict::F);
if (0 < count($cotes)) { // si cotes demandées, vérifier qu’elles existent
    // load available dico ids from base
    $coteQ = Medict::$pdo->prepare("SELECT cote FROM dico_titre");
    $coteQ->execute();
    $biblio = $coteQ->fetchAll(PDO::FETCH_COLUMN, 0);
    $cotes = array_intersect($cotes, $biblio);
}
if (0 < count($cotes)) $cotes = array_flip($cotes);
else $cotes = null;

$sql = "SELECT * FROM dico_titre ";
$titreQ = Medict::$pdo->prepare($sql);
$titreQ->execute(array());
echo '
';
while ($row = $titreQ->fetch(PDO::FETCH_ASSOC)) {
    if (!$row['cote']) continue; // buggy when a title has no cote
    echo titre($cotes, $row);
}


?>
    </div>
</div>
