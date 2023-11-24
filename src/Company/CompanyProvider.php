<?php

namespace CompassApp\Company;

/**
 * Класс-обертка для работы с компаниями
 */
class CompanyProvider {

	/**
	 * Закрываем конструктор.
	 */
	protected function __construct() {

	}

	/**
	 * получаем company_id
	 *
	 */
	public static function id():int {

		return CompanyHandler::instance()->id();
	}
}
