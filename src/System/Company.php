<?php

namespace CompassApp\System;

use BaseFrame\Domino\DominoProvider;
use BaseFrame\Url\UrlProvider;
use CompassApp\Company\DomainProvider;

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

		[$company_url, $url_company_id] = self::getCompanyUrlData();
		// попробуем получить компанию из тела запроса, чтобы обращаться по сокету
		$company_id = self::findCompanyIdInRequestBody();

		// если компании в заголовке нет - возьмем из домена
		if ($company_id === 0 && $url_company_id !== 0) {
			$company_id = $url_company_id;
		}

		if ($company_id < 1) {

			if (isCLi()) {
				return (int) getenv("COMPANY_ID");
			}
		}

		return $company_id;
	}

	/**
	 * получаем данные из урла
	 */
	public static function getCompanyUrlData():array {

		if (defined("CODECEPTION_СONVERSATION") && CODECEPTION_СONVERSATION && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return ["c5-" . DominoProvider::id() . "." . UrlProvider::pivotDomain(), 5];
		}

		if (defined("CODECEPTION_THREAD") && CODECEPTION_THREAD && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return ["c4-" . DominoProvider::id() . "." . UrlProvider::pivotDomain(), 4];
		}

		if (defined("CODECEPTION_TEST_RUNNING") && CODECEPTION_TEST_RUNNING && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return ["c1-" . DominoProvider::id() . "." . UrlProvider::pivotDomain(), 1];
		}

		$matches = [];

		preg_match("/" . DomainProvider::domainRegex() . "/", ($_SERVER["HTTP_HOST"] ?? "") . ($_SERVER["REQUEST_URI"] ?? ""), $matches);

		if (count($matches) > 2) {
			return [(string) $matches[1], (int) $matches[2]];
		}

		return ["", 0];
	}

	/**
	 * получаем домен
	 */
	public static function getCompanyDomain():string {

		if (defined("CODECEPTION_СONVERSATION") && CODECEPTION_СONVERSATION && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return "c5-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		if (defined("CODECEPTION_THREAD") && CODECEPTION_THREAD && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return "c4-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		if (defined("CODECEPTION_TEST_RUNNING") && CODECEPTION_TEST_RUNNING && defined("DOMAIN_PIVOT") && defined("DOMINO_ID")) {
			return "c1-" . DominoProvider::id() . "." . UrlProvider::pivotDomain();
		}

		$matches = [];

		preg_match("/" . DomainProvider::domainRegex() . "/", ($_SERVER["HTTP_HOST"] ?? "") . ($_SERVER["REQUEST_URI"] ?? ""), $matches);

		if ($matches !== []) {
			return $matches[1];
		}

		return "";
	}

	/**
	 * Попробуем получить компанию из заголовка
	 */
	public static function findCompanyIdInRequestBody():int {

		if (defined("PUBLIC_ENTRYPOINT_PIVOT") && isset($_SERVER["REQUEST_SCHEME"]) && isset($_SERVER["HTTP_HOST"]) && isset($_SERVER["REQUEST_URI"]) && $_SERVER["HTTP_HOST"] === UrlProvider::pivotDomain()) {

			$schema     = $_SERVER["REQUEST_SCHEME"];
			$host       = $_SERVER["HTTP_HOST"];
			$temp       = explode("/", $_SERVER["REQUEST_URI"]);
			$entrypoint = $temp[1] ?? "";

			$entrypoint_url = "{$schema}://{$host}/{$entrypoint}";
			if ($entrypoint_url === PUBLIC_ENTRYPOINT_PIVOT) {
				return 0;
			}
		}

		return $_POST["company_id"] ?? 0;
	}
}