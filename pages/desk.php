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
        <form name="medict" class="recherche scrollable" autocomplete="off">
            <div>
                <div>Rechercher un terme dans les vedettes</div>
                <input name="q" id="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" type="text"  autocomplete="off"/>
            </div>
            <div class="bislide">
                <div>Limiter la recherche à une période</div>

                <input name="an1" step="1" value="<?= $an1 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <input name="an2" step="1" value="<?= $an2 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <input type="hidden" name="t" value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"/>
                <div class="values"></div>
            </div>
            <div title="Cliquer pour accéder à la liste des titres à sélectionner" id="titres_open">Liste des titres</div>
            <div id="titres_modal" class="modal">
                <span class="close">×</span>
                <div id="titres_flex">
                    <header>
                        <input class="titre_check" id="coteAll" type="checkbox"/>
                        <label for="coteAll" id="coteAllCheck">Tout cocher</label>
                        <label for="coteAll" id="coteAllUncheck">Tout décocher</label>
                    </header>
                    <?php require(__DIR__.'/titres.php') ?>
                </div>
            </div>
            <!--
            <div>
                <div>Limiter la recherche à un ou plusieurs titres</div>
                <input placeholder="Expéditeur(s)" type="text" class="multiple" data-url="data/titres" id="titres" data-name="titres"/>
            </div>
            -->
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
