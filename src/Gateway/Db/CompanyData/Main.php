<?php

namespace CompassApp\Gateway\Db\CompanyData;

/**
 * Основной класс для БД company_data
 */
class Main {

	protected const _DB_KEY = "company_data";

	/**
	 * Получить название базы данных
	 *
	 * @return string
	 */
	protected static function _getDbKey():string {

		return self::_DB_KEY;
	}
}