<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    /**
     * Hien thi View voi layout chung cua he thong.
     *
     * @param string $view Duong dan View tinh tu app/Views/
     * @param array<string, mixed> $data Du lieu truyen vao View
     */
    protected function view(string $view, array $data = []): void
    {
        $viewsRoot = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views');

        if ($viewsRoot === false) {
            throw new RuntimeException('Khong tim thay thu muc Views.');
        }

        $viewFile = $viewsRoot
            . DIRECTORY_SEPARATOR
            . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $view);

        $resolvedView = realpath($viewFile);

        if (
            $resolvedView === false
            || !str_starts_with(
                $resolvedView,
                $viewsRoot . DIRECTORY_SEPARATOR
            )
            || !is_file($resolvedView)
        ) {
            http_response_code(404);
            throw new RuntimeException('Khong tim thay giao dien: ' . $view);
        }

        extract($data, EXTR_SKIP);

        require $viewsRoot . DIRECTORY_SEPARATOR . 'layouts'
            . DIRECTORY_SEPARATOR . 'header.php';

        require $viewsRoot . DIRECTORY_SEPARATOR . 'layouts'
            . DIRECTORY_SEPARATOR . 'sidebar.php';
        ?>

        <main class="main-content">
            <?php require $resolvedView; ?>
        </main>

        <?php
        require $viewsRoot . DIRECTORY_SEPARATOR . 'layouts'
            . DIRECTORY_SEPARATOR . 'footer.php';
    }

    /**
     * Chuyen huong den URL noi bo.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
