<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Router\Request;

/**
 *
 */
class Secure implements \BaseFrame\Router\Middleware\Main {

	/**
	 * поверяем безопасность данных в запросе
	 */
	public static function handle(Request $request):Request {

		// перед ответом превращаем все map в key
		$request->response = \CompassApp\Pack\Main::replaceMapWithKeys($request->response);

		// проводим тест безопасности, что в ответе нет map
		\CompassApp\Pack\Main::doSecurityTest($request->response);

		return $request;
	}
}