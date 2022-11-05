<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

// une veddette à chercher
$starttime = microtime(true);
$t = Web::par('t', null);
$sql = "SELECT id FROM dico_terme WHERE deforme = ?";
$qterme = Medict::$pdo->prepare($sql);
$qterme->execute([$t]);
$dico_terme = [];
while ($terme = $qterme->fetch(PDO::FETCH_ASSOC)) {
    $dico_terme[] = $terme['id'];
}
// rien à chercher
if (!count($dico_terme)) {
    echo "<!-- $t, mot inconnu -->";
    return; // rien à chercher
}
$dico_terme = array_unique($dico_terme);
$dico_terme = '(' . implode(', ', $dico_terme) . ')';


// $src_forme = $row['forme'];

$reltype_clique = 10;
$sql = "
SELECT *
FROM dico_rel
INNER JOIN dico_terme
    ON dico_rel.dico_terme = dico_terme.id
WHERE
    reltype = $reltype_clique
    AND dico_terme NOT IN $dico_terme -- exclure l’entrée demandée
    AND clique IN ( -- les mots liés par une clique
        SELECT DISTINCT clique FROM dico_rel WHERE 
        reltype = $reltype_clique 
        AND dico_terme IN $dico_terme
    )
ORDER BY deforme, volume_annee, refimg

";

$qrel = Medict::$pdo->prepare($sql);
$qrel->execute([]);
echo "<!-- $sql ; $t -->\n";
echo "<!--", number_format(microtime(true) - $starttime, 3), " s. -->\n";

$qfilter = null;

// filtre par cote
// ne pas suggérer un mot qu’on ne trouverait pas comme vedette dans le corpus
$reqPars = Medict::reqPars();
if ($reqPars[Medict::DICO_TITRE]) {
    $reltype_orth = 1;
    $reltype_term = 2;

    $dico_titre = "dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
    $sql = "
SELECT *
FROM dico_rel
WHERE
    dico_terme = ?
    AND (reltype = $reltype_orth OR reltype = $reltype_term)
    AND $dico_titre
LIMIT 1;
    ";
    echo "<!-- FILTRE $sql -->\n";

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
$last_terme = null;
$last_refimg = null;
// boucler sur toutes les relations de mots liés
// en ordre alpha des formes, et chrono inverse
// $last_terme permet de grouper les relations par forme
while ($rel = $qrel->fetch(PDO::FETCH_ASSOC)) {
    if ($last_terme != $rel['id']) {

        if ($last_terme !== null) { // clore un bloc mot si ouvert
            echo "\n</details>";
            echo "\n&#10;";
            flush();
        }
        // mot inconnu du corpus choisi, on passe
        if ($qfilter) {
            $qfilter->execute(array($rel['id']));
            if (!$qfilter->fetch()) {
                $last_terme = null;
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
        $last_terme = $rel['id'];
        $last_refimg = null;
    }
    // ne pas répéter la même page
    if ($last_refimg !== null && $last_refimg == $rel['refimg']) {
        continue;
    }
    $last_refimg = $rel['refimg'];
    $qentree->execute(array($rel['dico_entree']));
    $entree = $qentree->fetch();
    // si pas vedette, rappel de l’indice à trouver
    if (!$rel['orth']) $entree['in'] = $forme;
    $entree['page'] = $rel['page'];
    $entree['refimg'] = $rel['refimg'];
    $entree['page2'] = null;
    echo "\n".Medict::entree($entree);    
}
if ($last_terme) {
    echo "\n</details>";
    echo "\n&#10;";
    flush();    
}
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';
