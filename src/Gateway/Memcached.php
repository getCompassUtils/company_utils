<?php

namespace CompassApp\Gateway;

/**
 * Class Memcached
 */
class Memcached extends \mCache {

	// получаем очередь
	// эта функция используется после позднего наследования
	protected static function _getPrefix():string {

		return \CompassApp\System\Company::getCompanyId();
	}
}