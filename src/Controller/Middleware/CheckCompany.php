<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Exception\Domain\ReturnFatalException;
use BaseFrame\Exception\Request\CompanyIsHibernatedException;
use BaseFrame\Exception\Request\CompanyIsRelocatingException;
use BaseFrame\Exception\Request\CompanyNotServedException;
use BaseFrame\Router\Request;
use CompassApp\Company\CompanyProvider;
use CompassApp\System\Company;

/**
 * Проверяем, что компания доступна для запросов.
 * Определяем по статусу.
 */
class CheckCompany implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Проверяет статус компании из конфига.
	 * Если статус не позволяет компании обслуживать запросы, то бросаем ошибку.
	 *
	 * @long — switch … case
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws ReturnFatalException
	 * @throws CompanyNotServedException
	 */
	public static function handle(Request $request):Request {

		if (CompanyProvider::id() < 1) {

			if (isset($request->extra["api_type"]) && $request->extra["api_type"] == "socket") {
				return $request;
			}
			throw new CompanyNotServedException("company not served");
		}

		try {

			// получаем данные из конфига
			$company_status = \CompassApp\Conf\Company::instance()->get("COMPANY_STATUS");
		} catch (\BaseFrame\Exception\Request\CompanyConfigNotFoundException) {
			throw new CompanyNotServedException("company not served");
		}

		switch ($company_status) {

			case Company::COMPANY_STATUS_HIBERNATED:
				throw new CompanyIsHibernatedException("company hibernated");
			case Company::COMPANY_STATUS_RELOCATING:
				throw new CompanyIsRelocatingException("company has been migrated");
			case Company::COMPANY_STATUS_INVALID:
				throw new ReturnFatalException("company is broken");
			case Company::COMPANY_STATUS_DELETED:
				throw new CompanyNotServedException("company not served");
			default:
				return $request;
		}
	}
}