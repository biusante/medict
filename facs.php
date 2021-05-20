<?php 
include_once(dirname(__FILE__)."/medict.php" );
$pdo = Medict::pdo();

$pb = '';
if (isset($_REQUEST['pb'])) $pb = $_REQUEST['pb'];

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
  <body class="facs">

    <?php
while ($pb) {
  $pbQ = $pdo->prepare("SELECT * FROM pb, volume WHERE pb.id = ? AND pb.volume = volume.id");
  $pbQ->execute(array($pb));
  $pbRow = $pbQ->fetch(PDO::FETCH_ASSOC);
  if (!$pbRow) break;
  // preg_match('@(^.*/)(\d+).jpg@', $facs, $matches);
  // if ($matches[2] - 1 )
  
  echo '<h1 class="facs">',$pbRow['year'],', ',$pbRow['label'], ', p. ',$pbRow['n'],'</h1>',"\n";

  $navQ = $pdo->prepare("SELECT * FROM pb WHERE id = ? AND volume = ?");
  $navQ->execute(array($pb - 1, $pbRow['volume']));
  $navRow = $navQ->fetch(PDO::FETCH_ASSOC);
  if ($navRow) {
    echo '<a class="facsprev" href="?pb='.($pb - 1).'" title="page précédente">◀</a>',"\n";
  }
  $navQ->execute(array($pb + 1, $pbRow['volume']));
  $navRow = $navQ->fetch(PDO::FETCH_ASSOC);
  if ($navRow) {
    echo '<a class="facsnext" href="?pb='.($pb + 1).'" title="page suivante">▶</a>',"\n";
  }

  echo '<img class="facs" src="'.$pbRow['facs'].'"/>',"\n";
  break;
}
    ?>
  </body>
</html>
