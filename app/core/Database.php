<?php
declare(strict_types=1);

namespace core;

use core\Config;
use PDO;
use PDOException;

class Database
{
	private static ?PDO $pdo = null;

	public static function connect(): PDO
	{
		if (self::$pdo !== null) {
			return self::$pdo;
		}

		$config = Config::get('database');
		$dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};dbname={$config['DB_NAME']};charset=utf8mb4";

		try {
			self::$pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]);
		}
		catch (PDOException $e) {
			Router::abort(500, $e->getMessage());
		}

		return self::$pdo;
	}
}
