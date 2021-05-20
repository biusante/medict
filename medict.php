<?php 
class Medict
{
  /** SQLite link */
  static public $pdo;
  /** Database absolute path */
  static private $sqlfile;

  public static function pdo()
  {
    $dsn = "sqlite:".dirname(__FILE__).'/medict.sqlite';
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA temp_store = 2;");
    return $pdo;
  }
  
  public static function sortable($utf8)
  {
    $utf8 = mb_strtolower($utf8);
    $tr = array(
      '« ' => '"',
      ' »' => '"',
      '«' => '"',
      '»' => '"',
    );
    $utf8 = strtr($utf8, $tr);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $utf8);
    return $ascii;
  }

}
?>
