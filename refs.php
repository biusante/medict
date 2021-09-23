<?php 
include_once(dirname(__FILE__)."/medict.php" );
$q = '';
if (isset($_REQUEST['q'])) $q = $_REQUEST['q'];
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
  <body class="refs">
    <?php
if ($q) {
  $sql = "SELECT label, pb, pb.* FROM orth, pb WHERE key = ? AND orth.pb = pb.id ORDER BY year, pb LIMIT 1000";
  // $sql = "SELECT orth.* FROM orth WHERE key = ? ORDER BY year, sort, label LIMIT 1000";
  $query = $pdo->prepare($sql);
  $query->execute(array($q));
  $volumeQuery = $pdo->prepare("SELECT * FROM volume WHERE id = ?");
  $volumeLast = -1;
  while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $volume = $row['volume'];
    if ($volume != $volumeLast) {
      if ($volumeLast != -1) echo '</section>',"\n";
      $volumeLast = $volume;
      $volumeQuery->execute(array($volume));
      $volumeRow = $volumeQuery->fetch();
      echo '<section class="volume">', "\n";
      echo '<div class="volume">'.$volumeRow['year'].', '.$volumeRow['label'].'</div>',"\n";
    }
    if (is_numeric($row['n'])) $row['n'] = 0 + $row['n'];
    echo '<a class="facs" target="facs" href="facs.php?pb='.$row['pb'].'">p. '.$row['n'].', '.$row['label'].'</a>',"\n";
  }
  if ($volumeLast != -1) echo '</section>',"\n";
}
    ?>
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
