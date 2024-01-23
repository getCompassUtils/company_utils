<?php

namespace CompassApp\Controller\Exception;

/**
 * Исключение для клиентского запроса — пространство в гибернации.
 */
class SpaceHibernateException extends \BaseFrame\Exception\RequestException {

	const HTTP_CODE = 481;
}
