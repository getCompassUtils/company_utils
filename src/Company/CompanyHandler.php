<?php

namespace CompassApp\Company;

use BaseFrame\Exception\Domain\ReturnFatalException;

/**
 * Класс для работы с компаниями
 */
class CompanyHandler {

	private static CompanyHandler|null $_instance = null;
	private string                     $_company_id;

	/**
	 * Company constructor.
	 *
	 * @throws ReturnFatalException
	 */
	private function __construct(int $company_id) {

		if ($company_id < 0) {
			throw new ReturnFatalException("incorrect company_id");
		}

		$this->_company_id = $company_id;
	}

	/**
	 * инициализируем синглтон
	 *
	 */
	public static function init(int $company_id):static {

		if (!is_null(static::$_instance)) {
			return static::$_instance;
		}

		return static::$_instance = new static($company_id);
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
	 * получаем pivot_domain
	 *
	 */
	public function id():int {

		return $this->_company_id;
	}
}
