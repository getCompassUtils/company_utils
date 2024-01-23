<?php

namespace CompassApp\Conf;

use BaseFrame\Exception\Domain\ParseFatalException;

/**
 * Класс загрузки конфига для компании.
 * Работает по паттерну singletone.
 */
class Company {

	/** @var Company|null экземпляр конфига */
	protected static ?Company $_instance = null;

	/** @var string имя конфига компаний */
	public const COMPANY_CONFIG = "company";

	/** @var string корень пути к файлу с конфигами */
	protected const _CONFIG_FILE_PATH = "/config/";

	/** @var string id компании */
	protected string $_company_id;

	/** @var array загруженный файл конфига компании */
	protected array $_included_file = [];

	/** @var array известные загруженные конфиги */
	protected array $_loaded = [];

	/**
	 * Config constructor.
	 */
	public function __construct(int $company_id) {

		$this->_company_id = $company_id;
	}

	/**
	 * Возвращает экземпляр класса.
	 */
	public static function init(int $company_id):static {

		if (is_null(static::$_instance)) {
			static::$_instance = new static($company_id);
		}

		return static::$_instance;
	}

	/**
	 * Возвращает экземпляр класса.
	 */
	public static function instance():static {

		if (is_null(static::$_instance)) {
			throw new \BaseFrame\Exception\Domain\ReturnFatalException("company config need to initialized before using");
		}

		return static::$_instance;
	}

	/**
	 * Возвращает массив с данными конфига.
	 * @return array
	 */
	public function get(string $config):mixed {

		$code = strtoupper($config);

		if (!isset($this->_loaded[$code])) {

			$codes = explode("_", $code);
			$file  = strtolower($codes[0]);

			foreach ($this->_load($file) as $config_key => $config_value) {
				$this->_loaded[strtoupper($file . "_" . $config_key)] = $config_value;
			}
		}

		return $this->_loaded[$code] ?? [];
	}

	/**
	 * Установить значение конфига
	 *
	 * @param string $config
	 * @param        $data
	 *
	 * @return void
	 */
	public function set(string $config, mixed $data):void {

		$code                 = strtoupper($config);
		$this->_loaded[$code] = $data;
	}

	/**
	 * Выполнят загрузку файла с конфигом.
	 */
	protected function _load(string $file):array {

		$path = match ($file) {
			self::COMPANY_CONFIG => static::_CONFIG_FILE_PATH . $this->_company_id . "_" . $file . ".php",
			default => throw new ParseFatalException("passed incorrect config file"),
		};

		// если уже загрузили конфиг, просто его возвращаем
		if ($this->_included_file !== []) {
			return $this->_included_file;
		}

		if (!file_exists($path)) {
			throw new \BaseFrame\Exception\Request\CompanyConfigNotFoundException("company not served by this domino");
		}

		opcache_invalidate($path);

		$this->_included_file = include($path);

		return $this->_included_file;
	}
}
