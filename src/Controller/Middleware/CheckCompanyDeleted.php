<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Exception\Request\CompanyNotServedException;
use BaseFrame\Router\Request;
use CompassApp\Company\CompanyProvider;
use CompassApp\Conf\Company as CompanyConfig;
use CompassApp\System\Company;

/**
 * Проверяем, что компания не удалена.
 * Определяем по статусу.
 */
#[\JetBrains\PhpStorm\Deprecated("use \CompassApp\Controller\Middleware\CheckCompany instead")]
class CheckCompanyDeleted implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Проверяет статус компании из конфига.
	 * Если статус не позволяет компании обслуживать запросы, то бросаем ошибку.
	 *
	 * @throws CompanyNotServedException
	 * @throws \BaseFrame\Exception\Domain\ReturnFatalException
	 */
	public static function handle(Request $request):Request {

		if (CompanyProvider::id() < 1) {
			return $request;
		}

		// получаем данные из конфига
		$company_status = CompanyConfig::instance()->get("COMPANY_STATUS");

		switch ($company_status) {

			case Company::COMPANY_STATUS_DELETED:
				throw new CompanyNotServedException("company not served");
			default:
				return $request;
		}
	}
}