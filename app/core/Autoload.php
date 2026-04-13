<?php
declare(strict_types=1);

namespace core;

class Autoload
{
	private static array $prefixes = [
		'core\\' => 'app/core/',
		'controller\\' => 'app/controller/',
		'model\\' => 'app/model/'
	];

	public static function register(): void
	{
		spl_autoload_register(function (string $class): void {
			foreach (self::$prefixes as $prefix => $baseDir) {
				$len = strlen($prefix);

				if (strncmp($prefix, $class, $len) !== 0) {
					continue;
				}

				$relativeClass = substr($class, $len);
				$file = __DIR__ . '/../../' . $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

				if (file_exists($file)) {
					require_once $file;
					return;
				}
			}
		});
	}
}
