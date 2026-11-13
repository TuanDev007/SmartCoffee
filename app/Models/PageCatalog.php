<?php
declare(strict_types=1);

final class PageCatalog
{
    public function find(string $path): ?string
    {
        if (in_array($path, ['/', '/index.php', '/index.html', '/Your-Cafe-3D/Your-Cafe-3D/site/', '/Your-Cafe-3D/Your-Cafe-3D/site/index.html'], true)) {
            return 'intro/index';
        }
        if ($path === '/brunivo' || $path === '/brunivo/') {
            return 'brunivo/index';
        }
        if (!preg_match('~^/(brunivo/(?:[a-z0-9-]+/)*[a-z0-9-]+)\.html$~D', $path, $matches)) {
            return null;
        }
        return is_file(__DIR__ . '/../Views/' . $matches[1] . '.php') ? $matches[1] : null;
    }
}
