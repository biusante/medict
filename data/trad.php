<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$t = Web::par('t', null);
// rien à chercher
if (!$t) {
    echo '<!-- Aucun mot cherché. -->';
    return; // rien à chercher
}

$time_start = microtime(true);

$sql = "
SELECT *
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
WHERE
    reltype = 2
    AND dico_terme != ?
    AND dico_entree IN (SELECT dico_entree FROM dico_rel WHERE reltype = 2 AND dico_terme = ?)
ORDER BY langue, deforme, volume_annee

";

$qrel = Medict::$pdo->prepare($sql);
$qrel->execute([$t, $t]);
echo "<!-- $sql ; $t -->\n";
echo "<!--", number_format(microtime(true) - $time_start, 3), " s. -->\n";

$sql = "
SELECT
    * 
FROM dico_entree
INNER JOIN dico_volume
    ON dico_entree.dico_volume = dico_volume.id
WHERE dico_entree.id = ?
";
$qentree = Medict::$pdo->prepare($sql);
$last = null;
while ($rel = $qrel->fetch(PDO::FETCH_ASSOC)) {
    if ($last != $rel['id']) {

        if ($last !== null) {
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        $forme = $rel['forme'];
        $langue = Medict::$langs[$rel['langue']];
        echo "
<details class=\"sugg\">
    <summary><a class=\"sugg\" href=\"?q=" . rawurlencode($forme) . '"' 
    . ' title="' .  strip_tags($forme) . '">' 
    . "<small>[$langue]</small> "
    . $forme;
    /* On ne sait pas encore le score ici encore
    echo " <small>(". $sugg['score'], ")</small>";
    */
        echo"</a></summary>";
        $last = $rel['id'];
    }
    $qentree->execute(array($rel['dico_entree']));
    $entree = $qentree->fetch();
    $entree['in'] = "[$langue] " . $forme;
    $entree['page'] = $rel['page'];
    $entree['refimg'] = $rel['refimg'];
    $entree['page2'] = null;
    echo "\n".Medict::entree($entree);    
}
if ($last) {
    echo "\n</details>";
    echo "\n&#10;";
    flush();    
}
return;

$sql = "SELECT * FROM dico_trad WHERE src_sort = ? ORDER BY dst_langno, dst_sort, volume_annee, page";
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
    <summary title="' . $row['dst'] . '">
      <a class="sugg" href="?q=' . rawurlencode($row['dst']) . '">'
    . '<small>[' . $row['dst_lang'] . ']</small> ' . $row['dst'] . '</a></summary>';
    }
    $q_entree->execute(array($row['dico_entree']));
    $entree = $q_entree->fetch();
    if (!$entree) {
        // pas normal, mais déjà vu
        continue;
    }

    $entree['page'] = $row['page'];
    $entree['page2'] = null;
    $entree['refimg'] = $row['refimg'];


    echo "\n".Medict::entree($entree);
}
echo "\n</details>";
echo "\n&#10;";
flush();
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
