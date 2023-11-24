<?php

namespace CompassApp\Gateway\Db\CompanySystem;

/**
 * Gateway для работы с таблицей активности пользователей в компании
 */
class MemberActivityList extends Main {

	protected const _TABLE_KEY = "member_activity_list";

	/**
	 * Вставить запись
	 *
	 * @param mixed $sharding_gateway_class
	 * @param int   $user_id
	 * @param int   $day_start_at
	 *
	 * @return void
	 */
	public static function insert(mixed $sharding_gateway_class, int $user_id, int $day_start_at):void {

		// получаем ключ базы данных
		$shard_key = self::_getDbKey();

		// получаем название таблицы
		$table_name = self::_getTableName();

		$insert_row = [
			"user_id"      => $user_id,
			"day_start_at" => $day_start_at,
		];
		$sharding_gateway_class::database($shard_key)->insert($table_name, $insert_row);
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