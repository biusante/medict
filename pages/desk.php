<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
require_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Route,Web};

/** Search form  */

/*
list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(titre_annee), MAX(titre_annee) FROM dico_titre")->fetch();
$an1 = Web::par('an1', $an_min);
$an2 = Web::par('an2', $an_max);
*/
$q = Web::par('q', '');
$t = Web::par('t', '');

?>
<div id="medict">
    <div id="col1">
        <form name="medict" class="recherche" autocomplete="off">
            <input type="hidden" name="t" value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"/>
            <input type="hidden" name="cote" value="<?= htmlspecialchars( Web::par('cote', ''), ENT_QUOTES, 'UTF-8'); ?>"/>
            <input type="hidden" name="p" value="<?= intval( Web::par('p', '')); ?>"/>
            <input type="hidden" name="bibl" />
            <div class="flexbuts">
                <input name="q" id="q" placeholder="Rechercher un terme" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" type="text"  autocomplete="off"/>
                <a title="Effacer la sélection de titres" href="<?= Route::app_href() ?>." class="but reset">⟳</a>
            </div>
            <?php include(__DIR__.'/titres.php') ?>
            <button type="submit">Go</button>
        </form>
        <nav id="mots" class="data"  data-url="data/mots">
            Termes
        </nav>
    </div>
    <div id="col2">
        <div class="pannel entrees" id="panentrees">
            <header>Entrées</header>
            <nav id="entrees"  class="data" data-url="data/entrees">
            
            </nav>
        </div>
        <div id="sugg_trad">
            <div class="pannel sugg" id="pansugg">
                <header>Suggestions</header>
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
            <a id="medica-ext" target="_blank" title="Lien vers l’URL pérenne de cette page"></a>
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
