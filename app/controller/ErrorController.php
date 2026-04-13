<?php
declare(strict_types=1);

namespace controller;

use core\Controller;

class ErrorController extends Controller
{
	public function index404(): void
	{
		$this->render('/error.html.php', [
			'title' => 'Page Not Found',
			'code' => 404,
			'message' => 'The page you are looking for does not exist.'
		]);
	}

	public function index500(): void
	{
		$this->render('/error.html.php', [
			'title' => 'Internal Server Error',
			'code' => 500,
			'message' => 'An internal server error occurred.'
		]);
	}

	public function render(string $view, array $data = []): void
	{
		extract($data);
		ob_start();
		require __DIR__ . '/../view' . $view;
	}
}
