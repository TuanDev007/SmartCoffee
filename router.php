<?php
declare(strict_types=1);

// Development router for php -S with public as the document root.
$root = realpath(__DIR__ . '/public');
$path = rawurldecode(explode('?', $_SERVER['REQUEST_URI'], 2)[0]);
$file = str_contains($path, "\0") ? false : realpath($root . $path);
if ($file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file)
    && !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['php', 'htaccess'], true)) {
    return false;
}
require __DIR__ . '/public/index.php';
