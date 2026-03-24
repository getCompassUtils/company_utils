<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\ApiGateway\GatewayData;
use CompassApp\Domain\Member\Entity\Extra;
use BaseFrame\Exception\Request\EndpointAccessDeniedException;
use BaseFrame\Router\Request;
use CompassApp\Domain\Member\Entity\Member;

/**
 * Авторизуем пользователя по API ключу
 */
class CompanyGatewayAuthorization implements \BaseFrame\Router\Middleware\Main
{
	/**
	 * Авторизуем пользователя по API ключу
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws CompanyNotServedException
	 * @throws ParseFatalException
	 * @throws ReturnFatalException
	 * @throws SpaceHibernateException
	 * @throws SpaceIsRelocatingException
	 * @throws SpaceNotServedException
	 */
	public static function handle(Request $request): Request
	{

		$company_cache_class = $request->extra["company_cache_class"];
		$auth_header         = \BaseFrame\Http\Header\Authorization::parse();

		// заголовка авторизации в запросе нет
		if ($auth_header === false
		|| $auth_header->isNone()
		|| !$auth_header->isCorrect()
		|| $auth_header->getType() !== \BaseFrame\Http\Header\Authorization::AUTH_TYPE_BEARER
		) {
			throw new EndpointAccessDeniedException("invalid gateway data");
		}

		$gateway_data = GatewayData::fromJwt($auth_header->getToken());

		if (is_null($gateway_data)) {
			throw new EndpointAccessDeniedException("invalid gateway data");
		}

		try {
			$member = $company_cache_class::getMember($gateway_data->user_id);
		} catch (\cs_RowIsEmpty) {
			throw new EndpointAccessDeniedException("user not found");
		}

		if ($member === Member::ROLE_LEFT) {
			throw new EndpointAccessDeniedException("user left company");
		}

		$user_disabled_analytics_event_group_list = Extra::getDisabledAnalyticsEventGroupList($member->extra);

		$request->user_id                                                   = $member->user_id;
		$request->extra["user"]["role"]                                     = $member->role;
		$request->extra["user"]["permissions"]                              = $member->permissions;
		$request->extra["user"]["user_disabled_analytics_event_group_list"] = $user_disabled_analytics_event_group_list;
		$request->extra["user"]["need_block_if_premium_inactive"]           = false;
		$request->extra["user"]["premium_active_till"]                      = 0;
		$request->extra["gateway_data"]                                     = $gateway_data;
		return $request;
	}
}
