<?php 
include_once(dirname(__FILE__)."/medict.php" );
$q = 'a';
if (isset($_REQUEST['q'])) $q = $_REQUEST['q'];
if (!$q) $q = 'a';
$pdo = Medict::pdo();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
    <title>Nomenclature</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
    <link rel="icon" href="//u-paris.fr/wp-content/uploads/2019/04/Universite_Paris_Favicon.png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,300&amp;display=swap"> 
    <link rel="stylesheet" href="theme/medict.css" />
  </head>
  <body class="orth">
    <?php
$starttime = microtime(true);
$sql = "SELECT key, small FROM orth WHERE key LIKE ? GROUP BY key ORDER BY sort, label LIMIT 1000";
$query = $pdo->prepare($sql);
$q = Medict::sortable($q).'%';
$query->execute( array($q));
while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
  echo '<a class="orth" target="refs" href="refs.php?q='.$row['key'].'">'.$row['small'].'</a>',"\n";
}
echo '<small>',number_format(microtime(true) - $starttime, 3),' s.</small>';
    ?>
        <script>
let matches = document.querySelectorAll("a.orth");
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
