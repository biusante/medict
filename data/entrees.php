<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$t = Web::par('t', null);
if (!$t) return; // rien à chercher
$t = '1' . Medict::sortable($t);

list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = Web::par('an1', $an_min);
$an2 = Web::par('an2', $an_max);
if ($an2 < $an1) $an2 = $an1;

$sql = "SELECT dico_entree FROM dico_index WHERE terme_sort LIKE ? ";

$pars = array($t);
if ($an1 !== null) {
    $pars[] = $an1;
    $where[] = "annee_titre >= ?";
}
if ($an2 !== null) {
    $pars[] = $an2;
    $where[] = "annee_titre <= ?";
}
if (count($where) > 0) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

echo "<!-- " . $_SERVER['REQUEST_URI'] . "
$sql
" . print_r($pars, true) . "
-->\n";
        
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
