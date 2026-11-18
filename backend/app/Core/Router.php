<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;

/**
 * Bảng định tuyến. Mỗi route khai báo kèm tùy chọn:
 *   'auth'  => bool         cần đăng nhập (mặc định true)
 *   'roles' => string[]     vai trò được gọi (mặc định: mọi vai trò đã đăng nhập)
 *   'csrf'  => bool         kiểm CSRF với POST/PATCH/DELETE (mặc định = auth)
 *
 * Thứ tự kiểm: đăng nhập → vai trò → CSRF → controller.
 */
final class Router
{
    /** @var list<array{method:string, pattern:string, regex:string, handler:array, options:array}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    public function patch(string $path, array $handler, array $options = []): void
    {
        $this->add('PATCH', $path, $handler, $options);
    }

    public function delete(string $path, array $handler, array $options = []): void
    {
        $this->add('DELETE', $path, $handler, $options);
    }

    private function add(string $method, string $path, array $handler, array $options): void
    {
        $auth = $options['auth'] ?? true;
        $options = [
            'auth'  => $auth,
            'roles' => $options['roles'] ?? null,
            'csrf'  => $options['csrf'] ?? $auth,
        ];
        // {id} chỉ nhận số nguyên dương
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[1-9][0-9]*)', $path) . '$#';
        $this->routes[] = compact('method', 'path', 'regex', 'handler', 'options');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method || !preg_match($route['regex'], $request->path, $m)) {
                continue;
            }
            $request->params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            $opt = $route['options'];
            if ($opt['auth']) {
                AuthMiddleware::handle();
            }
            if ($opt['roles'] !== null) {
                RoleMiddleware::handle($opt['roles']);
            }
            if ($opt['csrf'] && $request->isMutating()) {
                CsrfMiddleware::handle($request);
            }

            [$class, $action] = $route['handler'];
            (new $class())->$action($request);
            return;
        }

        // Sai đường dẫn hoặc sai method đều trả 404 (api-contract mục 1.3)
        throw HttpException::notFound('Không có địa chỉ API này.');
    }
}
