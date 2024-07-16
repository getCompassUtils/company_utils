<?php

namespace CompassApp\Controller\Middleware;

use CompassApp\Company\CompanyProvider;
use CompassApp\Conf\Company as CompanyConfig;
use CompassApp\System\Company;
use BaseFrame\Exception\Request\CompanyIsHibernatedException;
use BaseFrame\Exception\Request\CompanyIsRelocatingException;
use BaseFrame\Exception\Domain\ReturnFatalException;
use BaseFrame\Router\Request;

/**
 * Проверяем, что компания доступна для запросов.
 * Определяем по статусу.
 */
#[\JetBrains\PhpStorm\Deprecated("use \CompassApp\Controller\Middleware\CheckCompany instead")]

class CheckCompanyStatus implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Проверяет статус компании из конфига.
	 * Если статус не позволяет компании обслуживать запросы, то бросаем ошибку.
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws ReturnFatalException
	 */
	#[\JetBrains\PhpStorm\Deprecated("use \CompassApp\Controller\Middleware\CheckCompany instead")]

	public static function handle(Request $request):Request {

		if (CompanyProvider::id() < 1) {
			return $request;
		}

		// получаем данные из конфига
		$company_status = CompanyConfig::instance()->get("COMPANY_STATUS");

		switch ($company_status) {

			case Company::COMPANY_STATUS_HIBERNATED:
				throw new CompanyIsHibernatedException("company hibernated");
			case Company::COMPANY_STATUS_RELOCATING:
				throw new CompanyIsRelocatingException("company has been migrated");
			case Company::COMPANY_STATUS_INVALID:
				throw new ReturnFatalException("company is broken");
			default:
				return $request;
		}
	}
}