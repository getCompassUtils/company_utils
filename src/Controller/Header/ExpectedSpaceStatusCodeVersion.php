<?php

namespace CompassApp\Controller\Header;

/**
 * Версия поддерживаемых статус-кодов для недоступных пространств.
 */
class ExpectedSpaceStatusCodeVersion extends \BaseFrame\Http\Header\Header {

	/**
	 * @inheritDoc
	 */
	protected const _HEADER_KEY = "X_EXPECTED_SPACE_STATUS_CODE_VERSION";

	/**
	 * @inheritDoc
	 */
	public function getValue():string {

		$value = parent::getValue();

		if ($value === "") {
			return "0";
		}

		return $value;
	}
}
