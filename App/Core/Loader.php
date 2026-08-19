<?php

define('ROOT_PATH', dirname(__DIR__, 2));

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Permission.php';
require_once __DIR__ . '/Module.php';
require_once dirname(__DIR__)."/Services/LauncherService.php";

Database::boot();
Session::boot();
Auth::boot();

if (class_exists('Module')) {
    Module::boot();
}