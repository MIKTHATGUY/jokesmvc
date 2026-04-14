<?php
declare(strict_types=1);

namespace core;

use controller\ErrorController;

class Router
{
	private static array $routes = [];
	private static array $params = [];

	public static function get(string $path, string $action): void
	{
		self::$routes['GET'][$path] = $action;
	}

	public static function post(string $path, string $action): void
	{
		self::$routes['POST'][$path] = $action;
	}

	public static function dispatch(): void
	{
		$method = $_SERVER['REQUEST_METHOD'] ?: 'GET';
		$path = parse_url($_SERVER['REQUEST_URI'] ?: '/', PHP_URL_PATH) ?: '/';
		$path = filter_var($path, FILTER_SANITIZE_URL) ?: '/';
		$path = rtrim($path, '/') ?: '/';

		parse_str($_SERVER['QUERY_STRING'] ?? '', self::$params);

		if (!isset(self::$routes[$method][$path])) {
			self::abort(404);
		}

		[$controllerClass, $actionName] = explode('@', self::$routes[$method][$path]);
		$controller = new $controllerClass();

		if (!$controller instanceof Controller) {
			self::abort(500, 'Controller must extend core\Controller.');
		}

		$controller->$actionName();

		if (ob_get_level() > 0) {
			ob_end_flush();
		}
	}

	public static function getParams(): array
	{
		return self::$params;
	}

	public static function getParam(string $key, mixed $default = null): mixed
	{
		return self::$params[$key] ?? $default;
	}

	public static function redirect(string $path): void
	{
		header('Location: ' . $path);
		exit;
	}

	public static function abort(int $code = 500, ?string $message = null): void
	{
		http_response_code($code);
		$errorController = new ErrorController();
		$action = 'index' . $code;

		if (ob_get_level() > 0) {
			ob_clean();
		}

		if (method_exists($errorController, $action)) {
			$errorController->$action();
			exit;
		}

		// Fallback if the view file doesn't exist
		echo "<h1>Error {$code}</h1>";
		echo '<p>Something went very wrong.</p>';
		if ($message) {
			echo '<pre>' . htmlspecialchars($message) . '</pre>';
		}
		exit;
	}
}
