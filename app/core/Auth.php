<?php
declare(strict_types=1);

namespace core;

use model\UserModel;

class Auth
{
	public static function init(): void
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
	}

	public static function check(): bool
	{
		return isset($_SESSION['userId']);
	}

	public static function userId(): ?int
	{
		return (int) $_SESSION['userId'] ?? null;
	}

	public static function requireAuth(): void
	{
		if (!self::check()) {
			Router::redirect('/login');
		}
	}

	public static function attempt(string $username, string $password): bool
	{
		$user = UserModel::findByUsername($username);

		if (!$user) {
			return false;
		}

		if (!$user->verifyPassword($password)) {
			return false;
		}

		self::storeUserIdInSession($user->id);
		return true;
	}

	public static function refreshUser(): void
	{
		if (!self::check()) {
			return;
		}

		$user = UserModel::findById(self::userId());

		if (!$user) {
			self::logout();
			return;
		}

		self::storeUserIdInSession($user->id, false);
	}

	public static function login(UserModel $user): void
	{
		self::storeUserIdInSession($user->id);
	}

	public static function logout(): void
	{
		$_SESSION = [];

		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		session_destroy();
	}

	private static function storeUserIdInSession(int $userId, bool $regenerate = true): void
	{
		if ($regenerate) {
			session_regenerate_id(true);
		}

		$_SESSION['userId'] = $userId;
	}
}
