<?php
/**
 * ==========================================================
 * PORTAL DKK
 * Core Loader
 * ==========================================================
 */

if (!defined('PORTAL_START')) {
    define('PORTAL_START', microtime(true));
}

/*
|--------------------------------------------------------------------------
| Load Configuration
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';

/*
|--------------------------------------------------------------------------
| Load Helpers
|--------------------------------------------------------------------------
*/

if (is_file(dirname(__DIR__).'/Helpers/functions.php')) require_once dirname(__DIR__).'/Helpers/functions.php';
if (is_file(dirname(__DIR__).'/Helpers/format.php'))    require_once dirname(__DIR__).'/Helpers/format.php';
if (is_file(dirname(__DIR__).'/Helpers/url.php'))       require_once dirname(__DIR__).'/Helpers/url.php';
if (is_file(dirname(__DIR__).'/Helpers/response.php'))  require_once dirname(__DIR__).'/Helpers/response.php';
if (is_file(dirname(__DIR__).'/Services/LauncherService.php')) require_once dirname(__DIR__)."/Services/LauncherService.php";

/*
|--------------------------------------------------------------------------
| Load Core
|--------------------------------------------------------------------------
*/

foreach (['Database.php','Session.php','Auth.php','Permission.php','Activity.php','Notification.php','Module.php'] as $__f) {
    $__p = __DIR__ . '/' . $__f;
    if (is_file($__p)) require_once $__p;
    // fallback ke App/Core jika tidak ada di config/
    $__p2 = dirname(__DIR__) . '/App/Core/' . $__f;
    if (!is_file(__DIR__.'/'.$__f) && is_file($__p2)) require_once $__p2;
}

/*
|--------------------------------------------------------------------------
| Initialize
|--------------------------------------------------------------------------
*/

Database::boot();

Session::boot();

Auth::boot();

Module::boot();