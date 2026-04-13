<?php
declare(strict_types=1);
namespace controller;

use core\Controller;

class HomeController extends Controller
{
	public function index(): void
	{
		$this->render('home', [
			'title' => 'Home',
			'message' => 'Welcome to the Home Page!'
		]);
	}
}
