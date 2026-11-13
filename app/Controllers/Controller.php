<?php
declare(strict_types=1);

abstract class Controller
{
    protected function render(string $view, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            require __DIR__ . '/../Views/' . $view . '.php';
        }
    }
}
