<?php

namespace CompassApp\Controller\Exception;

/**
 * Исключение для клиентского запроса — пространство удалено.
 */
class SpaceNotServedException extends \BaseFrame\Exception\RequestException {

	const HTTP_CODE = 404;
}
