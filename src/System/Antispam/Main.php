<?php

namespace CompassApp\System\Antispam;

/**
 * Class Main
 * @package CompassApp\System\Antispam
 */
class Main {

	/**
	 * Есть ли необходимость проверять блокировку
	 *
	 */
	public static function needCheckIsBlocked():bool {

		if (isBackendTest() && !isNeedAntispam()) {
			return true;
		}

		return false;
	}
}