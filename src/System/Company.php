<?php

namespace CompassApp\System;

use BaseFrame\Domino\DominoProvider;
use BaseFrame\Url\UrlProvider;

/**
 * системный класс для работы с хостами компании
 */
class Company {

	public const COMPANY_STATUS_INVALID    = 99; // компания зафейлилась и находится в подвешенном состоянии
	public const COMPANY_STATUS_ACTIVE     = 2;  // компания активна
	public const COMPANY_STATUS_HIBERNATED = 10; // компания в гибернации
	public const COMPANY_STATUS_RELOCATING = 40; // компаня перемещается с одного мира на другой
	public const COMPANY_STATUS_DELETED    = 50; // компания удалена

	/**
	 * если запущена миграция
	 *
	 */
	public static function isMigration():bool {

		$value = getenv("IS_MIGRATION");
		if ($value === "true") {
			return true;
		}

		return false;
	}

	/**
	 * получаем постфикс для сервера
	 */
	public static function getServicePostFix():string {

		return self::getCompanyId();
	}

	/**
	 * получаем порт
	 */
	public static function getServicePort():string {

		if (self::isMigration()) {
			return 1;
		}

		return self::getCompanyId();
	}

	/**
	 * получаем порт
	 */
	public static function getWsServicePort():string {

		if (self::isMigration()) {
			return 1;
		}

		return self::getCompanyId();
	}

	/**
	 * Получаем company_id из домена
	 *
	 * @return int
	 */
	public static function getCompanyId():int {

		$server_name = self::getCompanyDomain();

		// попробуем получить компанию из тела запроса, чтобы обращаться по сокету
		$company_id = self::findCompanyIdInRequestBody();

		// если компании в заголовке нет - возьмем из домена
		if ($company_id == 0) {

			$company_domino = explode(".", $server_name)[0];
			$company        = explode("-", $company_domino);

			if (count($company) == 2) {
				$company_id = intval(substr($company[0], 1));
			}
		}

		if ($company_id < 1) {

			if (isCLi()) {
				return intval(getenv("COMPANY_ID"));
			}
		}

		return $company_id;
	}

	/**
	 * получаем домен
	 */
	public static function getCompanyDomain():string {

		if (defined("CODECEPTION_СONVERSATION") && CODECEPTION_СONVERSATION && defined("PIVOT_DOMAIN") && defined("DOMINO_ID")) {
			return "c5-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		if (defined("CODECEPTION_THREAD") && CODECEPTION_THREAD && defined("PIVOT_DOMAIN") && defined("DOMINO_ID")) {
			return "c4-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		if (defined("CODECEPTION_TEST_RUNNING") && CODECEPTION_TEST_RUNNING && defined("PIVOT_DOMAIN") && defined("DOMINO_ID")) {
			return "c1-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		return $_SERVER["HTTP_HOST"] ?? "";
	}

	/**
	 * Попробуем получить компанию из заголовка
	 */
	public static function findCompanyIdInRequestBody():int {

		if (defined("PIVOT_DOMAIN") && isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"] === UrlProvider::pivotDomain()) {
			return 0;
		}

		return $_POST["company_id"] ?? 0;
	}
}