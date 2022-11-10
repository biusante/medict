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
![image](https://user-images.githubusercontent.com/5686231/201225285-fd59f3a1-9cc0-4948-94b1-2459d14a2a28.png)


