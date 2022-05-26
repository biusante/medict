<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// pars
$starttime = microtime(true);
list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = Web::par('an1', null);
if ($an1 <=  $an_min) $an1 = null;
$an2 = Web::par('an2', null);
if ($an2 >=  $an_max) $an2 = null;
if ($an1 !== null && $an2 !== null && $an2 < $an1) $an2 = $an1;

$q = Web::par('q', null);

if ($q) {
    $q = Medict::sortable($q);
}

$limit = 100; // nombre maximal de vedettes affichées

$fwhere = array();
$fpars = array();
// filtrage
if ($an1 !== null) {
    $fwhere[] = "annee_titre >= ?";
    $fpars[] = $an1;
}
if ($an2 !== null) {
    $fwhere[] = "annee_titre <= ?";
    $fpars[] = $an2;
}
$fsql = "SELECT terme, terme_sort, COUNT(*) AS compte FROM dico_index ";

/* première requête, préfixe uniquement */
$sql = $fsql;
$where = $fwhere;
$pars = $fpars;
if ($q) {
    $where[] = "terme_sort LIKE ?";
    $pars[] = '1' . $q . '%';
}
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($pars, true) . "
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
    $where[] = "MATCH (terme_sort) AGAINST (? IN BOOLEAN MODE)";
    if (mb_strpos($q, ' ') !== false) {
        $pars[] = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
    }
    else {
        $pars[] = $q . '*';
    }
}
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($pars, true) . "
-->\n";
$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q);
    $limit --;
}

echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';


/* // pour index fulltext
$where[] = "MATCH (terme) AGAINST (? IN BOOLEAN MODE)";
if (mb_strpos($q, ' ') !== false) $pars[] = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
else $pars[] = $q . '*';
*/


function html(&$row, $q) {
    $href = '?t=' . $row['terme_sort'];
    $title = htmlspecialchars($row['terme']);
    $value = Medict::hilite($q, $row['terme']);
    echo '<a href="' . $href .'">' . $value . ' <small>(', $row['compte'], ')</small></a>', "\n";
    flush();
}