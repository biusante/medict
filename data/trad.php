<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$t = preg_replace('@^1@', '', Web::par('t', null));
// désaccentuer ?
if (!$t) return; // rien à chercher


$starttime = microtime(true);

$sql = "SELECT * FROM dico_trad WHERE src_sort = ? AND src_lang IN ('fra', 'lat', 'grc') ORDER BY dst_langno, dst_sort, volume_annee, page";
$q_mot = Medict::$pdo->prepare($sql);
$q_mot->execute(array($t));

echo "<!-- $sql ; $t -->\n";

$q_entree = Medict::$pdo->prepare("SELECT * FROM dico_entree WHERE id = ?");
$last_sort = null;
$last_lang = null;
while ($row = $q_mot->fetch(PDO::FETCH_ASSOC)) {
    if (
        $last_lang != $row['dst_lang']
        || $last_sort != $row['dst_sort']
    ) {
        $last_lang = $row['dst_lang'];
        $last_sort = $row['dst_sort'];
        // fermer le dernier
        if ($last_sort !== null) {
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        echo '
<details class="sugg">
    <summary>[' . $row['dst_lang'] . '] <a class="sugg" href="?q=' . rawurlencode($row['dst']) . '">' . $row['dst'] . '</a></summary>';
    }
    $q_entree->execute(array($row['dico_entree']));
    $entree = $q_entree->fetch();
    $entree['page'] = $row['page'];
    $entree['page2'] = null;
    $entree['refimg'] = $row['refimg'];
    echo "\n".Medict::entree($entree);
}
echo "\n</details>";
echo "\n&#10;";
flush();
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
