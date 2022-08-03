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

function titre(&$cotes, $row)
{
    $div = '';
    $checked = '';
    if (isset($cotes[$row['cote']])) $checked = "\n".'      checked="checked"';
    $div .= '
  <div class="titre">
    <input
      class="titre_check" 
      id="check_' . $row['cote'] . '" type="checkbox" 
      name="'. Medict::COTE . '" 
      value="' . $row['cote'] . '"' . $checked .'
    />
    <label 
      class="titre_label" 
      for="check_' . $row['cote'] . '"
      title="' . $row['bibl'] . '"
      >
      
      <span class="annee">' . $row['annee'] . ', </span><span class="nom">' . $row['nom'] . '</span></label>
  </div>';
    return $div;
}

$cotes = Web::pars(Medict::COTE);
if (0 < count($cotes)) { // si cotes demandées, vérifier qu’elles existent
    // load available dico ids from base
    $coteQ = Medict::$pdo->prepare("SELECT cote FROM dico_titre");
    $coteQ->execute();
    $biblio = $coteQ->fetchAll(PDO::FETCH_COLUMN, 0);
    $cotes = array_intersect($cotes, $biblio);
}
$cotes = array_flip($cotes);



$sql = "SELECT * FROM dico_titre ";
$titreQ = Medict::$pdo->prepare($sql);
$titreQ->execute(array());
echo '
';
while ($row = $titreQ->fetch(PDO::FETCH_ASSOC)) {
    echo titre($cotes, $row);
}


?>
