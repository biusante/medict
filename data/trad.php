<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Http};

// une veddette à chercher
$t = Http::par('t', null);
// rien à chercher
if (!$t) {
    echo '<!-- Aucun mot cherché. -->';
    return; // rien à chercher
}

$time_start = microtime(true);

$sql = "SELECT id FROM dico_terme WHERE deforme = ?";
$qterme = Medict::$pdo->prepare($sql);
$qterme->execute([$t]);
$dico_terme = [];
while ($terme = $qterme->fetch(PDO::FETCH_ASSOC)) {
    $dico_terme[] = $terme['id'];
}
$dico_terme = '(' . implode(', ', $dico_terme) . ')';

$reltype_foreign = 3;
$sql = "
SELECT *
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
WHERE
    reltype = $reltype_foreign
    AND dico_terme NOT IN $dico_terme
    AND dico_entree IN (SELECT dico_entree FROM dico_rel WHERE reltype = $reltype_foreign AND dico_terme IN $dico_terme)
ORDER BY langue, deforme, volume_annee DESC

";

$qrel = Medict::$pdo->prepare($sql);
$qrel->execute([]);
echo "<!-- $t ; $sql -->\n";
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
        // pas de volume ? pas compris pourquoi
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

    if (!isset($entree['volume_cote']) || !$entree['volume_cote']) {
        // pb dans les données le volume n’existe pas
        continue;
    }


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
