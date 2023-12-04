<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Http};


// pars
$time_start = microtime(true);
$reqPars = Medict::reqPars();

$q = Http::par('q', null);
if (!$q) return;



$dico_titre = '';
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}

// limiter le nombre de résultats
$limit = 1000;
// pareil que entrees.php
$rels = Medict::rels_vedettes();

/*
// si une seule lettre, c’est lent, contournement
if (mb_strlen($q) == 1) {
    $limit = 100;

    $sql = "
SELECT * 
    FROM dico_terme
    WHERE deforme LIKE ?
    ORDER BY deforme
    LIMIT 1000
";
    $qterme = Medict::$pdo->prepare($sql);
    echo "<!-- $sql -->\n";
    $sql = "
SELECT COUNT(*) AS count
    FROM dico_rel
    WHERE 
        dico_terme = ?
        AND reltype = 1
        $dico_titre
";
    echo "<!-- $sql -->\n";
    $qrel = Medict::$pdo->prepare($sql);


    $qterme->execute([$q.'%']);
    echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";
    $n = 1;
    while ($terme = $qterme->fetch(PDO::FETCH_ASSOC)) {
        $id = $terme['id'];
        // echo $id . " " . $terme['forme']."\n";

        $qrel->execute([$id]);
        $rel = $qrel->fetch();
        if (!$rel) continue;
        $count = $rel['count'];
        if (!$count) continue;
        html($n, $id, $terme['forme'], $count, $q);
        $n++;
        if (!--$limit) break;
    }
    echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";
    return;
}
*/

// Vu avec EXPLAIN, cherche d’abord dans deforme
$sql = "
SELECT
    dico_terme.id AS id,
    forme,
    langue,
    deforme,
    COUNT(dico_entree) AS count
    FROM dico_rel
    INNER JOIN dico_terme
        ON dico_rel.dico_terme = dico_terme.id
        AND (deforme LIKE ?)
    WHERE
        $dico_titre

    GROUP BY deforme
    ORDER BY deforme
    LIMIT $limit
";
/*
    WHERE
        $rels
        $dico_titre
*/

$deforme = Medict::deforme($q);
$uvij = Medict::deforme($q, true);
echo "<!-- \$q=$uvij -->\n";
echo "\n<!-- $sql -->\n";

$starttime = microtime(true);
$query = Medict::$pdo->prepare($sql);
$query->execute([$deforme.'%']);
echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";
$n = 1;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {

    html($n, $row['deforme'], $row['forme'], $row['count'], $deforme);
    $n++;
    $limit--;
}

// limit
$sql = "
SELECT
    dico_terme.id AS id,
    forme,
    langue,
    deforme,
    COUNT(dico_entree) AS count
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
        AND MATCH (deloc) AGAINST (? IN BOOLEAN MODE)
WHERE
    $rels
    $dico_titre
GROUP BY deforme
ORDER BY deforme
LIMIT $limit
";
echo "\n<!-- $sql -->\n";

$starttime = microtime(true);
// si pas q parti ?
if (mb_strpos($deforme, ' ') !== false) {
    $search = '+' . preg_replace('@\s+@ui', '* +', $deforme) . '*';
}
else {
    $search = $deforme . '*';
}
$query = Medict::$pdo->prepare($sql);
$query->execute([$search]);
echo "<!-- search=$search limit=$limit " . number_format(microtime(true) - $time_start, 3). " s. -->\n";
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($n, $row['deforme'], $row['forme'], $row['count'], $deforme);
    $n++;
}
echo '<p class="end"></p>';

function html($n, $deforme, $forme, $count, $q) {
    $href = '?t=' . $deforme;
    $title = htmlspecialchars($forme);
    $terme = Medict::hilite($q, $forme);
    echo '<a draggable="false" href="' . $href .'"><small>' . $n .'.</small> ' . $terme 
    . ' <small>('.  $count . ')</small>'
    .'</a>', "\n";
    flush();
}
