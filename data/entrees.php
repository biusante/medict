<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
declare(strict_types=1);

include_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

$reqPars = Medict::reqPars();

// une veddette à chercher
$t = Web::par('t', null);
// rien à chercher
if (!$t) return;

$limit = 1000;

// filtre de titre
$dico_titre = '';
// filtre par cote
if ($reqPars[Medict::DICO_TITRE]) {
    $dico_titre = "AND dico_titre IN (" . implode(", ", $reqPars[Medict::DICO_TITRE]) . ")";
}


$sql = "
SELECT *
FROM dico_rel
WHERE
    dico_terme = ?
    AND reltype = 1
--    $dico_titre
ORDER BY
    volume_annee,
    dico_entree
-- LIMIT $limit
";

$pars = [$t];

echo "<!-- " . $_SERVER['REQUEST_URI'] . "
$sql
" . print_r($pars, true) . "
-->
";

$starttime = microtime(true);
$motQ = Medict::$pdo->prepare($sql);
$motQ->execute($pars);
echo '<!--', number_format(microtime(true) - $starttime, 3), ' s. -->';


$sql = "
SELECT
    * 
FROM dico_entree
INNER JOIN dico_volume
    ON dico_entree.dico_volume = dico_volume.id
WHERE dico_entree.id = ?
";
$entreeQ = Medict::$pdo->prepare($sql);
while ($row = $motQ->fetch(PDO::FETCH_ASSOC)) {
    $entreeQ->execute(array($row['dico_entree']));
    $entree = $entreeQ->fetch(PDO::FETCH_ASSOC);
    echo Medict::entree($entree) . "\n";
    flush();
}
