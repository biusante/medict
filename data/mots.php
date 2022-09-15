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
if (!$q) return;

if ($q) {
    $q = Medict::sortable($q);
}

$dico_titre = '';
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}


$limit = 1000; // nombre maximal de vedettes affichées

// Vu avec EXPLAIN, cherche d’abord dans sortable
$sql = "
SELECT
    dico_terme.id AS id,
    forme,
    langue,
    sortable,
    COUNT(dico_entree) AS count
    FROM dico_rel
    INNER JOIN dico_terme
        ON dico_rel.dico_terme = dico_terme.id
        AND sortable LIKE ?
    WHERE
        reltype = 1
        $dico_titre
    GROUP BY sortable
    ORDER BY sortable
    LIMIT $limit
";
$query = Medict::$pdo->prepare($sql);
$pars = [$q.'%'];

echo "<!-- $sql
".print_r($pars, true)."
-->\n";


$starttime = microtime(true);
$query->execute($pars);
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
$n = 1;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q, $n);
    $n++;
    $limit--;
}



return;

// construire la requête de filtrage
$fwhere = array();
$fpars = array();
// base de la requête sql
$fsql = "SELECT orth, orth_sort, COUNT(*) AS compte FROM dico_index WHERE ";

// première requête, préfixe uniquement, copier les paramètres communs
$sql = $fsql;
$where = $fwhere;
$pars = $fpars;
if ($q) {
    $where[] = "orth_sort LIKE ?";
    $pars[] = '1' . $q . '%';
}

if (count($where) > 0) {
    $sql .= implode(' AND ', $where);
}
$sql .= " GROUP BY orth_sort ORDER BY orth_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($where, true) . "
-->\n";
$query->execute($pars);
$n = 1;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q, $n);
    $n++;
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
    $sql .= implode(' AND ', $where);
}
$sql .= " GROUP BY orth_sort ORDER BY orth_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
WHERE " . print_r($where, true) . "
PARS " . print_r($pars, true) . "
-->\n";
$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q, $n);
    $n++;
}



function html(&$row, $q, $n) {
    $href = '?t=' . $row['id'];
    $title = htmlspecialchars($row['forme']);
    $terme = Medict::hilite($q, $row['forme']);
    echo '<a href="' . $href .'"><small>' . $n .'.</small> ' . $terme 
    . ' <small>('.  $row['count'] . ')</small>'
    .'</a>', "\n";
    flush();
}