<?php 
include_once(dirname(__FILE__)."/medict.php" );
list($min, $max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an_min = $min;
if (isset($_REQUEST['an_min'])) $an_min = $_REQUEST['an_min'];
$an_max = $max;
if (isset($_REQUEST['an_max'])) $an_max = $_REQUEST['an_max'];
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Medict — Dictionnaires Medica — BIU Santé, Paris</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
    <link rel="icon" href="//u-paris.fr/wp-content/uploads/2019/04/Universite_Paris_Favicon.png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,300&amp;display=swap"> 
    <link rel="stylesheet" href="//www.biusante.parisdescartes.fr/ressources/css/up-font-definitions.css?2.3.1" />
    <link rel="stylesheet" href="//www.biusante.parisdescartes.fr/ressources/css/style.css?2.3.1" />
    <link rel="stylesheet" href="//www.biusante.parisdescartes.fr/histoire/medica/assets/js/highslide/highslide.css" />
    <link rel="stylesheet" href="//www.biusante.parisdescartes.fr/histoire/medica/assets/css/styles-medica.css?2.3.1" />
    <link rel="stylesheet" href="theme/medict.css" />
    <script type="text/javascript">
    </script>
  </head>
  <body>
    <?php
/*
    <header id="header">
      <div id="main-logo-container">
        <span class="logo-img-helper"></span>
        <a href="https://u-paris.fr/"><img src="//www.biusante.parisdescartes.fr/histoire/medica/assets/images/Universite_Paris_logo_horizontal.jpg"></a>
      </div>
    </header>
*/
    ?>
    <main>
      <div id="medict">
        <nav id="col1">
          <form name="aumot" action="terme.php" target="terme">
            <input name="q" class="sugg" oninput="this.form.submit()" autocomplete="off"/>
            <input class="an_range first" value="<?= $an_min ?>" min="<?= $min ?>" max="<?= $max ?>" type="range"/>
            <div class="an_minmax">
              <input class="an" name="an_min"  value="<?= $an_min ?>" size="2"/>
              —
              <input class="an" name="an_max" value="<?= $an_max ?>" size="2"/>
            </div>
            <input class="an_range second" value="<?= $an_max ?>" min="<?= $min ?>" max="<?= $max ?>" type="range"/>
          </form>
          <div class="index">
            <iframe name="terme" id="terme" src="terme.php">
            </iframe>
          </div>
        </nav>
        <nav id="col2">
          <iframe name="entree" id="entree"  src="entree.php">
          </iframe>
        
        </nav>
        <!-- 
        <nav id="col3">
          <iframe name="bibl" id="bibl"  src="bibl.php">
          </iframe>
        </nav>
        -->
        <nav id="col4">
          <iframe name="facs" id="facs" src="https://www.biusante.parisdescartes.fr/histoire/medica/dictionnaires.php#table246">
          </iframe>
        </nav>
      </div>
    </main>
    <?php
/*
    <footer id="footer">
    <div id="pied">
      <div id="upper-footer">
        <div id="logos-institutionnels">
          <span>
            <a href="" target="_blank"> <img src="//www.biusante.parisdescartes.fr/histoire/medica/assets/images/MonogrammeUP_43px.jpg" alt="Monogramme Université de Paris"></a>
          </span>
          <span>
            <img src="//www.biusante.parisdescartes.fr/histoire/medica/assets/images/LogoIA_43px.jpg" alt="Logo Investissements d'avenir">
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
    </footer>
*/
    ?>
    <script>
// bottom script
// Initialize Sliders
var sliders = document.getElementsByClassName("an_range");
if (sliders.length == 2) {
  sliders[0].oninput = function() {
    if (this.value > sliders[1].value) sliders[1].value = this.value;
    this.form['an_min'].value = sliders[0].value;
    this.form['an_max'].value = sliders[1].value;
  };
  sliders[0].onchange = function() { 
    this.form.submit();
    upEntree({'an_min':this.form['an_min'].value, 'an_max':this.form['an_max'].value })
  };
  sliders[1].oninput = function() {
    if (this.value < sliders[0].value) sliders[0].value = this.value;
    this.form['an_min'].value = sliders[0].value;
    this.form['an_max'].value = sliders[1].value;
  };
  sliders[1].onchange = function() {
    this.form.submit();
    upEntree({'an_min':this.form['an_min'].value, 'an_max':this.form['an_max'].value })
  };
}
function upEntree(hash) {
  let search = window.parent.frames['entree'].location.search;
  let pars = new URLSearchParams(search);
  for (var k in hash){
    pars.set(k, hash[k]);
  }
  window.parent.frames['entree'].location.search = pars.toString();

  // window.parent.frames['entree']

}
      </script>
  </body>
</html>
