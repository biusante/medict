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

$q = trim(Http::par('q', null));
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
// test si uvji utile
$deforme = Medict::deforme($q);
$uvji = strtr($deforme, ['j' => 'i', 'u' => 'v']);
// pas d’uvji pour une lettre ou pas d’u, j
if ($deforme == $uvji || mb_strlen( $uvji, "UTF-8") < 2) {
    $uvji = null;
}
$inverse = null;
if ($q[0] === '*') {
    $inverse = implode(array_reverse(preg_split('//u', $deforme, -1, PREG_SPLIT_NO_EMPTY)));
}

if ($inverse) {
    $where = "(inverse LIKE ?)";
}
else if ($uvji) {
    $where = "(deforme LIKE ? OR uvji LIKE ?)";
}
else {
    $where = "deforme LIKE ?";
}


/*
// Vu avec EXPLAIN, cherche d’abord dans deforme
$sql = "
SELECT
    dico_terme.id AS id,
    forme,
    dico_terme.langue AS langue,
    deforme,
    COUNT(dico_entree) AS count
    FROM dico_rel
    INNER JOIN dico_terme
        ON dico_rel.dico_terme = dico_terme.id
        AND $where
    WHERE
        $rels
        $dico_titre
    GROUP BY deforme
    ORDER BY deforme
    LIMIT $limit
";
*/


$sql = "
SELECT
    deforme,
    dico_terme.id AS id,
    dico_terme.forme,
    dico_terme.langue AS langue,
    COUNT(dico_entree) AS count
FROM dico_terme, dico_rel
WHERE
    dico_rel.dico_terme = dico_terme.id
    AND $rels
    AND $where
    $dico_titre
GROUP BY deforme
ORDER BY deforme
LIMIT $limit
";


echo "\n<!-- $sql -->\n";

$starttime = microtime(true);
$query = Medict::$pdo->prepare($sql);
if ($inverse) {
    $query->execute([$inverse.'%']);
}
else if ($uvji) {
    $query->execute([$deforme.'%', $uvji.'%']);
}
else {
    $query->execute([$deforme.'%']);
}
// $query->execute([$deforme.'%']);
echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";
$n = 0;
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $n++;
    html($n, $row['deforme'], $row['forme'], $row['count'], $q);
    $limit--;
}

// locutions, recherche par index plein texte
// le champ "deloc" omet le premier mot pour éviter de répéter ci-dessus
$loc_col = "deloc";
// "Coeur de boeuf", trouver avec "coeur boeuf" 
// si pas de résultat ci-dessus, chercher partout
if (!$n) {
    $loc_col = "deforme";
}
// ne pas cherche dans les locutions en inverse ?
if (!$inverse) {
    // limit
    $sql = "
    SELECT
        dico_terme.id AS id,
        dico_terme.forme AS forme,
        dico_terme.langue AS langue,
        deforme,
        COUNT(dico_entree) AS count
    FROM dico_rel
    INNER JOIN dico_terme
        ON dico_rel.dico_terme = dico_terme.id
            AND MATCH ($loc_col) AGAINST (? IN BOOLEAN MODE)
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
        $n++;
        html($n, $row['deforme'], $row['forme'], $row['count'], $q);
    }

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
