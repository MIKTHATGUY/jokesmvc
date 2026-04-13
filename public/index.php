<?php
declare(strict_types=1);
require_once '../app/core/Autoload.php';

use controller\ErrorController;
use core\Autoload;
use core\Router;

use controller\HomeController;

Autoload::register();

Router::get('/', HomeController::class . '@index');

Router::get('/404', ErrorController::class . '@notFound');
Router::get('/500', ErrorController::class . '@serverError');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
Router::dispatch($method, $path);
