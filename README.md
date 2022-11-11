# BIU Santé / Médica / Métadictionnaire : application web

## Installation rapide

1. Charger une base de données MySQL, cf. entrepôt [medict_sql](https://github.com/biusante/medict_sql#readme)
2. Récupérer la dernière version de l’aplication dans un dossier servi par Apache
~~~~
/var/www/html$ git clone https://github.com/biusante/medict.git
~~~~
3. Paramétrage (connexion MySQL)
~~~~
/var/www/html$ cd medict
/var/www/html/medict$ cp _pars.php pars.php
/var/www/html/medict$ vi pars.php
~~~~
4. http://localhost/medict

## Requis

Un serveur PHP/MySQL installé.

* Module Apache
  * mod_rewrite — pour routage des url
* Modules PHP
  * intl — pour normalisation du grec, [Normalizer](https://www.php.net/manual/fr/class.normalizer.php)
  * mbstring — traitement de chaînes unicode
  * pdo_mysql — connexion à la base de données

## Arbre des fichiers

L’application est structurée par un framework très léger (routage, templettage, localisation, loggage…), dédié à la publication XML/TEI ([Teinte](https://github.com/oeuvres/teinte/tree/master/php)). Les librairies existantes ne sont pas conçues pour répondre aux exigences de la publication académique,
notamment sur le routage, restreint à des blogs ou des applis web.  
Pas de dépendances ou de paquets à importer, le nécessaire est posé et réduit au strict minimum.

* [pars.php](pars.php) — MODIFIABLE, fichier obligatoire à créer avec les paramètre de connexion et des chemins, sur le modèle de [_pars.php](_pars.php).
* [index.php](index.php) — MODIFIABLE, chaîne de routage
* [tmpl_doc.php](tmpl_doc.php) — MODIFIABLE, la templette, essentiellement le bureau à l’accueil pour l’instant
* [theme/](theme/) — MODIFIABLE, ressources statiques spécifiques du site (css, js, images…)
* [data/](data/) — MODIFIABLE, générateurs de contenus MySQL insérés par l’interface, hors template
* [pages/](pages/) — MODIFIABLE, contiendra les pages statiques à ajouter au site dans le template, servies par le routage
* [doc/](doc/) — MODIFIABLE, des documents qui ont servi au développement
* [Medict.php](Medict.php) — classe partagée par les générateurs de contenus : connexion MySQL, imports, méthodes partagées…
* [php/](php/) — classes de la librairie Teinte
* [vendor/](vendor/) — librairies javascript
* [.htaccess](.htaccess) — redirige tout vers [index.php](index.php)
* .gitignore, .gitattributes — des fichiers nécessaire à git 

