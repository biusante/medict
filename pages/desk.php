<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
require_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Http, Route};

/** Search form  */

/*
list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(titre_annee), MAX(titre_annee) FROM dico_titre")->fetch();
$an1 = Web::par('an1', $an_min);
$an2 = Web::par('an2', $an_max);



*/
$q = Http::par('q', '');
$t = Http::par('t', '');
$cote = Http::par('cote', '');

?>
<div id="medict">
    <div id="col1">
        <form name="medict" class="recherche" autocomplete="off">
            <?php include(__DIR__.'/titres.php') ?>
            <div class="flexbuts">
                <input name="q" id="q" placeholder="Rechercher un terme" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" type="text"  autocomplete="off"/>
                <a title="Tout réinitialiser" href="<?= Route::home_href() ?>." class="but reset">⟳</a>
            </div>
            <input type="hidden" name="t" value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"/>
            <input type="hidden" name="cote" value="<?= htmlspecialchars($cote, ENT_QUOTES, 'UTF-8'); ?>"/>
            <input type="hidden" name="p" value="<?= intval(Http::par('p', '')); ?>"/>
            <button type="submit">Go</button>
        </form>
        <nav id="mots" class="data"  data-url="data/mots">
            Termes
        </nav>
    </div>
    <div id="col2">
        <div class="pannel entrees" id="panentrees">
            <header>Entrées 
            <!-- <a class="but" target="_blank" href="data/entrees?<?= Http::query() ?>">🡵</a> -->
            </header>
            <nav id="entrees"  class="data" data-url="data/entrees">
            
            </nav>
        </div>
        <div id="sugg_trad">
            <div class="pannel sugg" id="pansugg">
                <header>Mots liés</header>
                <nav id="sugg"  class="data" data-url="data/sugg">
                
                </nav>
            </div>
            <div class="pannel trad" id="pantrad">
                <header>Traductions</header>
                <nav id="trad"  class="data" data-url="data/trad">
                
                </nav>
            </div>
        </div>
    </div>
    <div id="col3">
        <header id="medica">
            <a id="medica-prev" href="#" class="entree"> </a>
            <a id="medica-ext" target="_blank"><?php
// TODO, get entry bibl with &cote=dico_volume.volume_cote, &t=dico_terme_deforme

if ($cote && $t) {
    $sql = "SELECT dico_entree.*, dico_volume.*
FROM dico_entree, dico_rel, dico_volume, dico_terme 
WHERE   dico_volume.volume_cote = ? 
    AND dico_entree.dico_volume = dico_volume.id
    AND dico_terme.deforme = ?
    AND dico_rel.dico_terme = dico_terme.id 
    AND dico_rel.dico_entree = dico_entree.id
";
    $entreeQ = Medict::$pdo->prepare($sql);
    $entreeQ->execute([$cote, $t]);
    $entree = $entreeQ->fetch(PDO::FETCH_ASSOC);
    if ($entree) {
        echo Medict::entree($entree, true) . "\n";
    }
}
            ?></a>
            <a id="medica-next" href="#" class="entree"> </a>
        </header>
        <div>
            <div id="viewcont">
                <img id="image"/>
                <img id="imageHi"/>
            </div>
        </div>
    </div>
</div>
