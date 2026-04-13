<?php
declare(strict_types=1);
namespace core;

use core\Controller;

class Router
{
	private static array $routes = [];

	public static function get(string $path, string $action): void
	{
		self::$routes['GET'][$path] = $action;
	}

	public static function post(string $path, string $action): void
	{
		self::$routes['POST'][$path] = $action;
	}

	public static function dispatch(string $method, string $path): void
	{
		if (isset(self::$routes[$method][$path])) {
			$route = self::$routes[$method][$path];
			$parts = explode('@', $route);
			$controllerName = $parts[0];
			$actionName = $parts[1];
			$controller = new $controllerName();
			if ($controller instanceof Controller) {
				$controller->$actionName();
			} else {
				http_response_code(500);
				header('Location: /500');
			}
		} else {
			http_response_code(404);
			header('Location: /404');
		}
	}
}
