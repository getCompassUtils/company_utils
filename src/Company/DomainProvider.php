<?php

namespace CompassApp\Company;

/**
 * Класс-обертка для работы с доменом
 */
class DomainProvider {

	/**
	 * Закрываем конструктор.
	 */
	protected function __construct() {

	}

	/**
	 * Получаем регулярку для домена
	 *
	 */
	public static function domainRegex():string {

		return DomainHandler::instance()->domainRegex();
	}
}
