<?php 
include_once(dirname(__FILE__)."/medict.php" );
$q = null;
if (isset($_REQUEST['q'])) $q = htmlspecialchars(trim($_REQUEST['q']));
list($min, $max) = Medict::$pdo->query("SELECT MIN(annee_titre), MAX(annee_titre) FROM dico_entree")->fetch();
$an_min = $min;
if (isset($_REQUEST['an_min'])) $an_min=$_REQUEST['an_min'];
if ($an_min <= $min) $an_min=null;
if ($an_min >= $max) $an_min=null;
$an_max = $max;
if (isset($_REQUEST['an_max'])) $an_max = $_REQUEST['an_max'];
if ($an_max >= $max) $an_max = null;
if ($an_max <= $min) $an_max = null;

/* utile ?
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,300&amp;display=swap"> 
*/
$limit = 100; // nombre maximal de vedettes affichées
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Nomenclature</title>
    <link rel="stylesheet" href="theme/medict.css" />
  </head>
  <body class="orth">

    <?php

Medict::hilite($q, "loop");

$starttime = microtime(true);
$sql = "SELECT terme, terme_sort, COUNT(*) AS compte, dico_entree FROM dico_index ";
$pars = array();
$where = array();
// affixe demandé
$hire = array();
if ($q) {
  // juste au début
  if ( mb_strlen($q) < 4) {
    $where[] = "terme_sort LIKE ?";
    $pars[] = $q.'%';
  }
  else {
    $where[] = "MATCH (terme) AGAINST (? IN BOOLEAN MODE)";
    if (mb_strpos($q, ' ') !== false) $pars[] = '+'.preg_replace('@\s+@ui', '* +', $q).'*';
    else $pars[] = $q . '*';
  }
  /*
  $q = trim(Medict::sortable($q));
  // split words ?
  $tokens = preg_split('@\s+@ui', $q);
  $first = true;
  $clause = "(";
  foreach($tokens as $tok) {
    $tok = trim($tok);
    if (!$tok) continue;
    if ($first) $first = false;
    else $clause .= " AND "; // OR, AND ?
    if (mb_strlen($tok) > 4) $tok = '%'.$tok;
    $tok .= '%';
    $pars[] = $tok;
    $clause .= "terme_sort LIKE ?";
  }
  $clause .= ")";
  $where[] = $clause;
  */

}
if ($an_min !== null) {
  $pars[] = $an_min;
  $where[] = "annee_titre >= ?";
}
if ($an_max !== null) {
  $pars[] = $an_max;
  $where[] = "annee_titre <= ?";
}
if (count($where) > 0) {
  $sql .= ' WHERE '.implode(' AND ', $where); 
}
$sql .= " GROUP BY terme_sort ORDER BY terme_sort LIMIT ".$limit;
// if (isset($_REQUEST['de']) )

$query = Medict::$pdo->prepare($sql);
$query->execute($pars);
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
  $queryString = "?t=". urlencode(utf8_encode($row['terme_sort']));
  if ($an_min) $queryString .= '&amp;an_min='.$an_min;
  if ($an_max) $queryString .= '&amp;an_max='.$an_max;
  // redonner les filtres
  foreach(array('annee1', 'annee2') as $name) {
    if (isset($_REQUEST[$name])) $queryString .= '&amp;' . $name . '=' . $_REQUEST[$name];
  }
  $title = htmlspecialchars($row['terme']);
  $value = Medict::hilite($q, $row['terme']);
  echo '<a class="terme" target="entree" href="entree.php'.$queryString.'" title="'.$title.'">'.$value.' <small>(',$row['compte'],')</small></a>',"\n";
}
echo '<small style="color: #ccc">',number_format(microtime(true) - $starttime, 3),' s.</small>';
    ?>
        <script>
let matches = document.querySelectorAll("body.orth a");
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
