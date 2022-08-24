<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

$reqPars = Medict::reqPars();

// une veddette à chercher
$t = Web::par('t', null);
if (!$t) return; // rien à chercher
$t = '1' . Medict::sortable($t);

$pars = array($t);
$sql = "SELECT * FROM dico_index WHERE orth_sort LIKE ? ";


// construire la requête de filtrage
$where = array();
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $where[] = " dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}
if (count($where) > 0) {
    $sql .= ' AND ' . implode(' AND ', $where);
}
$sql .= " ORDER BY annee_titre";

echo "<!-- " . $_SERVER['REQUEST_URI'] . "
$sql
" . print_r($pars, true) . "
-->
";
        
// $sql .= " ORDER BY mot.annee AND " 
$motQ = Medict::$pdo->prepare($sql);
$motQ->execute($pars);
$entreeQ = Medict::$pdo->prepare("SELECT * FROM dico_entree WHERE id = ?");
while ($row = $motQ->fetch(PDO::FETCH_ASSOC)) {
    $entreeQ->execute(array($row['dico_entree']));
    $entree = $entreeQ->fetch(PDO::FETCH_ASSOC);
    echo Medict::entree($entree) . "\n";
    flush();
}
