<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$src = preg_replace('@^1@', '', Web::par('t', null));
if (!$src) return; // rien à chercher


$starttime = microtime(true);

$sql = "SELECT * FROM dico_sugg WHERE src_sort = ? AND cert = 1 ORDER BY dst_sort, volume_annee";
$qsugg = Medict::$pdo->prepare($sql);
$qsugg->execute(array($src));

$qfilter = null;
// filtre par cote
$reqPars = Medict::reqPars();
if ($reqPars[Medict::DICO_TITRE]) {
    $sql = "SELECT terme, terme_sort FROM dico_index WHERE terme_sort LIKE ? ";
    $sql .= " AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
    $sql .= " LIMIT 1"; 
    $qfilter = Medict::$pdo->prepare($sql);
}


echo "<!-- $sql ; $src -->\n";

$qentree = Medict::$pdo->prepare("SELECT * FROM dico_entree WHERE id = ?");
$last = null;
while ($sugg = $qsugg->fetch(PDO::FETCH_ASSOC)) {
    if ($last != $sugg['dst_sort']) {
        if ($last !== null) {
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        if ($qfilter) {
            $qfilter->execute(array('1' .  $sugg['dst']));
            if (!$qfilter->fetch()) continue;
        }


        echo "
<details class=\"sugg\">
    <summary><a class=\"sugg\" href=\"?q=" . rawurlencode($sugg['dst']) . "\">" . $sugg['dst'];
    echo " <small>(". $sugg['score'], ")</small>";
        echo"</a></summary>";
        $last = $sugg['dst_sort'];
    }
    $qentree->execute(array($sugg['dico_entree']));
    $entree = $qentree->fetch();
    $entree['page'] = $sugg['page'];
    $entree['page2'] = null;
    $entree['refimg'] = $sugg['refimg'];
    echo "\n".Medict::entree($entree);
}
echo "\n</details>";
echo "\n&#10;";
flush();
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
