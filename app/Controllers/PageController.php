<?php
declare(strict_types=1);

final class PageController extends Controller
{
    public function __construct(private PageCatalog $pages) {}

    public function show(string $path): void
    {
        $view = $this->pages->find($path);
        if ($view === null) {
            $this->render('brunivo/404', 404);
            return;
        }
        $this->render($view);
    }
}
