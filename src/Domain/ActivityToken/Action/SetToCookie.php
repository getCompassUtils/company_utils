<?php

namespace CompassApp\Domain\ActivityToken\Action;

use CompassApp\Domain\ActivityToken\Entity\Main;

/**
 * Сохранить токен в куки
 */
class SetToCookie {

	/**
	 * Сохранить токен в куки
	 *
	 * @param string $activity_token_key
	 *
	 * @return void
	 */
	public static function do(string $activity_token_key):void {

		// устанавливаем session_key для пользователя
		setcookie(
			Main::TOKEN_KEY_PREFIX . \CompassApp\System\Company::getCompanyId(),
			urlencode($activity_token_key),
			time() + DAY1 * 360,
			"/", \CompassApp\System\Company::getCompanyDomain(),
			false,
			false
		);
	}
}