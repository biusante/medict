<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};


// pars
$time_start = microtime(true);
$reqPars = Medict::reqPars();

$q = Web::par('q', null);
if (!$q) return;

if ($q) {
    $q = Medict::deforme($q);
}

$dico_titre = '';
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}

// limiter le nombre de résultats
$limit = 1000;
$rels = "(reltype = 1 OR (reltype = 4 AND ORTH IS NULL ) OR (reltype = 2 AND ORTH IS NULL ))";

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
        AND deforme LIKE ?
    WHERE
        $rels
        $dico_titre
    GROUP BY deforme
    ORDER BY deforme
    LIMIT $limit
";
echo "<!-- \$q=$q -->\n";

$starttime = microtime(true);
$query = Medict::$pdo->prepare($sql);
$query->execute([$q.'%']);
echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";
$n = 1;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($n, $row['id'], $row['forme'], $row['count'], $q);
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
if (mb_strpos($q, ' ') !== false) {
    $search = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
}
else {
    $search = $q . '*';
}
$query = Medict::$pdo->prepare($sql);
$query->execute([$search]);
echo "<!-- search=$search limit=$limit " . number_format(microtime(true) - $time_start, 3). " s. -->\n";
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($n, $row['id'], $row['forme'], $row['count'], $q);
    $n++;
}
echo '<p class="end"></p>';

function html($n, $id, $forme, $count, $q) {
    $href = '?t=' . $id;
    $title = htmlspecialchars($forme);
    $terme = Medict::hilite($q, $forme);
    echo '<a href="' . $href .'"><small>' . $n .'.</small> ' . $terme 
    . ' <small>('.  $count . ')</small>'
    .'</a>', "\n";
    flush();
}
