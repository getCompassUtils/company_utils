<?php

namespace CompassApp\Gateway\Db\CompanySystem;

/**
 * Основной класс для БД company_system
 */
class Main {

	protected const _DB_KEY = "company_system";

	/**
	 * Получить название базы данных
	 *
	 * @return string
	 */
	protected static function _getDbKey():string {

		return self::_DB_KEY;
	}
}