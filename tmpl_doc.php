<?php

/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */
/** 
 * La “templette” de la page d’accueil.
 * 
 * Les contenus sont insérés par le routeur (Route) selon l’url demandée
 * 
 * Route::main() : le contenu principal à insérer 
 * Route::home_href() : lien relatif a ici
 * Route::title() : titre du 
 * 
 */

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
  <title><?= Route::title('Métadictionnaire — Dictionnaires Medica — BIU Santé, Paris') ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
  <link rel="icon" href="<?= Route::home_href() ?>theme/UP_favicon.png" sizes="32x32">

  <link href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.10.5/viewer.min.css" rel="stylesheet" />


  <!-- Polices -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&amp;subset=latin,latin-ext" />
  <!-- Feuilles de styles -->
  <link rel="stylesheet" href="<?= Route::home_href() ?>theme/biusante.css" />
  <link rel="stylesheet" href="<?= Route::home_href() ?>js/split.css" />
  <link rel="stylesheet" href="<?= Route::home_href() ?>theme/medict.css" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
</head>

<body class="desk">
  <div id="page">

    <header id="tete">
      <div id="header-main-content">
        <div id="main-logo-container">
          <span class="logo-img-helper"></span>
          <a href="https://u-paris.fr/"><img src="https://www.biusante.parisdescartes.fr/histoire/medica/assets/images/UniversiteParisCite_logo_horizontal_couleur_RVB.png" alt=""></a>
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
                <a itemprop="item" href="https://www.biusante.parisdescartes.fr/histoire/medica/">
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

        </div>
        <div id="tete-titre">
          <h1>Métadictionnaire médical multilingue</h1>
          <div class="liens">
            <a target="_blank" class="externe" href="https://www.biusante.parisdescartes.fr/histoire/medica/presentation-metadictionnaire.php">Présentation</a>
          </div>
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
    </header>
    <!-- Content -->
    <div id="conteneur-ventre">
      <?= Route::main() ?>
    </div>


    <div id="pied">
      <div id="upper-footer">
        <div id="logos-institutionnels">
          <span>Un projet de</span>
          <span>
            <a target="_blank"
            href="https://u-paris.fr/bibliotheques"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_UPC_48px.png" 
            alt="UP Cité"></a>
          </span>
          <!--
          <span>
            <img class="logo" alt="Logo Investissements d'avenir" src="<?= Route::home_href() ?>theme/logo_IA_48px.png">
          </span>
          -->
          <span>
            <a target="_blank" 
            href="https://lettres.sorbonne-universite.fr/"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_SUlettres_48px.png" 
            alt="Sorbonne"></a>
          </span>
          <span>
            <a target="_blank" 
            href="http://www.orient-mediterranee.com/spip.php?rubrique314"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_UMR8167_48px.png" 
            alt="UMR Orient & Méditerranée"></a>
          </span>
          <span>
            <a target="_blank" 
            href="https://www.iufrance.fr/"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_IUF_48px.png" 
            alt="IUF"></a>
          </span>
          <span>
            <a target="_blank" 
            href="https://www.atilf.fr/"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_ATILF_48px.png" 
            alt="ATILF"></a>
          </span>
          <span>
            <a target="_blank" 
            href="http://www.cnrs.fr/fr/page-daccueil"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_CNRS_48px.png" 
            alt="CNRS"></a>
          </span>
          financé par
          <span>
            <a target="_blank" 
            href="https://www.collexpersee.eu/projet/metadictionnaire-medical-multilingue/"><img class="logo" 
            src="<?= Route::home_href() ?>theme/logo_COLLEX_48px.png" 
            alt="CollEx"></a>
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
  <script src="<?= Route::home_href() ?>js/split.js"></script>
  <script src="<?= Route::home_href() ?>js/viewer.js"></script>
  <script src="<?= Route::home_href() ?>js/FastBitSet.js">//</script>
  <script src="<?= Route::home_href() ?>theme/medict.js"></script>
  <!-- jQuery (for the Medica menu) too heavy  -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
  <script src="<?= Route::home_href() ?>theme/biusante.js"></script>
</body>

</html>