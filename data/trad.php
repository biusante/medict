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

$sql = "SELECT id, langue FROM dico_terme WHERE deforme = ?";
$qterme = Medict::$pdo->prepare($sql);
$qterme->execute([$t]);
$dico_terme = [];
$langs = [];
while ($terme = $qterme->fetch(PDO::FETCH_ASSOC)) {
    $dico_terme[] = $terme['id'];
    $langs[$terme['langue']] = true;
}
$langs = '(' . implode(', ', $langs) . ')';
$dico_terme = '(' . implode(', ', $dico_terme) . ')';

$reltype_foreign = 11;
$sql = "
SELECT *
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
WHERE
    reltype = $reltype_foreign
    -- AND dico_terme.langue NOT IN $langs
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
    // pas de “traductions” dans la langue de l’entrée demandée (= mot lié)
    if ($last != $rel['id']) {
        // pas de volume ? pas compris pourquoi
        if ($last !== null) {
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        $trad_forme = $rel['forme'];
        if ($rel['langue']) {
            $langue = Medict::$langs[$rel['langue']];
        }
        else {
            $langue = false;
        }
        echo "
<details class=\"sugg\">
    <summary><a class=\"sugg\" href=\"?q=" . rawurlencode($trad_forme) . '"' 
    . ' title="' .  strip_tags($trad_forme) . '">';
    if ($langue) echo "<small>[$langue]</small> ";
    echo $trad_forme;
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
    // si un mot dans une clique de traduction est la vedette de l'article éviter
    // « Même » in Même, mêmes. Littré Robin 13e éd., 1873...
    if ($rel['orth']) {
        $entree['in'] = null;    
    }
    else if ($langue) {
        $entree['in'] = "[$langue] " . $trad_forme;
    }
    else {
        $entree['in'] = $trad_forme;
    }
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
