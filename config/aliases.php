<?php
declare(strict_types=1);

/*
 * Return an array of namespace aliases and their corresponding paths. This is used by the autoloader to resolve class names to file paths.
 * Eg: 'core\\' => 'app/core/' means that any class in the 'core' namespace will be looked for in the 'app/core/' directory.
*/
return [
	'core\\' => 'app/core/',
	'controller\\' => 'app/controller/',
	'model\\' => 'app/model/',
];
