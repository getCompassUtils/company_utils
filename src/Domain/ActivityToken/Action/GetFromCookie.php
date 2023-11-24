<?php

namespace CompassApp\Domain\ActivityToken\Action;

use CompassApp\Domain\ActivityToken\Entity\Main;
use CompassApp\Domain\ActivityToken\Exception\DecryptFailed;

/**
 * Получить ключ активности из куки
 */
class GetFromCookie {

	/**
	 * Получить ключ активности из куки
	 *
	 * @return bool|\CompassApp\Domain\ActivityToken\Struct\Main
	 */
	public static function do():bool|\CompassApp\Domain\ActivityToken\Struct\Main {

		$activity_token_key = Main::TOKEN_KEY_PREFIX . \CompassApp\System\Company::getCompanyId();

		// проверяем, есть ли токен в куках
		if (!isset($_COOKIE[$activity_token_key])) {
			return false;
		}

		// если не получилось декрпинуть - возвращаем false
		try {
			return Main::decrypt(urldecode($_COOKIE[$activity_token_key]));
		} catch (DecryptFailed) {
			return false;
		}
	}
}