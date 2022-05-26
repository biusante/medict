<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
require_once(dirname(__DIR__) . "/Medict.php");

use Oeuvres\Kit\{Web};

/** Search form  */

list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = Web::par('an1', $an_min);
$an2 = Web::par('an2', $an_max);
$q = Web::par('q', 'a');
$t = Web::par('t', '');

?>
<div id="medict">
    <div id="col1">
        <form name="medict" class="recherche" autocomplete="off">
            <div>
                <div>Rechercher un terme dans les vedettes</div>
                <input name="q" id="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" type="text"  autocomplete="off"/>
            </div>
            <div class="bislide">
                <div>Limiter la recherche à une période </div>

                <input name="an1" step="1" value="<?= $an1 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <input name="an2" step="1" value="<?= $an2 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <input type="hidden" name="t" value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"/>
                <div class="values"></div>
            </div>
            <button type="submit">Go</button>
        </form>
        <nav id="index" class="data"  data-url="data/index">
            Termes
        </nav>
    </div>
    <div id="col2">
        <div class="pannel entrees">
            <header>Entrées</header>
            <nav id="entrees"  class="data" data-url="data/entrees">
            
            </nav>
        </div>
        <div class="pannel sugg">
            <header>Suggestions</header>
            <nav id="sugg"  class="data" data-url="data/sugg">
            
            </nav>
        </div>
    </div>
    <div id="col3">
        <header id="medica">
            <a id="medica-prev" class="entree"> </a>
            <a id="medica-ext" target="_blank"></a>
            <a id="medica-next" class="entree"> </a>
        </header>
        <div>
            <div id="viewcont">
                <img id="image"/>
            </div>
        </div>
    </div>
</div>
