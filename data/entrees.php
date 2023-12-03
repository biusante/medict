<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
declare(strict_types=1);

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Http};

$reqPars = Medict::reqPars();

// une veddette à chercher
$t = Http::par('t', null);
// rien à chercher
if (!$t) return;

$limit = 1000;

// filtre de titre
$dico_titre = '';
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}

// pareil que mots.php
$rels = Medict::rels_vedettes();


//     AND $rels

$sql = "
SELECT *
FROM dico_rel
WHERE
    dico_terme IN (SELECT id FROM dico_terme WHERE deforme = ?)
    $dico_titre
ORDER BY
    volume_annee,
    dico_entree
    LIMIT $limit
";
$pars = [$t];

echo "<!-- 
$sql
" . print_r($pars, true) . "
-->
";

$starttime = microtime(true);

$relQ = Medict::$pdo->prepare($sql);
$relQ->execute($pars);
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';

// Récupérer la forme trouvée
$formeQ = Medict::$pdo->prepare("SELECT forme FROM dico_terme WHERE deforme = ?"); 
$formeQ->execute($pars);
$row = $formeQ->fetch();
$forme = null;
if ($row) $forme = $row['forme'];


$sql = "
SELECT
    * 
FROM dico_entree
INNER JOIN dico_volume
    ON dico_entree.dico_volume = dico_volume.id
WHERE dico_entree.id = ?
";
$entreeQ = Medict::$pdo->prepare($sql);
while ($rel = $relQ->fetch(PDO::FETCH_ASSOC)) {
    $entreeQ->execute(array($rel['dico_entree']));
    $entree = $entreeQ->fetch(PDO::FETCH_ASSOC);
    // rajouter le mot d’origine si pas vedette
    if ($rel['reltype'] != 1) {
        $entree['in'] = $forme;
        $entree['page'] = $rel['page'];
        $entree['refimg'] = $rel['refimg'];
        $entree['page2'] = null;
    }
    echo Medict::entree($entree) . "\n";
    flush();
}
