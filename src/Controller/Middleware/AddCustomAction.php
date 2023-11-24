<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Router\Request;

/**
 * Инициализиуем кастомный action
 */
class AddCustomAction implements \BaseFrame\Router\Middleware\Main {

	/**
	 * авторизуем пользователя
	 */
	public static function handle(Request $request):Request {

		$request->extra["action"] = \CompassApp\Controller\ApiAction::class;

		return $request;
	}
}