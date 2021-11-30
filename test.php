<?php 
include_once(dirname(__FILE__)."/medict.php" );
echo "ATTR_SERVER_INFO=" . Medict::$pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "<br/>\n";
echo "ATTR_SERVER_VERSION=" . Medict::$pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "<br/>\n";