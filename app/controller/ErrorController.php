<?php
declare(strict_types=1);
namespace controller;

use core\Controller;

class ErrorController extends Controller
{
	public function notFound(): void
	{
		$this->render('error', [
			'title' => 'Not Found',
			'message' => 'The page you are looking for does not exist.'
		]);
	}

	public function serverError(): void
	{
		$this->render('error', [
			'title' => 'Server Error',
			'message' => 'An error occurred while processing your request.'
		]);
	}
}
