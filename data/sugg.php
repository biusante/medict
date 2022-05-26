<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$terme1 = preg_replace('@^1@', '', Web::par('t', null));
if (!$terme1) return; // rien à chercher


$starttime = microtime(true);

$sql = "SELECT * FROM dico_sugg WHERE terme1_sort = ? ORDER BY score DESC, terme2_sort";
$qsugg = Medict::$pdo->prepare($sql);
$qsugg->execute(array($terme1));

echo "<!-- $sql ; $terme1 -->\n";

$qentree = Medict::$pdo->prepare("SELECT * FROM dico_entree WHERE id = ?");
$last = null;
while ($sugg = $qsugg->fetch(PDO::FETCH_ASSOC)) {
    $terme2 = $sugg['terme2'];
    if ($terme2 != $last) {
        if ($last !== null) {
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        echo "
<details class=\"sugg\">
    <summary><a class=\"sugg\" href=\"?q=" . rawurlencode($terme2) . "\">$terme2 <small>(". $sugg['score'], ")</small></a></summary>";
        $last = $terme2;
    }
    $qentree->execute(array($sugg['dico_entree']));
    $entree = $qentree->fetch();
    echo "\n".Medict::entree($entree);
}
echo "\n</details>";
echo "\n&#10;";
flush();
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
