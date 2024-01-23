<?php

namespace CompassApp\Company;

use BaseFrame\Exception\Domain\ReturnFatalException;

/**
 * Класс для работы с доменом
 */
class DomainHandler {

	private static DomainHandler|null $_instance = null;
	private string                    $_domain_regex;

	/**
	 * Domain constructor.
	 *
	 * @throws ReturnFatalException
	 */
	private function __construct(string $domain_regex) {

		$this->_domain_regex = $domain_regex;
	}

	/**
	 * инициализируем синглтон
	 *
	 */
	public static function init(string $domain_regex):static {

		if (!is_null(static::$_instance)) {
			return static::$_instance;
		}

		return static::$_instance = new static($domain_regex);
	}

	/**
	 * Возвращает экземпляр класса.
	 */
	public static function instance():static {

		if (is_null(static::$_instance)) {
			throw new ReturnFatalException("need to initialized before using");
		}

		return static::$_instance;
	}

	/**
	 * Получить регулярку для домена
	 *
	 * @return string
	 */
	public function domainRegex():string {

		return $this->_domain_regex;
	}
}
