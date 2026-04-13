<?php
declare(strict_types=1);
namespace core;

abstract class Controller
{
	protected function render(string $view, array $data = []): void
	{
		extract($data);
		ob_start();
		require '../app/view/layout/header.html.php';
		require '../app/view/' . $view . '.html.php';
		require '../app/view/layout/footer.html.php';
		ob_end_flush();
	}
}
