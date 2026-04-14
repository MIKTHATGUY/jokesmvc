<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core/Autoload.php';
\core\Autoload::register();

use controller\JokesController;
use core\Auth;
use core\Config;
use core\Router;

Auth::init();
Config::load();
\core\InPageLogger::init();

Router::get('/', JokesController::class . '@indexHome');
Router::get('/all-jokes', JokesController::class . '@indexAllJokes');
Router::dispatch();
