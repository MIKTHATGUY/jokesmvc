<?php
declare(strict_types=1);

namespace core;

class Config
{
    private static array $settings = [];

    public static function load(string $file = __DIR__ . '/../../.env.ini'): void
    {
        if (!file_exists($file)) {
			throw new \Exception('Configuration file not found.');
		}
		self::$settings = parse_ini_file($file, true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = self::$settings;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
			}
			$value = $value[$k];
        }

        return $value;
    }
}
