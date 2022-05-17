<?php 

include_once(dirname(__FILE__)."/Medict.php" );

use Oeuvres\Kit\Web;

/** Reception of a corpus selection */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // load available dico ids from base
    $coteQ = Medict::$pdo->prepare("SELECT cote FROM dico_titre");
    $coteQ->execute();
    $result = $coteQ->fetchAll(PDO::FETCH_COLUMN, 0);
    $dico_titre = Web::pars('dico_titre');
    print_r($dico_titre);
}
?>
<!DOCTYPE html>
<html>
  <head>
    <?php require(dirname(__FILE__) . "/theme/head.php"); ?>
    <title>Titres</title>
  </head>
  <body>
    <form method="post">
      <div id="dico_titre">
        <h1>Dictionnaires sélectionnés</h1>
        <div  class="checkall">
          <label>
            <input id="dico_checkall" type="checkbox" checked="checked"/>
            <span>Tout décocher</span>
          </label>
      </div>
    <?php
$sql = "SELECT * FROM dico_titre ";
$titreQ = Medict::$pdo->prepare($sql);
$titreQ->execute(array());
while ($row = $titreQ->fetch(PDO::FETCH_ASSOC)) {
    echo titre($row);
}
    ?>
        <div>
          <button class="submit" type="submit">Enregistrer</button>
        </div>
      </div>
    </form>
    <script src="theme/dico_titre.js">//</script>
  </body>
</html>
<?php
function titre($row)
{
    $checked = true;
    $div = '';
    $div .= '  <label class="dico_label';
    if ($checked) $div .= ' checked';
    $div .= '">'."\n";
    $div .= '    <input class="dico_check" type="checkbox" name="dico_titre" value="' . $row['cote'] . '"';
    if ($checked) $div .= ' checked="checked"';
    $div .= '/>'."\n";
    $div .= $row['nom'];
    $div .= '      (';
    if ($row['ed']) $div .= $row['ed'] . ' ';
    $div .= $row['annee'];
    if ($row['an_max']) $div .= '-' . $row['an_max'];
    $div .=  ")\n";
    $div .= '  </label>'."\n";
    return $div;
}

?>