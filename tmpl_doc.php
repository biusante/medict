<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
/** Template */

declare(strict_types=1);

require_once(__DIR__ . "/Medict.php");

use Oeuvres\Kit\{Route};


$page = Route::$url_parts[0];
$body_class = $page;

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title><?= Route::title('Métadictionnaires — Dictionnaires Medica — BIU Santé, Paris') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
    <link rel="icon" href="//u-paris.fr/wp-content/uploads/2019/04/Universite_Paris_Favicon.png" sizes="32x32">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.10.5/viewer.min.css" rel="stylesheet" />
    

    <!-- Polices -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&amp;subset=latin,latin-ext" />
    <!-- Feuilles de styles -->
    <link rel="stylesheet" href="https://www.biusante.parisdescartes.fr/ressources/css/up-font-definitions.css?2.3.8" />
    <link rel="stylesheet" href="https://www.biusante.parisdescartes.fr/ressources/css/style.css?2.3.8" />
    <link rel="stylesheet" href="<?= Route::app_href() ?>vendor/split.css" />
    <link rel="stylesheet" href="<?= Route::app_href() ?>theme/medict.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
    <!-- jQuery (for the Medica menu)  -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
</head>

<body class="<?= $body_class ?>">
    <div id="page">

        <header id="tete">
            <div id="header-main-content">
                <div id="main-logo-container">
                    <span class="logo-img-helper"></span>
                    <a href="https://u-paris.fr/"><img src="https://www.biusante.parisdescartes.fr/histoire/medica/assets/images/UniversiteParisCite_logo_horizontal_couleur_RVB.png" alt=""></a>
                </div>
                <div id="menu-container">
                    <div id="site-name">
                        <a href="https://u-paris.fr/bibliotheques">Bibliothèques d'Université Paris Cité</a>
                    </div>
                    <div id="menu-icon" class="toggle-responsive-menu">
                        <span class="icon-menu"></span>
                    </div>
                    <div id="menu-niveau1-conteneur">
                        <div id="close-menu-icon" class="toggle-responsive-menu"><span class="icon-cancel"></span></div>
                        <ol>
                            <li class="section-histoire menu-item-niveau1 actif"><a href="https://www.biusante.parisdescartes.fr/histoire/index.php">Histoire de la santé</a>
                                <ol>
                                    <li class="menu-item-niveau2 actif"><a href="https://www.biusante.parisdescartes.fr/histoire/medica/index.php">Bibliothèque numérique <span class="nom-medica">Medica</span></a></li>
                                    <li class="menu-item-niveau2"><a href="https://www.biusante.parisdescartes.fr/histoire/images/index.php">Images et portraits</a></li>
                                    <li class="menu-item-niveau2"><a href="https://www.biusante.parisdescartes.fr/histoire/asclepiades/index.php">Asclépiades (thèses d&rsquo;histoire)</a></li>
                                    <li class="menu-item-niveau2"><a class="externe" href="http://www.calames.abes.fr" target="_blank">Manuscrits (Calames)</a></li>
                                    <li class="menu-item-niveau2"><a href="https://www.biusante.parisdescartes.fr/histoire/biographies/index.php">Base biographique</a></li>
                                    <li class="menu-item-niveau2"><a href="https://www.biusante.parisdescartes.fr/histoire/medicina/index.php">Medicina (bibliographie)</a></li>
                                    <li class="menu-item-niveau2"><a href="https://www.biusante.parisdescartes.fr/histoire/ebooks-on-demand.php">Numérisation à la demande (EOD)</a></li>
                                </ol>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            <div id="fil">
                <ol itemscope itemtype="http://schema.org/BreadcrumbList">
                    <li class="breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <a itemprop="item" href="https://www.biusante.parisdescartes.fr">
                            <span itemprop="name"><span class="breadcrumbs-root"></span>&nbsp;&nbsp;Accueil</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    <li class="breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <a itemprop="item" href="https://www.biusante.parisdescartes.fr/histoire/index.php">
                            <span itemprop="name">Histoire de la santé</span>
                        </a>
                        <meta itemprop="position" content="2" />
                    </li>
                    <li class="breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <a itemprop="item" href="/histoire/medica">
                            <span itemprop="name">Medica</span>
                        </a>
                        <meta itemprop="position" content="3" />
                    </li>
                    <li class="breadcrumbs-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <span itemprop="item" href=".">
                            <span itemprop="name">Dictionnaires</span>
                        </span>
                        <meta itemprop="position" content="4" />
                    </li>
                </ol>
            </div>
        </header>
        <!-- Content -->
        <div id="conteneur-ventre">
            <?= Route::main() ?>
        </div>


        <div id="pied">
            <div id="upper-footer">
                <div id="logos-institutionnels">
                    <span>
                        <a href="https://u-paris.fr/bibliotheques" target="_blank"> <img src="https://www.biusante.parisdescartes.fr/histoire/medica/assets/images/MonogrammeUP_43px.jpg" alt="Monogramme Université Paris Cité"></a>
                    </span>
                    <span>
                        <img src="https://www.biusante.parisdescartes.fr/histoire/medica/assets/images/LogoIA_43px.jpg" alt="Logo Investissements d'avenir">
                    </span>
                </div>
                <div id="liens-utilitaires">
                    <a class="up-footer-button" href="https://www.biusante.parisdescartes.fr/infos/contacts/index.php">Contacts</a>
                    <a class="up-footer-button" href="https://www.biusante.parisdescartes.fr/mentions.php">Mentions légales</a>
                    <a class="up-footer-button" href="https://www.biusante.parisdescartes.fr/plan.php">Plan du site</a>
                </div>
                <span class="clearfix"></span>
            </div>
        </div>
    </div>
    <script src="<?= Route::app_href() ?>theme/biusante.js"></script>
    <script src="<?= Route::app_href() ?>vendor/viewer.js"></script>
    <script src="<?= Route::app_href() ?>vendor/split.js"></script>
    <script src="<?= Route::app_href() ?>theme/medict.js"></script>
</body>

</html>