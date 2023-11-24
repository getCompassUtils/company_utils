<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\BlockException;
use BaseFrame\Router\Request;
use CompassApp\Company\CompanyProvider;
use CompassApp\Domain\ActivityToken\Action\AddActivity;
use CompassApp\Domain\ActivityToken\Action\GetFromCookie;
use CompassApp\Domain\ActivityToken\Action\SetToCookie;
use CompassApp\Domain\ActivityToken\Action\Update;
use CompassApp\Domain\ActivityToken\Entity\Main;
use CompassApp\System\Antispam\User;

/**
 * Добавить activity токен пользователю
 */
class AddActivityToken implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Добавляем activity токен пользователю
	 *
	 * @param Request $request
	 *
	 * @return Request
	 * @throws ParseFatalException
	 * @throws \busException
	 * @throws \cs_SessionNotFound
	 * @throws \BaseFrame\Exception\Domain\ReturnFatalException
	 * @long
	 */
	public static function handle(Request $request):Request {

		// если для компании не требуется гибернация или метод не триггерит активность - ничего не делаем
		if (!\CompassApp\Company\HibernationHandler::instance()->isNeedHibernation()
			|| !$request->controller_class->isNeedRefreshHibernationDelayTokenMethod($request->method_name)) {

			return $request;
		}

		// если пришел левый user_id
		if ($request->user_id < 1) {
			return $request;
		}

		// если не смогли найти класс гейтвея
		if (!isset($request->extra["sharding_gateway_class"])) {
			throw new ParseFatalException("cant find sharding gateway class");
		}

		$sharding_gateway_class = $request->extra["sharding_gateway_class"];

		// забираем из куки токен активности
		$activity_token = GetFromCookie::do();

		// если у пользователя нет токена или он кривой, или истек, генерируем новый
		if ($activity_token && $activity_token->payload->expires_at > time()) {
			return $request;
		}

		try {
			User::throwIfBlocked($sharding_gateway_class, $request->user_id, User::GENERATE_ACTIVITY_TOKEN);
		} catch (BlockException) {

			// ничего не делаем, просто возвращаем реквест (блокировать пользователя из-за боковой штуки не гуд)
			return $request;
		}

		$activity_token = Main::generate($request->user_id, CompanyProvider::id());

		// получаем до какого времени откладываем гибернацию компании
		\CompassApp\Company\HibernationHandler::instance()->getHibernationDelayedTill($sharding_gateway_class);
		$hibernation_delayed_till = \CompassApp\Company\HibernationHandler::instance()->hibernationDelayedTill();

		Update::do($sharding_gateway_class, $activity_token->payload->token_uniq, $request->user_id, $hibernation_delayed_till);
		AddActivity::do($sharding_gateway_class, $request->user_id, dayStart());

		// установить куки пользователю
		SetToCookie::do(Main::encrypt($activity_token));
		return $request;
	}
}