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
echo "<!-- \$q=$q -->\n";


$starttime = microtime(true);
$query = Medict::$pdo->prepare($sql);
$query->execute([$q.'%']);
echo "<!--", number_format(microtime(true) - $starttime, 3), " s. -->\n";
$n = 1;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q, $n);
    $n++;
    $limit--;
}

// limit
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
        AND MATCH (locutable) AGAINST (? IN BOOLEAN MODE)
WHERE
    reltype = 1
    $dico_titre
GROUP BY sortable
ORDER BY sortable
LIMIT $limit
";
echo "\n<!-- $sql -->\n";

$starttime = microtime(true);
// si pas q parti ?
if (mb_strpos($q, ' ') !== false) {
    $search = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
}
else {
    $search = $q . '*';
}
$query = Medict::$pdo->prepare($sql);
$query->execute([$search]);
echo "<!-- search=$search limit=$limit " . number_format(microtime(true) - $starttime, 3). " s. -->\n";
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