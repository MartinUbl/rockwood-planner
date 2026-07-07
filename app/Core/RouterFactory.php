<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;
		$router->addRoute('', 'Project:default');
		$router->addRoute('<presenter [A-Za-z][A-Za-z0-9.-]*>/<action [A-Za-z][A-Za-z0-9-]*>[/<id>]', 'Home:default');
		return $router;
	}
}
