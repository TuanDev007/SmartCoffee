<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Category;
use App\Models\Product;

/** GET /api/menu: thực đơn cho phục vụ, thu ngân, pha chế, manager (api-contract đợt 3; D-58). */
final class MenuController
{
    public function index(Request $request): void
    {
        $byCategory = [];
        foreach (Product::onMenu() as $p) {
            $byCategory[$p['category_id']][] = $p;
        }

        $menu = [];
        foreach (Category::active() as $c) {
            $products = array_map(static function (array $p): array {
                unset($p['category_id']);
                return $p;
            }, $byCategory[$c['id']] ?? []);
            $menu[] = ['id' => $c['id'], 'name' => $c['name'], 'products' => $products];
        }
        Response::json($menu);
    }
}
