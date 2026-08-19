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

require_once dirname(__DIR__,2).'/config/app.php';
require_once dirname(__DIR__,2).'/config/database.php';

/*
|--------------------------------------------------------------------------
| Load Helpers
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__).'/Helpers/functions.php';
require_once dirname(__DIR__).'/Helpers/format.php';
require_once dirname(__DIR__).'/Helpers/url.php';
require_once dirname(__DIR__).'/Helpers/response.php';
require_once dirname(__DIR__)."/Services/LauncherService.php";

/*
|--------------------------------------------------------------------------
| Load Core
|--------------------------------------------------------------------------
*/

require_once __DIR__.'/Database.php';
require_once __DIR__.'/Session.php';
require_once __DIR__.'/Auth.php';
require_once __DIR__.'/Permission.php';
require_once __DIR__.'/Activity.php';
require_once __DIR__.'/Notification.php';
require_once __DIR__.'/Module.php';

/*
|--------------------------------------------------------------------------
| Initialize
|--------------------------------------------------------------------------
*/

Database::boot();

Session::boot();

Auth::boot();

Module::boot();