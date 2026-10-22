<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Router
{
    public function dispatch(
        array $routes,
        string $defaultRoute = 'dashboard'
    ): void {
        $page = $_GET['page'] ?? $defaultRoute;

        if (!is_string($page) || !isset($routes[$page])) {
            http_response_code(404);
            $pageTitle = 'Không tìm thấy trang';

            require dirname(__DIR__) . '/Views/layouts/header.php';
            require dirname(__DIR__) . '/Views/layouts/sidebar.php';
            ?>
            <main class="main-content">
                <section class="page">
                    <h1>404 - Không tìm thấy trang</h1>
                    <p>Trang bạn yêu cầu không tồn tại.</p>
                </section>
            </main>
            <?php
            require dirname(__DIR__) . '/Views/layouts/footer.php';
            return;
        }

        $route = $routes[$page];

        // Route MVC: Controller + Action
        if (isset($route['controller'], $route['action'])) {
            $controllerClass = $route['controller'];
            $action = $route['action'];

            if (
                !is_string($controllerClass)
                || !str_starts_with($controllerClass, 'App\\Controllers\\')
                || !class_exists($controllerClass)
            ) {
                throw new RuntimeException(
                    'Controller khong ton tai hoac khong hop le.'
                );
            }

            if (!is_string($action)) {
                throw new RuntimeException('Action khong hop le.');
            }

            $controller = new $controllerClass();

            if (!is_callable([$controller, $action])) {
                throw new RuntimeException(
                    'Action khong ton tai hoac khong the goi.'
                );
            }

            $controller->{$action}();
            return;
        }

        // Route cũ: giữ tương thích với các View chưa có Controller
        if (isset($route['view']) && is_string($route['view'])) {
            $pageTitle = isset($route['title']) && is_string($route['title'])
                ? $route['title']
                : 'Hệ thống quản lý bán hàng';

            $viewsRoot = realpath(dirname(__DIR__) . '/Views');
            $viewFile = $viewsRoot === false
                ? false
                : realpath(
                    $viewsRoot . DIRECTORY_SEPARATOR
                    . str_replace(
                        ['/', '\\'],
                        DIRECTORY_SEPARATOR,
                        $route['view']
                    )
                );

            if (
                $viewsRoot === false
                || $viewFile === false
                || !is_file($viewFile)
                || !str_starts_with(
                    $viewFile,
                    $viewsRoot . DIRECTORY_SEPARATOR
                )
            ) {
                http_response_code(404);
                echo '404 - Khong tim thay giao dien.';
                return;
            }

            require $viewsRoot . '/layouts/header.php';
            require $viewsRoot . '/layouts/sidebar.php';
            ?>
            <main class="main-content">
                <?php require $viewFile; ?>
            </main>
            <?php
            require $viewsRoot . '/layouts/footer.php';
            return;
        }

        throw new RuntimeException(
            'Route "' . $page . '" chua co controller/action hoac view hop le.'
        );
    }
}
