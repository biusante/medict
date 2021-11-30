<?php
Medict::init();
class Medict
{
  /** SQL link */
  static public $pdo;
  /** Les paramètres */
  static public $pars;
  /** Cache l’expression régulière de hilite */
  static private $hire = array();

  public static function init()
  {
    self::$pars = include dirname(__FILE__).'/pars.php';
    self::$pdo =  new PDO(
      "mysql:host=" . self::$pars['host'] . ";port=" . self::$pars['port'] . ";dbname=" . self::$pars['dbname'],
      self::$pars['user'],
      self::$pars['pass'],
      array(
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        // if true : big queries need memory
        // if false : multiple queries arre not allowed
        // PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
      ),
    );
    // self::$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    mb_internal_encoding("UTF-8");
  }

  public static function hilite($query, $vedette)
  {
    if (!isset(self::$hire[$query])) {
      $regs = preg_split("@[ ,]+@u", trim($query));
      $regs = preg_replace(
        array(
          "@[aàâä]@ui",
          "@[æ]@ui",
          "@[cç]@ui",
          "@[eéèêë]@ui",
          "@[iîï]@ui",
          "@[oôö]@ui",
          "@[œ]@ui",
          "@[uûü]@ui",
          "@^.*$@ui",
        ),
        array(
          "[aàâä]",
          "ae",
          "[cç]",
          "[eéèêë]",
          "[iîï]",
          "[oôö]",
          "oe",
          "[uûü]",
          "@([^<>\p{L}])($0)@ui",
        ),
        $regs
      );
      self::$hire[$query] = $regs;
    }
    $regs = self::$hire[$query];
    $vedette = " " . $vedette;
    foreach ($regs as $re) {
      $vedette = preg_replace($re, "$1<mark>$2</mark>", $vedette);
    }
    return $vedette;
    /*
    return preg_replace_callback(
      $re,
      function ($matches) use ($terme_sort) {
        $test = Medict::sortable($matches[0]);
        if ($test == $terme_sort) return "<mark>".$matches[0]."</mark>";
        return $matches[0];
      },
      " ".$vedette
    );
    */
  }
  
  public static function sortable($utf8)
  {
    $utf8 = mb_strtolower($utf8, 'UTF-8');
    $tr = array(
      // '-' => '',
      '« ' => '"',
      ' »' => '"',
      '«' => '"',
      '»' => '"',
      'à' => 'a',
      'ä' => 'a',
      'â' => 'a',
      'ӕ' => 'ae',
      'é' => 'e',
      'è' => 'e',
      'ê' => 'e',
      'ë' => 'e',
      'î' => 'i',
      'ï' => 'i',
      'ô' => 'o',
      'ö' => 'o',
      'œ' => 'oe',
      'ü' => 'u',
      'û' => 'u',
      'ÿ' => 'y',
    );
    $sortable = strtr($utf8, $tr);
    // pb avec les accents, passera pas pour le grec
    // $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $utf8);
    return $sortable;
  }


}
?>
