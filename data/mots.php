<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};


// pars
$starttime = microtime(true);
$reqPars = Medict::reqPars();

$q = Web::par('q', null);

if ($q) {
    $q = Medict::sortable($q);
}

$limit = 1000; // nombre maximal de vedettes affichées

// construire la requête de filtrage
$fwhere = array();
$fpars = array();
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $fwhere[] = " dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}
// base de la requête sql
$fsql = "SELECT orth, orth_sort, COUNT(*) AS compte FROM dico_index WHERE orth_lang IN ('fra', 'lat', 'grc')";

// première requête, préfixe uniquement, copier les paramètres communs
$sql = $fsql;
$where = $fwhere;
$pars = $fpars;
if ($q) {
    $where[] = "orth_sort LIKE ?";
    $pars[] = '1' . $q . '%';
}

if (count($where) > 0) {
    $sql .= ' AND ' . implode(' AND ', $where);
}
$sql .= " GROUP BY orth_sort ORDER BY orth_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($where, true) . "
-->\n";
$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q);
    $limit--;
}
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
$starttime = microtime(true);


/* suite, dans les expressions */
$sql = $fsql;
$where = $fwhere;
$pars = $fpars;
if ($q) {
    if (mb_strpos($q, ' ') !== false) {
        $v = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
    }
    else {
        $v = $q . '*';
    }
    $where[] = "MATCH (orth_sort) AGAINST (? IN BOOLEAN MODE)";
    $pars[] = $v;
}
if (count($where) > 0) {
    $sql .= ' AND ' . implode(' AND ', $where);
}
$sql .= " GROUP BY orth_sort ORDER BY orth_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($where, true) . "
-->\n";
$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q);
    $limit --;
}

echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';


function html(&$row, $q) {
    $href = '?t=' . $row['orth_sort'];
    $title = htmlspecialchars($row['orth']);
    $value = Medict::hilite($q, $row['orth']);
    echo '<a href="' . $href .'">' . $value . ' <small>(', $row['compte'], ')</small></a>', "\n";
    flush();
}