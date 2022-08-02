<?php 

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

/** Reception of a corpus selection */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // load available dico ids from base
    $coteQ = Medict::$pdo->prepare("SELECT cote FROM dico_titre");
    $coteQ->execute();
    $result = $coteQ->fetchAll(PDO::FETCH_COLUMN, 0);
    $dico_titre = Web::pars('dico_titre');
    print_r($dico_titre);
}

$sql = "SELECT * FROM dico_titre ";
$titreQ = Medict::$pdo->prepare($sql);
$titreQ->execute(array());
while ($row = $titreQ->fetch(PDO::FETCH_ASSOC)) {
    echo titre($row);
}

function titre($row)
{
    $checked = true;
    $div = '';
    $div .= '  <label class="dico_label';
    if ($checked) $div .= ' checked';
    $div .= '">'."\n";
    $div .= '    <input class="dico_check" type="checkbox" name="dico_titre" value="' . $row['cote'] . '"';
    if ($checked) $div .= ' checked="checked"';
    $div .= '/>'."\n";
    $div .= $row['nom'];
    $div .= '      (';
    if ($row['ed']) $div .= $row['ed'] . ', ';
    $div .= $row['annee'];
    if ($row['an_max']) $div .= '-' . $row['an_max'];
    $div .=  ")\n";
    $div .= '  </label>'."\n";
    return $div;
}

?>