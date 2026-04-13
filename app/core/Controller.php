<?php
declare(strict_types=1);

namespace core;

abstract class Controller
{
	protected function render(string $view, array $data = []): void
	{
		extract($data);
		ob_start();
		require __DIR__ . '/../view/layout/header.html.php';
		require __DIR__ . '/../view' . $view;
		require __DIR__ . '/../view/layout/footer.html.php';
	}
}
