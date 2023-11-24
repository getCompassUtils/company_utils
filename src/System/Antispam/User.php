<?php

namespace CompassApp\System\Antispam;

use BaseFrame\Exception\Request\BlockException;

/**
 * Значения для антиспама
 */
class User extends Main {

	const GENERATE_ACTIVITY_TOKEN = [
		"key"    => "GENERATE_ACTIVITY_TOKEN",
		"limit"  => 5,
		"expire" => 5 * 60,
	];

	protected const _DB_KEY    = "company_system";
	protected const _TABLE_KEY = "antispam_user";

	// ------------------------------------------------------------
	// PUBLIC
	// ------------------------------------------------------------

	/**
	 * Проверяем на срабатывание блокировок по конкретному ключу
	 * Пишем статистику по срабатыванию блокировки если необходимо
	 *
	 * @param mixed $sharding_gateway_class
	 * @param int   $user_id
	 * @param array $block_key
	 *
	 * @return void
	 * @throws BlockException
	 */
	public static function throwIfBlocked(mixed $sharding_gateway_class, int $user_id, array $block_key):void {

		if (self::needCheckIsBlocked()) {
			return;
		}

		// получаем текущее состояние блокировки
		$row = self::_getRow($sharding_gateway_class, $user_id, $block_key);

		// если превысили лимит - выбрасываем исключение
		if ($row["count"] >= $block_key["limit"]) {

			throw new BlockException("User with user_id [{$user_id}] blocked with key: '{$block_key["key"]}'", $row["expires_at"]);
		}

		// обновляем запись
		self::_set($sharding_gateway_class, $row["user_id"], $row["key"], $row["is_stat_sent"], $row["count"] + 1, $row["expires_at"]);
	}

	/**
	 * Получаем состояние блокировки
	 * @param mixed $sharding_gateway_class
	 * @param int   $user_id
	 * @param array $block_key
	 *
	 * @return array
	 */
	protected static function _getRow(mixed $sharding_gateway_class, int $user_id, array $block_key):array {

		// получаем запись с блокировкой из базы
		$row = self::_get($sharding_gateway_class, $user_id, $block_key["key"]);

		// если время превысило expires_at, то сбрасываем блокировку
		if (time() > $row["expires_at"]) {

			$row["count"]        = 0;
			$row["is_stat_sent"] = 0;
			$row["expires_at"]   = time() + $block_key["expire"];
		}

		return $row;
	}

	// ------------------------------------------------------------
	// PROTECTED
	// ------------------------------------------------------------

	/**
	 * Создаем новую или обновляем существующую запись в базе
	 * @param mixed  $sharding_gateway_class
	 * @param int    $user_id
	 * @param string $key
	 * @param int    $is_stat_sent
	 * @param int    $count
	 * @param int    $expires_at
	 *
	 * @return void
	 */
	protected static function _set(mixed $sharding_gateway_class, int $user_id, string $key, int $is_stat_sent, int $count, int $expires_at):void {

		$set = [
			"user_id"      => $user_id,
			"key"          => $key,
			"is_stat_sent" => $is_stat_sent,
			"count"        => $count,
			"expires_at"   => $expires_at,
		];

		$sharding_gateway_class::database(self::_DB_KEY)->insertOrUpdate(self::_TABLE_KEY, $set);
	}

	/**
	 * Пытаемся получить информацию по ключу и user_id
	 * @param mixed  $sharding_gateway_class
	 * @param int    $user_id
	 * @param string $key
	 *
	 * @return array
	 */
	protected static function _get(mixed $sharding_gateway_class, int $user_id, string $key):array {

		$row = $sharding_gateway_class::database(self::_DB_KEY)
			->getOne("SELECT * FROM `?p` WHERE user_id=?s AND `key`=?s LIMIT ?i", self::_TABLE_KEY, $user_id, $key, 1);

		// если записи нет - формируем
		if (!isset($row["user_id"])) {

			$row = [
				"user_id"      => $user_id,
				"key"          => $key,
				"is_stat_sent" => 0,
				"count"        => 0,
				"expires_at"   => 0,
			];
		}

		return $row;
	}
}