<?php

namespace CompassApp\Controller\Middleware;

use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Domain\ReturnFatalException;
use BaseFrame\Exception\Request\CompanyConfigNotFoundException;
use BaseFrame\Exception\Request\CompanyIsHibernatedException;
use BaseFrame\Exception\Request\CompanyIsRelocatingException;
use BaseFrame\Exception\Request\CompanyNotServedException;
use BaseFrame\Router\Request;
use CompassApp\Company\CompanyProvider;
use CompassApp\Controller\Exception\SpaceHibernateException;
use CompassApp\Controller\Exception\SpaceIsRelocatingException;
use CompassApp\Controller\Exception\SpaceNotServedException;
use CompassApp\Controller\Header\ExpectedSpaceStatusCodeVersion;
use CompassApp\System\Company;

/**
 * Проверяем, что компания доступна для запросов.
 * Определяем по статусу.
 */
class CheckCompany implements \BaseFrame\Router\Middleware\Main {

	/**
	 * Проверяет статус компании из конфига.
	 * Если статус не позволяет компании обслуживать запросы, то бросаем ошибку.
	 *
	 * @param Request $request
	 * @return Request
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws CompanyNotServedException
	 * @throws ParseFatalException
	 * @throws ReturnFatalException
	 * @throws SpaceHibernateException
	 * @throws SpaceIsRelocatingException
	 * @throws SpaceNotServedException
	 */
	public static function handle(Request $request):Request {

		$is_internal = isset($request->extra["api_type"]) && $request->extra["api_type"] === "socket";

		if (CompanyProvider::id() < 1) {

			// возможно это внутренний запрос к домино,
			// не к конкретной компании и его нужно пропустить
			if ($is_internal) {
				return $request;
			}

			// если внешний запрос к конкретной компании,
			// то идентификатор должен быть объявлен
			throw new CompanyNotServedException("company not served");
		}

		if ($is_internal) {
			return static::_handleInternal($request);
		}

		return match ((new ExpectedSpaceStatusCodeVersion())->getValue()) {
			"2"     => static::_handleV2($request),
			default => static::_handleV1($request),
		};
	}

	/**
	 * Обрабатывает внутренний запрос.
	 * Может вернуть 500+ http-код.
	 *
	 * @param Request $request
	 * @return Request
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws CompanyNotServedException
	 * @throws ReturnFatalException
	 * @throws ParseFatalException
	 */
	protected static function _handleInternal(Request $request):Request {

		try {

			// получаем данные из конфига
			$company_status = \CompassApp\Conf\Company::instance()->get("COMPANY_STATUS");
		} catch (CompanyConfigNotFoundException) {
			throw new CompanyNotServedException("company not served");
		}

		switch ($company_status) {

			case Company::COMPANY_STATUS_HIBERNATED:
				throw new CompanyIsHibernatedException("company hibernated");
			case Company::COMPANY_STATUS_RELOCATING:
				throw new CompanyIsRelocatingException("company has been migrated");
			case Company::COMPANY_STATUS_INVALID:
				throw new ReturnFatalException("company is broken");
			case Company::COMPANY_STATUS_DELETED:
				throw new CompanyNotServedException("company not served");
			default:
				return $request;
		}
	}

	/**
	 * Обрабатывает клиентский запрос с флагом первой версии.
	 * Здесь клиенту может прилететь 500+ ошибка.
	 *
	 * @param Request $request
	 * @return Request
	 *
	 * @throws CompanyIsHibernatedException
	 * @throws CompanyIsRelocatingException
	 * @throws CompanyNotServedException
	 * @throws ParseFatalException
	 * @throws ReturnFatalException
	 */
	protected static function _handleV1(Request $request):Request {

		return static::_handleInternal($request);
	}

	/**
	 * Обрабатывает клиентский запрос с флагом второй версии.
	 * Здесь клиенту не должны прилетать 500+ ошибки.
	 *
	 * @param Request $request
	 * @return Request
	 *
	 * @throws CompanyNotServedException
	 * @throws ParseFatalException
	 * @throws ReturnFatalException
	 * @throws SpaceHibernateException
	 * @throws SpaceIsRelocatingException
	 * @throws SpaceNotServedException
	 */
	protected static function _handleV2(Request $request):Request {

		try {

			// получаем данные из конфига
			$company_status = \CompassApp\Conf\Company::instance()->get("COMPANY_STATUS");
		} catch (CompanyConfigNotFoundException) {
			throw new CompanyNotServedException("company not served");
		}

		switch ($company_status) {

			case Company::COMPANY_STATUS_HIBERNATED:
				throw new SpaceHibernateException("company hibernated");
			case Company::COMPANY_STATUS_RELOCATING:
				throw new SpaceIsRelocatingException("company has been migrated");
			case Company::COMPANY_STATUS_INVALID:
				throw new ReturnFatalException("company is broken");
			case Company::COMPANY_STATUS_DELETED:
				throw new SpaceNotServedException("company not served");
			default:
				return $request;
		}
	}
}