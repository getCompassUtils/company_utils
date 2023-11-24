<?php

namespace CompassApp\Controller\Middleware\Session;

/**
 * Устанавливает флаг возможности выполнить запрос к методу с ограниченным доступом.
 */
class PremiumStatus implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Проверяем данные текущего премиум-статуса пользователя.
	 * Эта мидлвара может вызываться только после Authorization
	 */
	public static function handle(\BaseFrame\Router\Request $request):\BaseFrame\Router\Request {

		// если мидлваре нечего проверять
		if (!isset($request->extra["user"]["need_block_if_premium_inactive"], $request->extra["user"]["premium_active_till"])) {
			return $request;
		}

		// если нет флага необходимости блокировать апи
		if (!$request->extra["user"]["need_block_if_premium_inactive"]) {
			return $request;
		}

		// если апи нужно блокировать, то проверяем актуальность текущего премиума
		if ($request->extra["user"]["premium_active_till"] > time()) {
			return $request;
		}

		// если для метода нужен премиум, то заворачиваем запрос
		if ($request->controller_class->isActivePremiumRequiredForMethodCall($request->method_name)) {
			throw new \BaseFrame\Exception\Request\PremiumRequiredException("premium required");
		}

		return $request;
	}
}