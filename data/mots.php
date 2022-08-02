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

$limit = 100; // nombre maximal de vedettes affichées

// construire la requête de filtrage
$fwhere = array();
if ($reqPars['an1'] !== null) $fwhere["annee_titre >= ?"] = $reqPars['an1'];
if ($reqPars['an2'] !== null) $fwhere["annee_titre <= ?"] = $reqPars['an2'];
// base de la requête sql
$fsql = "SELECT terme, terme_sort, COUNT(*) AS compte FROM dico_index ";

// première requête, préfixe uniquement, copier les paramètres communs
$sql = $fsql;
$where = $fwhere;
if ($q) $where["terme_sort LIKE ?"] = '1' . $q . '%';

if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', array_keys($where));
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($where, true) . "
-->\n";
$query->execute(array_values($where));
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    html($row, $q);
    $limit--;
}
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
$starttime = microtime(true);


/* suite, dans les expressions */
$sql = $fsql;
$where = $fwhere;
if ($q) {
    if (mb_strpos($q, ' ') !== false) {
        $v = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
    }
    else {
        $v = $q . '*';
    }
    $where["MATCH (terme_sort) AGAINST (? IN BOOLEAN MODE)"] = $v;
}
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', array_keys($where));
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT " . $limit;
$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($where, true) . "
-->\n";
$query->execute(array_values($where));
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