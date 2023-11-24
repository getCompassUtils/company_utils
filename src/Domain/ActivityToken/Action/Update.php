<?php

namespace CompassApp\Domain\ActivityToken\Action;

use CompassApp\Gateway\Db\CompanyData\HibernationDelayTokenList;

/**
 * Обновить запись токена в БД
 */
class Update {

	/**
	 * Обновляем токен для пользователя в базе
	 *
	 * @throws \busException
	 * @throws \cs_SessionNotFound
	 */
	public static function do(mixed $sharding_gateway_class, string $token_uniq, int $user_id, string $hibernation_delayed_till):void {

		HibernationDelayTokenList::insertOrUpdate($sharding_gateway_class, $token_uniq, $user_id, $hibernation_delayed_till);
	}
}