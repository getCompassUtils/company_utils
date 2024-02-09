<?php

namespace CompassApp\Controller\Exception;

/**
 * Исключение для клиентского запроса — пространство переезжает.
 */
class SpaceIsRelocatingException extends \BaseFrame\Exception\RequestException {

	const HTTP_CODE = 410;
}
