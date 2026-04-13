<?php
declare(strict_types=1);

namespace controller;

use core\Controller;
use model\UserModel;

class JokesController extends Controller
{
	public function indexHome(): void
	{
		$this->render('/home.html.php', [
			'title' => 'Home',
			'message' => 'Welcome to the home page! Here you can find some of the best jokes around.'
		]);
	}

	public function indexAllJokes(): void
	{
		$jokes = [
			'Why don\'t scientists trust atoms? Because they make up everything!',
			'Why did the bicycle fall over? Because it was two-tired!',
			'What do you call fake spaghetti? An impasta!',
			'Why did the scarecrow win an award? Because he was outstanding in his field!',
			'What do you call cheese that isn\'t yours? Nacho cheese!'
		];

		$this->render('/all-jokes.html.php', [
			'title' => 'All Jokes',
			'jokes' => $jokes
		]);
	}
}
