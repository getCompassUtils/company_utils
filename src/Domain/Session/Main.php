<?php

namespace CompassApp\Domain\Session;

use CompassApp\Domain\Member\Entity\Extra;

/**
 * класс для работы с пользовательской сессией
 */
class Main {

	protected const _COMPANY_COOKIE_POSTFIX = "_company_session_key";                                  // ключ, передаваемый в cookie пользователя
	protected const _HEADER_AUTH_TYPE       = \BaseFrame\Http\Header\Authorization::AUTH_TYPE_BEARER;  // тип токена для запроса

	/**
	 * проверяем что сессия существует и валидна
	 *
	 * @throws \busException
	 * @throws \cs_SessionNotFound
	 */
	public static function getSession(mixed $company_cache_class):array {

		// проверяем, что у пользователя установлена кука
		$cloud_session_uniq = self::_getCloudSessionUniq();
		if ($cloud_session_uniq === false) {
			return [0, false, false, false, [], []];
		}

		// отдаем сессию пользователя
		[$member, $session_extra] = $company_cache_class::getSessionInfo($cloud_session_uniq);
		$user_disabled_analytics_event_group_list = Extra::getDisabledAnalyticsEventGroupList($member->extra);
		return [$member->user_id, $cloud_session_uniq, $member->role, $member->permissions, $session_extra, $user_disabled_analytics_event_group_list];
	}

	// ------------------------------------------------------------
	// PROTECTED
	// ------------------------------------------------------------

	/**
	 * Получает идентификатор сессии из запроса.
	 * @throws \cs_SessionNotFound
	 */
	protected static function _getCloudSessionUniq():bool|string {

		$session_map = static::_getCloudSessionMap();
		if ($session_map === false) {
			return false;
		}

		return \CompassApp\Pack\CompanySession::getSessionUniq(static::_getCloudSessionMap());
	}

	/**
	 * Получает сессию из данных запроса
	 * @throws \cs_SessionNotFound
	 */
	protected static function _getCloudSessionMap():bool|string {

		$cloud_session_key = false;

		// сначала пытается получить токен/ключ из заголовка авторизации
		[$has_header, $header_cloud_session_uniq] = static::_tryGetCloudSessionKeyFromAuthorizationHeader();

		// если в заголовке нет, то пытается достать из кук
		if ($has_header === false || $header_cloud_session_uniq === "") {
			$cloud_session_key = static::_tryGetCloudSessionKeyFromCookie();
		}

		// если есть токен из заголовка, но нет ключа из кук, то
		// используем токен из заголовка как значение ключа
		if ($cloud_session_key === false && $header_cloud_session_uniq !== "") {
			$cloud_session_key = $header_cloud_session_uniq;
		}

		if ($cloud_session_key === false) {
			return false;
		}

		try {
			$session_map = \CompassApp\Pack\CompanySession::doDecrypt($cloud_session_key);
		} catch (\cs_DecryptHasFailed) {
			throw new \cs_SessionNotFound("session in request is invalid");
		}

		return $session_map;
	}

	/**
	 * Пытается получить сессию из токена заголовка авторизации.
	 */
	protected static function _tryGetCloudSessionKeyFromAuthorizationHeader():array {

		// получаем заголовок авторизации
		$auth_header = \BaseFrame\Http\Header\Authorization::parse();

		// заголовка авторизации в запросе нет
		if ($auth_header === false) {
			return [false, ""];
		}

		// заголовок есть, но он пустой, т.е. клиент поддерживает
		// авторизацию через заголовок, но еще не получал токен
		if ($auth_header->isNone()) {
			return [true, ""];
		}

		// заголовок есть, но он имеет некорректный формат
		if (!$auth_header->isCorrect()) {
			return [false, ""];
		}

		// заголовок есть, он корректный, но предназначен не для этого
		// модуля/сервиса, считаем, что заголовка нет в таком случае
		if ($auth_header->getType() !== static::_HEADER_AUTH_TYPE) {
			return [false, ""];
		}

		return [true, base64_decode($auth_header->getToken())];
	}

	/**
	 * Пытается получить токен из cookie.
	 */
	protected static function _tryGetCloudSessionKeyFromCookie():string|false {

		$company_session_key = static::_makeCookieKey();

		// проверяем что сессии нет в куках
		if (!isset($_COOKIE[$company_session_key])) {
			return false;
		}

		return urldecode($_COOKIE[$company_session_key]);
	}

	/**
	 * Формирует название cookie для текущей компании.
	 */
	protected static function _makeCookieKey():string {

		return \CompassApp\System\Company::getCompanyId() . self::_COMPANY_COOKIE_POSTFIX;
	}
}