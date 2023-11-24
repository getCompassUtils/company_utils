<?php

namespace CompassApp\Gateway\Db\CompanyData;

/**
 * Gateway для работы с таблицей токенов активности
 */
class HibernationDelayTokenList extends Main {

	protected const _TABLE_KEY = "hibernation_delay_token_list";

	/**
	 * Вставить или обновить запись
	 *
	 * @param mixed  $sharding_gateway_class
	 * @param string $token_uniq
	 * @param int    $hibernation_delayed_till
	 * @param int    $user_id
	 *
	 * @return void
	 */
	public static function insertOrUpdate(mixed $sharding_gateway_class, string $token_uniq, int $user_id, int $hibernation_delayed_till):void {

		// получаем ключ базы данных
		$shard_key = self::_getDbKey();

		// получаем название таблицы
		$table_name = self::_getTableName();

		$insert_row = [
			"token_uniq"               => $token_uniq,
			"user_id"                  => $user_id,
			"hibernation_delayed_till" => $hibernation_delayed_till,
			"created_at"               => time(),
			"updated_at"               => time(),
		];

		$sharding_gateway_class::database($shard_key)->insertOrUpdate($table_name, $insert_row);
	}

	/**
	 * Получить имя таблицы
	 *
	 * @return string
	 */
	protected static function _getTableName():string {

		return self::_TABLE_KEY;
	}
}