<?php

namespace CompassApp\Controller\Middleware;

use CompassApp\Company\CompanyProvider;

/**
 * Проверяем, что компания доступна для запросов.
 * Определяем по наличию конфига.
 */
class CheckIsCompanyServed implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Выполняет проверку — обслуживается ли компания модулей или нет.
	 * Если не облуживается, то бросает ошибку.
	 *
	 * @throws \BaseFrame\Exception\Domain\ReturnFatalException
	 * @throws \BaseFrame\Exception\Request\CompanyNotServedException
	 */
	public static function handle(\BaseFrame\Router\Request $request):\BaseFrame\Router\Request {

		if (CompanyProvider::id() < 1) {
			return $request;
		}

		try {

			// получаем данные из конфига
			\CompassApp\Conf\Company::instance()->get("COMPANY_STATUS");
		} catch (\BaseFrame\Exception\Request\CompanyConfigNotFoundException) {
			throw new \BaseFrame\Exception\Request\CompanyNotServedException("company not served");
		}

		return $request;
	}
}