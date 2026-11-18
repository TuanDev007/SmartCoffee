<?php

declare(strict_types=1);

/*
 * Điểm vào duy nhất của API. Mọi request /api/... được .htaccess chuyển về đây.
 */

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Router;

define('BASE_PATH', dirname(__DIR__));

// Tự nạp class theo namespace App\... → backend/app/...
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$config = App\Core\Config::load(BASE_PATH . '/config');
date_default_timezone_set($config['timezone']);
ErrorHandler::register((bool) $config['debug']);

$router = new Router();
require BASE_PATH . '/routes/api.php';

$router->dispatch(Request::fromGlobals());
