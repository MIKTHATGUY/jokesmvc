<?php
declare(strict_types=1);
namespace core;

class Autoload
{
	private static array $aliases = [];

	public static function register(): void
	{
		self::$aliases = require '../config/aliases.php';

		spl_autoload_register(function (string $class): void {
			foreach (self::$aliases as $alias => $baseDir) {
				$len = strlen($alias);
				if (strncmp($alias, $class, $len) !== 0) {
					continue;
				}

				$relativeClass = substr($class, $len);
				$file = '../' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

				if (file_exists($file)) {
					require_once $file;
				}
				return;
			}
		});
	}
}
