<?php
declare(strict_types=1);

namespace model;

use core\Model;

class UserModel extends Model
{
	public int $id;
	public string $username;
	public string $passwordHash;

	public static function findById(int $id): ?self
	{
		return new self($id, 'example', '$2y$12$2yomXD358.0MWTvT.JuX8uZIeVaZWoMJSyghjYw4DPj9j3se0dVGq');
	}

	public static function findByUsername(string $username): ?self
	{
		return new self(1, $username, '$2y$12$2yomXD358.0MWTvT.JuX8uZIeVaZWoMJSyghjYw4DPj9j3se0dVGq');
	}

	public function __construct(int $id, string $username, string $passwordHash)
	{
		$this->id = $id;
		$this->username = $username;
		$this->passwordHash = $passwordHash;
	}

	public function verifyPassword(string $password): bool
	{
		return password_verify($password, $this->passwordHash);
	}
}
