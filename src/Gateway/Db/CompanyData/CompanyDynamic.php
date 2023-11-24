<?php

namespace CompassApp\Gateway\Db\CompanyData;

/**
 * Интерфейс для работы с таблицу company_data.company_dynamic
 */
class CompanyDynamic extends Main {

	protected const _TABLE_KEY = "company_dynamic";

	/**
	 * Получение значения из БД
	 */
	public static function getValue(mixed $sharding_gateway_class, string $key):int {

		$query = "SELECT * FROM `?p` WHERE `key` = ?s LIMIT ?i";
		$row   = $sharding_gateway_class::database(self::_getDbKey())->getOne($query, self::_TABLE_KEY, $key, 1);

		if (!isset($row["key"])) {
			return 0;
		}
		return $row["value"];
	}
}