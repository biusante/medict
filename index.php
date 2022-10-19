<?php
/**
 * This file is part of Medict https://github.com/biusante/medict
 * Copyright (c) 2021 Université Paris Cité / Bibliothèques / Histoire de la santé
 */

/** router */
declare(strict_types=1);

// Change this path if app is in another folder
$appdir = __DIR__ . "/";

// load the master class of the app
require_once($appdir . "Medict.php");

use Oeuvres\Kit\{Route};

// data servlets, no template
Route::get('/data/(.*)', $appdir . 'data/$1.php', null, null);

// register templates
Route::template($appdir . 'tmpl_doc.php', 'doc');
// welcome page
Route::get('/', $appdir . 'pages/desk.php');
// try if a php content is available
Route::get('/(.*)', $appdir . 'pages/$1.php'); 
// try if an html content is available
Route::get('/(.*)', $appdir . 'pages/$1.html');
// catch all
Route::route('/404', $appdir . 'pages/404.html');
// No Route has worked
echo "Bad routage, 404.";