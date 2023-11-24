<?php

namespace CompassApp\Domain\ActivityToken\Action;

use CompassApp\Gateway\Db\CompanySystem\MemberActivityList;

/**
 * Обновить запись токена в БД
 */
class AddActivity {

	/**
	 * Отмечаем активность пользователя за день
	 *
	 */
	public static function do(mixed $sharding_gateway_class, int $user_id, int $day_start_at):void {

		MemberActivityList::insert($sharding_gateway_class, $user_id, $day_start_at);
	}
}