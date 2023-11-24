<?php

namespace CompassApp\Domain\Session;

use CompassApp\Domain\Member\Entity\Extra;

/**
 * класс для работы с пользовательской сессией
 */
class Main {

	protected const _COMPANY_COOKIE_POSTFIX = "_company_session_key"; // ключ, передаваемый в cookie пользователя

	/**
	 * проверяем что сессия существует и валидна
	 *
	 * @throws \busException
	 * @throws \cs_SessionNotFound
	 */
	public static function getSession(mixed $company_cache_class):array {

		// проверяем, что у пользователя установлена кука
		$cloud_session_uniq = self::_getCloudSessionUniqFromCookie();
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
	 * получаем session map из куки
	 *
	 * @return false|string
	 * @mixed
	 * @throws \cs_SessionNotFound
	 */
	protected static function _getCloudSessionUniqFromCookie():bool|string {

		$company_session_key = \CompassApp\System\Company::getCompanyId() . self::_COMPANY_COOKIE_POSTFIX;

		// проверяем что сессии нет в куках
		if (!isset($_COOKIE[$company_session_key])) {
			return false;
		}

		try {
			return \CompassApp\Pack\CompanySession::getSessionUniq(\CompassApp\Pack\CompanySession::doDecrypt(urldecode($_COOKIE[$company_session_key])));
		} catch (\cs_DecryptHasFailed) {
			throw new \cs_SessionNotFound();
		}
	}
}