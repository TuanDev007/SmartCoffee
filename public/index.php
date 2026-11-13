<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Models/PageCatalog.php';
require_once __DIR__ . '/../app/Controllers/Controller.php';
require_once __DIR__ . '/../app/Controllers/PageController.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}
$path = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
(new PageController(new PageCatalog()))->show($path);
