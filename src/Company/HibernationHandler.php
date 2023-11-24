<?php

namespace CompassApp\Company;

use BaseFrame\Exception\Domain\ReturnFatalException;

/**
 * Handler для работы с гибернацией компаний.
 */
class HibernationHandler {

	private static HibernationHandler|null $_instance = null;

	private bool $_is_need_hibernation;
	private int  $_hibernation_delayed_time;
	private int  $_hibernation_delayed_till = 0;

	/**
	 * HibernationHandler constructor.
	 *
	 * @throws ReturnFatalException
	 */
	private function __construct(bool $is_need_hibernation, int $hibernation_delayed_time) {

		if ($hibernation_delayed_time < 0) {
			throw new ReturnFatalException("incorrect hibernation_delayed_time");
		}

		$this->_is_need_hibernation      = $is_need_hibernation;
		$this->_hibernation_delayed_time = $hibernation_delayed_time;
	}

	/**
	 * Инициализируем синглтон.
	 *
	 */
	public static function init(bool $is_need_hibernation, int $hibernation_delayed_time):static {

		if (!is_null(static::$_instance)) {
			return static::$_instance;
		}

		return static::$_instance = new static($is_need_hibernation, $hibernation_delayed_time);
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
	 * Нужно ли выполнять гибернацию компании.
	 *
	 */
	public function isNeedHibernation():bool {

		return $this->_is_need_hibernation;
	}

	/**
	 * Время, на которое откладываем гибернацию компании.
	 *
	 */
	public function hibernationDelayedTime():int {

		return $this->_hibernation_delayed_time;
	}

	/**
	 * До какого времени откладываем гибернацию компании.
	 */
	public function hibernationDelayedTill():int {

		return $this->_hibernation_delayed_till;
	}

	/**
	 * Высчитываем до какого времени откладываем гибернацию компании.
	 */
	public function getHibernationDelayedTill(mixed $sharding_gateway_class):void {

		// получаем время последнего засыпания компании
		$last_wakeup_at = \CompassApp\Gateway\Db\CompanyData\CompanyDynamic::getValue($sharding_gateway_class, "last_wakeup_at");

		// получаем время задержки для гибернации
		$hibernation_delayed_time = HibernationHandler::instance()->hibernationDelayedTime();
		$hibernation_delayed_till = time();

		// если прошло меньше 14 дней
		if ($last_wakeup_at > time() - DAY14) {
			$hibernation_delayed_till += $hibernation_delayed_time;
		} else {

			// если прошло больше 14 дней
			$hibernation_delayed_till += $hibernation_delayed_time * 2;
		}

		$this->_hibernation_delayed_till = $hibernation_delayed_till;
	}
}
