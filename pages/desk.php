<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
require_once(dirname(__DIR__) . "/Medict.php");
use Oeuvres\Kit\{Web};
/** Search form  */

list($an_min, $an_max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an1 = $an_min;
if (isset($_REQUEST['an1'])) $an1 = $_REQUEST['an1'];
$an2 = $an_max;
if (isset($_REQUEST['an2'])) $an2 = $_REQUEST['an2'];
?>
<div id="medict">
    <div id="col1">
        <form name="medict" class="recherche">
            <div class="bislide">
                <div>Limiter la recherche à une période </div>

                <input name="an1" step="1" value="<?= $an1 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <input name="an2" step="1" value="<?= $an2 ?>" min="<?= $an_min ?>" max="<?= $an_max ?>" type="range"/>
                <div class="values"></div>
            </div>
            <button type="submit">Go</button>
            <div>
                <div>Rechercher un terme dans les vedettes</div>
                <input name="q" value="" type="text"  autocomplete="off"/>
            </div>
        </form>
        <nav id="index"  data-url="data/index">
            Termes
        </nav>
    </div>
    <div id="col2">
        <nav id="index"  data-url="data/index">
            Termes
        </nav>
    </div>
    <div id="col4">
        Image
    </div>
</div>
