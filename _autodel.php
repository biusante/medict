<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ignore_user_abort(true);


$commands = array(
    "ls -alh",
    /* 
    // clone initial avec countournement erreur fatale
    // fatal: destination path '.' already exists and is not an empty directory.
    // "git clone https://github.com/biusante/medict.git . 2>&1", 
    "git init 2>&1",
    "git remote add origin https://github.com/biusante/medict.git 2>&1",
    // "git fetch 2>&1", // pull fonctionne
    "git pull 2>&1", // un warning
    "git checkout main -f 2>&1",
    */
    "git pull 2>&1", // un warning
    "ls -alh",

);
echo '<pre>Commands
';
foreach ($commands as $cmd) {
    echo $cmd."\n";
    $last_line = system($cmd, $retval);
}
echo '</pre>';
flush();

register_shutdown_function('unlink', __FILE__);
