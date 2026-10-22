<?php

declare(strict_types=1);

session_start();

date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once dirname(__DIR__) . '/app/Core/autoload.php';
require_once dirname(__DIR__) . '/app/Helpers/functions.php';

$routes = require dirname(__DIR__) . '/routes/web.php';

$router = new App\Core\Router();
$router->dispatch($routes, 'dashboard');
