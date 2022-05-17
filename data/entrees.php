<?php


include_once(dirname(__DIR__) . "/Medict.php");

$t = false;
if (isset($_REQUEST['t'])) $t = $_REQUEST['t'];
if (!$t) {
    return;
}

list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = null;
if (isset($_REQUEST['an1'])) $an1 = $_REQUEST['an1'];
if ($an1 < $an_min) $an1 = null;
else if ($an1 >= $an_max) $an1 = null;
$an2 = null;
if (isset($_REQUEST['an2'])) $an2 = $_REQUEST['an2'];
if ($an2 > $an_max) $an2 = null;
else if ($an2 < $an1) $an2 = $an1;


$sql = "SELECT dico_entree FROM dico_index WHERE terme_sort = ? ";
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
    $url = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=' . $entree['cote_volume'] . '&amp;p=' . $entree['url'];
    echo '<div class="entree">';
    echo '<a class="entree" target="facs" href="' . $url . '">';
    echo '<b>' . $entree['vedette'] . '</b>.';
    echo ' <i>' . $entree['nom_volume'] . '</i> (' . $entree['annee_volume'] . ', ';
    if ($entree['page2'] != null) echo "pps. " . $entree['page'] . '-' . $entree['page2'];
    else echo "p. " . $entree['page'];
    echo ")</a>\n";
    echo "</div>\n";
    echo "</div>\n";
    flush();
}
