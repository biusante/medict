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
  </head>
  <body>
    <header id="header">
      <div id="main-logo-container">
        <span class="logo-img-helper"></span>
        <a href="https://u-paris.fr/"><img src="//www.biusante.parisdescartes.fr/histoire/medica/assets/images/Universite_Paris_logo_horizontal.jpg"></a>
      </div>
    </header>
    <main>
      <div id="medict">
        <nav id="col1">
          <form name="toorth" action="orth.php" target="orth">
            <input name="q" oninput="this.form.submit()" autocomplete="off"/>
          </form>
          <iframe name="orth" id="orth" src="orth.php">
          </iframe>
        </nav>
        <nav id="col2">
          <iframe name="refs" id="refs"  src="refs.php">
          </iframe>
        
        </nav>
        <!-- 
        <nav id="col3">
          <iframe name="bibl" id="bibl"  src="bibl.php">
          </iframe>
        </nav>
        -->
        <nav id="col4">
          <iframe name="facs" id="facs" src="facs.php">
          </iframe>
        </nav>
      </div>
    </main>
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
  </body>
</html>
