<?php

namespace CompassApp\System;

/**
 * системный класс для работы с хостами серввисов
 */
class Sharding {

	/**
	 * получаем host manticore
	 *
	 */
	public static function getManticoreHost(string $manticore_host):string {

		if (Company::isMigration()) {
			return getenv("MANTICORE_HOST");
		}
		return $manticore_host . "-" . Company::getServicePostFix();
	}

	/**
	 * получаем port manticore
	 *
	 */
	public static function getManticorePort():string {

		if (Company::isMigration()) {
			return getenv("MANTICORE_PORT");
		}
		return Company::getServicePort();
	}

	/**
	 * адрес хоста sender
	 *
	 */
	public static function getSenderHost(string $sender_host):string {

		if (Company::isMigration()) {
			return "";
		}
		return $sender_host . "-" . Company::getServicePostFix();
	}

	/**
	 * порт sender
	 *
	 */
	public static function getSenderPort():string {

		if (Company::isMigration()) {
			return "";
		}
		return Company::getServicePort();
	}

	/**
	 * ws url
	 *
	 */
	public static function getSenderWsUrl():string {

		if (Company::isMigration()) {
			return "";
		}
		return "wss://" . Company::getCompanyDomain() . "/ws?a=" . Company::getServicePostFix() . "&b=" . Company::getWsServicePort();
	}

	/**
	 * получаем mysql host
	 *
	 */
	public static function getMysqlHost(string $mysql_host):string {

		if (Company::isMigration()) {
			return getenv("MYSQL_HOST");
		}
		return $mysql_host . "-" . Company::getServicePostFix();
	}

	/**
	 * получаем mysql port
	 *
	 */
	public static function getMysqlPort():string {

		if (Company::isMigration()) {
			return getenv("MYSQL_PORT");
		}
		return Company::getServicePort();
	}

	/**
	 * получаем mysql user
	 *
	 */
	public static function getMysqlUser(string $user):string {

		if (Company::isMigration()) {
			return getenv("MYSQL_USER");
		}
		return $user;
	}

	/**
	 * получаем mysql user
	 *
	 */
	public static function getMysqlPass(string $password):string {

		if (Company::isMigration()) {
			return getenv("MYSQL_PASS");
		}
		return $password;
	}
}