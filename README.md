# BIU Santé / Médica / Métadictionnaire : application web

Cet entrepôt contient l’application web de publication du Métadictionnaire (données à charger ou produire avec [medict_sql](https://github.com/biusante/medict_sql#readme)). Par design, cette application essaiera de rester en lecture seule et ne produire aucune données, sauf besoins de l’équipe de recherche.

## Requis

Un serveur MySQL **5.7**. Attention, très gros problème de performances avec **MySQL 8** (requêtes en timeout), cf. https://dev.mysql.com/doc/refman/8.0/en/known-issues.html. Aucun contournement n’a encore été trouvé.

* Module Apache
  * mod_rewrite — pour routage des url
* Modules PHP
  * pdo_mysql — connexion à la base de données
  * mbstring — traitement de chaînes unicode
  * intl — pour normalisation du grec, [Normalizer](https://www.php.net/manual/fr/class.normalizer.php)


## Installation rapide

1. Charger une base de données MySQL avec [medict_sql](https://github.com/biusante/medict_sql#readme)
2. Récupérer la dernière version de l’aplication dans un dossier servi par Apache
```bash
# vérifier les droits sur le dossier html/ (.)
/var/www/html$ ls -alh
total 20K
drwxrwsr-x 2 root les_admins 4.0K Nov 13 12:42 .
# facultatif, donne les droits d’écriture au groupe sur les fichiers créés
/var/www/html$ umask 0002
# Apache n’a (pour l’instant) pas besoin d’écrire
# Ceci pourra évoluer selon les demandes scientifiques
# dernière version de l’appli
/var/www/html$ git clone https://github.com/biusante/medict.git
```
3. Paramétrage (connexion MySQL)
```bash
/var/www/html$ cd medict
/var/www/html/medict$ ls -alh
total 80K
drwxrwsr-x 9 moi_meme les_admins 4.0K Nov 13 12:54 .
drwxrwsr-x 3 root     les_admins 4.0K Nov 13 12:54 ..
drwxrwsr-x 8 moi_meme les_admins 4.0K Nov 13 12:54 .git
-rw-rw-r-- 1 moi_meme les_admins   55 Nov 13 12:54 .gitattributes
-rw-rw-r-- 1 moi_meme les_admins  127 Nov 13 12:54 .gitignore
-rw-rw-r-- 1 moi_meme les_admins  435 Nov 13 12:54 .htaccess
-rw-rw-r-- 1 moi_meme les_admins 8.5K Nov 13 12:54 Medict.php
-rw-rw-r-- 1 moi_meme les_admins 2.5K Nov 13 12:54 README.md
-rw-rw-r-- 1 moi_meme les_admins  191 Nov 13 12:54 _pars.php
drwxrwsr-x 2 moi_meme les_admins 4.0K Nov 13 12:54 data
drwxrwsr-x 2 moi_meme les_admins 4.0K Nov 13 12:54 doc
-rw-rw-r-- 1 moi_meme les_admins  893 Nov 13 12:54 index.php
drwxrwsr-x 2 moi_meme les_admins 4.0K Nov 13 12:54 pages
drwxrwsr-x 4 moi_meme les_admins 4.0K Nov 13 12:54 php
drwxrwsr-x 2 moi_meme les_admins 4.0K Nov 13 12:54 theme
-rw-rw-r-- 1 moi_meme les_admins 7.8K Nov 13 12:54 tmpl_doc.php
drwxrwsr-x 3 moi_meme les_admins 4.0K Nov 13 12:54 vendor
/var/www/html/medict$ cp _pars.php pars.php
/var/www/html/medict$ vi pars.php
```
4. http://localhost/medict


## Erreurs connues

Les erreurs suivantes ont été rencontrées lors de l’installation de l’application
sur un serveur Ubuntu 22.04 LTS vierge.

**http://localhost/medict HTTP ERROR 500**

Votre serveur refuse d’afficher les erreurs PHP, c’est la configuration par défaut en production
(ce qui est une bonne pratique). Le fichier [.htaccess](.htaccess) par défaut de l’application
force normalement l’affichage des messages d’erreur. Vérifier que la configuration de votre
serveur http (Apache) prend en compte les fichiers .htaccess. Assurez-vous que le dossier qui 
contient l’application a bien la commande `AllowOverride All`.

```apacheconf
# Ubuntu /etc/apache2/sites-available/000-default.conf
DocumentRoot /var/www/html
<Directory /var/www/html>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Ne pas oublier de redémarrer apache `sudo service apache2 restart`.

**Fatal error: Uncaught Exception: Fatal error: Uncaught Exception: Au moins une extension php manquante : … in …medict/Medict.php**

Extensions PHP requises qui ne sont pas installées sur le serveur.
```bash
Ubuntu 22.04$ sudo apt update
Ubuntu 22.04$ sudo apt install php-mysql php-mbstring php-intl
Ubuntu 22.04$ sudo service apache2 restart
```

**Fatal error: Uncaught Exception: Paramètres MySQL manquants (pars.php). Modèle : _pars.php in …medict/Medict.php**

L’application ne trouve pas son fichier de paramétrage attendu dans `./pars.php`.

**Fatal error: Uncaught Exception: pars.php, 5 paramètres manquants : host, port, base, user, pass in …medict/Medict.php**

1 ou plusieurs paramètre requis ne sont pas renseignés dans ./pars.php, cf. [_pars.php](_pars.php).

```php
return array(
  // serveur MySQL
  'host' => '127.0.0.1',
  // port MySQL
  'port' => '3306',
  // base du métadictionnaire créeé avec https://github.com/biusante/medict_sql
  'base' => 'medict', 
  // utilisateur avec 
  'user' => 'medict',
  // mot de passe de cet utilisateur 
  'pass' => ?????, 
);
```

**PDOException: SQLSTATE[HY000] [2002] Connection refused**

MySQL n’est pas démarré.

```bash
Ubuntu 22.04$ telnet 127.0.0.1 3306
Trying 127.0.0.1...
telnet: Unable to connect to remote host: Connection refused
Ubuntu 22.04$ sudo service mysql restart
Ubuntu 22.04$ telnet 127.0.0.1 3306
Trying 127.0.0.1...
Connected to 127.0.0.1.
# demande un mot de passe, arrêter avec Ctrl+C
```

**PDOException: SQLSTATE[HY000] [1044] Access denied for user …**

Ce serveur MySQL ne connaît pas l’utilisateur déclaré dans votre fichier pars.php. Cet utilisateur a besoin des seuls droits de SELECT (`GRANT SELECT ON medict.*`).

```bash
Ubuntu 22.04$ sudo mysql
mysql> CREATE USER 'medict'@'localhost' IDENTIFIED BY 'MotDePasseSuperSecret';
Query OK, 0 rows affected (0.02 sec)

mysql> GRANT SELECT ON medict.* TO 'medict'@'localhost';
Query OK, 0 rows affected (0.02 sec)
```

**404 Not Found (dans tous les cadres)**

Le module Apache mod_rewrite n’est probablement pas fonctionnel.

```bash
Ubuntu 22.04$ sudo a2enmod rewrite
Ubuntu 22.04$ sudo service apache2 restart
```

**[data/mots?q=a](http://localhost/medict/data/mots?q=a) PDOException: SQLSTATE[42000]: Syntax error or access violation: 1055 Expression #1 of SELECT list is not in GROUP BY clause and contains nonaggregated column…**

La base est sans doute mal chargée.

```bash
Ubuntu 22.04$ sudo mysql
mysql> source …medict_sql/data_sql/medict_dico_titre.sql;
mysql> source …medict_sql/data_sql/medict_dico_volume.sql;
mysql> source …medict_sql/data_sql/medict_dico_terme.sql;
mysql> source …medict_sql/data_sql/medict_dico_entree.sql;
mysql> source …medict_sql/data_sql/medict_dico_rel.sql;
```

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

