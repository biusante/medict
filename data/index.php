<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");
$q = null;
if (isset($_REQUEST['q'])) $q = htmlspecialchars(trim($_REQUEST['q']));

list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = null;
if (isset($_REQUEST['an1'])) $an1 = $_REQUEST['an1'];
if ($an1 <= $an_min) $an1 = null;
else if ($an1 >= $an_max) $an1 = null;
$an2 = null;
if (isset($_REQUEST['an2'])) $an2 = $_REQUEST['an2'];
if ($an2 > $an_max) $an2 = null;
else if ($an2 < $an1) $an2 = $an1;


$limit = 100; // nombre maximal de vedettes affichées

$starttime = microtime(true);
$sql = "SELECT terme, terme_sort, COUNT(*) AS compte, dico_entree FROM dico_index ";
$pars = array();
$where = array();
// affixe demandé
$hire = array();
if ($q) {
    // juste au début
    if (mb_strlen($q) < 4) {
        $where[] = "terme_sort LIKE ?";
        $pars[] = $q . '%';
    } else {
        $where[] = "MATCH (terme) AGAINST (? IN BOOLEAN MODE)";
        if (mb_strpos($q, ' ') !== false) $pars[] = '+' . preg_replace('@\s+@ui', '* +', $q) . '*';
        else $pars[] = $q . '*';
    }
    /*
  $q = trim(Medict::sortable($q));
  // split words ?
  $tokens = preg_split('@\s+@ui', $q);
  $first = true;
  $clause = "(";
  foreach($tokens as $tok) {
    $tok = trim($tok);
    if (!$tok) continue;
    if ($first) $first = false;
    else $clause .= " AND "; // OR, AND ?
    if (mb_strlen($tok) > 4) $tok = '%'.$tok;
    $tok .= '%';
    $pars[] = $tok;
    $clause .= "terme_sort LIKE ?";
  }
  $clause .= ")";
  $where[] = $clause;
  */
}
if ($an1 !== null) {
    $pars[] = $an1;
    $where[] = "annee_titre >= ?";
}
if ($an2 !== null) {
    $pars[] = $an2;
    $where[] = "annee_titre <= ?";
}
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT " . $limit;
// if (isset($_REQUEST['de']) )

$query = Medict::$pdo->prepare($sql);
echo "<!-- $sql
" . print_r($pars, true) . "
-->\n";

$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $href = urlencode(utf8_encode($row['terme_sort']));
    $title = htmlspecialchars($row['terme']);
    $value = Medict::hilite($q, $row['terme']);
    echo '<a href="#' . $href .'">' . $value . ' <small>(', $row['compte'], ')</small></a>', "\n";
}
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
