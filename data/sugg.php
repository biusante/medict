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

// Le mot source
$sql = "SELECT forme FROM dico_terme WHERE id = ?";
$qsrc = Medict::$pdo->prepare($sql);
$qsrc->execute([$t]);
$row = $qsrc->fetch();
if (!$row) {
    echo "<!-- $t Mot inconnu -->";
    return; // rien à chercher
}
$src_forme = $row['forme'];

$starttime = microtime(true);

$sql = "
SELECT *
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
WHERE
    reltype = 3
    AND dico_terme != ?
    AND dico_entree IN (SELECT dico_entree FROM dico_rel WHERE reltype = 3 AND dico_terme = ?)
ORDER BY deforme, volume_annee

";

$qrel = Medict::$pdo->prepare($sql);
$qrel->execute([$t, $t]);
echo "<!-- $sql ; $t -->\n";
echo "<!--", number_format(microtime(true) - $starttime, 3), " s. -->\n";

$qfilter = null;

// filtre par cote
// ne pas suggérer un mot qu’on ne trouverait pas dans le corpus
$reqPars = Medict::reqPars();
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
    $sql = "
SELECT *
FROM dico_rel
WHERE
    dico_terme = ?
    AND (reltype = 1 OR reltype = 4)
    $dico_titre
LIMIT 1;
    ";
    $qfilter = Medict::$pdo->prepare($sql);
}



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
        if ($qfilter) {
            $qfilter->execute(array($rel['id']));
            if (!$qfilter->fetch()) {
                $last = null;
                continue;
            }
        }
        $forme = $rel['forme'];

        echo "
<details class=\"sugg\">
    <summary><a class=\"sugg\" href=\"?q=" . rawurlencode($forme) . '"' 
    . ' title="' .  strip_tags($forme) . '">' 
    . $forme;
    /* On ne sait pas encore le score ici encore
    echo " <small>(". $sugg['score'], ")</small>";
    */
        echo"</a></summary>";
        $last = $rel['id'];
    }
    $qentree->execute(array($rel['dico_entree']));
    $entree = $qentree->fetch();
    // $entree['in'] = 'V. ' . $forme;
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
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
