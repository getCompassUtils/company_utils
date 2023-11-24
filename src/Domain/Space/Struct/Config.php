<?php

namespace CompassApp\Domain\Space\Struct;

/**
 * Структура для информации о значении конфига
 */
class Config {

	public string $key;
	public int    $created_at;
	public int    $updated_at;
	public array  $value;

	/**
	 * Struct_Bus_CompanyCache_Config constructor.
	 *
	 * @param string $key
	 * @param int    $created_at
	 * @param int    $updated_at
	 * @param array  $value
	 */
	public function __construct(
		string $key,
		int    $created_at,
		int    $updated_at,
		array  $value,
	) {

		$this->key        = $key;
		$this->created_at = $created_at;
		$this->updated_at = $updated_at;
		$this->value      = $value;
	}
}