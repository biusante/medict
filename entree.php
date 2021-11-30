<?php 
include_once(dirname(__FILE__)."/medict.php" );
$t = false;
if (isset($_REQUEST['t'])) $t = $_REQUEST['t'];
list($min, $max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an_min = $min;
if (isset($_REQUEST['an_min'])) $an_min=$_REQUEST['an_min'];
if ($an_min <= $min) $an_min=null;
if ($an_min >= $max) $an_min=null;
$an_max = $max;
if (isset($_REQUEST['an_max'])) $an_max = $_REQUEST['an_max'];
if ($an_max >= $max) $an_max = null;
if ($an_max <= $min) $an_max = null;

?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Nomenclature</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
    <link rel="icon" href="//u-paris.fr/wp-content/uploads/2019/04/Universite_Paris_Favicon.png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400&amp;display=swap"> 
    <link rel="stylesheet" href="theme/medict.css" />
  </head>
  <body class="refs">
    <?php
if ($t) {
  $sql = "SELECT dico_entree FROM dico_index WHERE terme_sort = ? ";
  $pars = array($t);
  if ($an_min !== null){
    $pars[] = $an_min;
    $sql .= " AND annee_titre >= ?";
  }
  if ($an_max !== null) {
    $pars[] = $an_max;
    $sql .= " AND annee_titre <= ?";
  }

  // $sql .= " ORDER BY mot.annee AND " 
  $motQ = Medict::$pdo->prepare($sql);
  $motQ->execute($pars);
  $entreeQ = Medict::$pdo->prepare("SELECT * FROM dico_entree WHERE id = ?");
  while ($row = $motQ->fetch(PDO::FETCH_ASSOC)) {
    $entreeQ->execute(array($row['dico_entree']));
    $entree = $entreeQ->fetch(PDO::FETCH_ASSOC);
    $url = 'https://www.biusante.parisdescartes.fr/histoire/medica/resultats/index.php?do=page&amp;cote=' . $entree['cote_volume'] . '&amp;p=' . $entree['url'];
    echo '<div class="entree">';
    echo '<a class="entree" target="facs" href="' . $url . '">';
    echo '<b>'. $entree['vedette'] .'</b>.';
    echo ' <i>'.$entree['nom_volume'].'</i> (' . $entree['annee_volume'] . ', ';
    if ($entree['page2'] != null) echo "pps. " . $entree['page'] . '-' . $entree['page2'];
    else echo "p. " . $entree['page'];
    echo ")</a>\n";
    echo "</div>\n";

  }
}
    ?>
    <p> </p>
    <script>
let matches = document.querySelectorAll("a.facs");
for (let i = 0, max = matches.length; i < max; i++) {
  let el = matches[i];
  el.addEventListener("click", function() {
    if (document.lastFacs) {
      document.lastFacs.classList.remove('active');
    }
    if (this.classList.contains("active")) {
      this.classList.remove('active');
      document.lastFacs = null;
    }
    else {
      this.classList.add('active');
      document.lastFacs = this;
    }
  }, false);
}
    </script>
  </body>
</html>
