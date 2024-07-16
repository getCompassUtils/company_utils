<?php

namespace CompassApp\Controller;

use BaseFrame\Exception\Domain\ParseFatalException;
use CompassApp\Domain\Member\Entity\Permission;

/**
 * экшены апи компании
 */
class ApiAction extends \BaseFrame\Controller\Action {

	// просим клиент подгрузить пользователей
	public function users(array $user_list):void {

		// nothing
		if (count($user_list) < 1) {
			return;
		}

		if (!isset($this->_ar_need["users"])) {
			$this->_ar_need["users"] = [];
		}

		foreach ($user_list as $v) {
			$this->_ar_need["users"][$v] = null;
		}
	}

	// отдаем клиенту состояние авторизации
	public function member(array $extra):void {

		$this->_ar_need["member"]["extra"] = $extra;
	}

	/**
	 * просим клиент пропинговать ноды для звонка
	 *
	 */
	public function needPing(string $call_map, array $node_list):void {

		$this->_ar_need["need_ping"] = [
			"call_map"  => (string) $call_map,
			"node_list" => (array) $node_list,
		];
	}

	// просим клиент подгрузить пользователей
	protected function _getUsers(array $user_list):array {

		$output    = [];
		$user_list = array_keys($user_list);

		// не забываем про форматирование, клиенты будут ругаться если сюда попадут строки
		foreach ($user_list as $v) {
			$output[] = intval($v);
		}

		return [
			"user_list" => (array) $output,
			"signature" => (string) self::getUsersSignature($output, time()),
		];
	}

	/**
	 * Отдаем клиенту состояние авторизации
	 *
	 * @return int[]
	 */
	protected function _getMember(array $member):array {

		$extra                      = $member["extra"];
		$permissions_output_version = $extra["permissions_output_version"] ?? Permission::CURRENT_PERMISSIONS_OUTPUT_SCHEMA_VERSION;

		// инициализируем массив, который вернем в ответе
		$output = [
			"logged_in" => (int) 0,
		];

		// если пользователь авторизован
		if ($this->_user_id > 0) {

			// получаем информацию о пользователе
			$user_info                      = \CompassApp\Gateway\Bus\CompanyCache::getMember($this->_user_id);
			$output["permissions"]          = Permission::formatToOutput($user_info->role, $user_info->permissions, $permissions_output_version);
			$output["logged_in"]            = (int) 1;
			$output["member"]               = (object) \CompassApp\Domain\Member\Entity\Member::formatMember($user_info);
			$output["is_display_push_body"] = (int) $extra["is_display_push_body"];

			// удаляем легаси во второй версии прав
			if ($permissions_output_version > 1) {
				unset($output["permission_list"], $output["is_user_forced_admin"], $output["member"]->role);
			}
		}



		return $output;
	}

	// просим клиент пропинговать ноды для звонка
	protected function _getNeedPing(array $data):array {

		return $data;
	}

	/**
	 * Получает подпись
	 *
	 * @param array $user_list
	 * @param int   $time
	 *
	 * @return string
	 * @throws ParseFatalException
	 */
	public static function getUsersSignature(array $user_list, int $time):string {

		// делаем int каждого элемента
		$temp = [];
		foreach ($user_list as $v) {
			$temp[] = (int) $v;
		}
		$user_list = $temp;

		$user_list[] = $time;
		sort($user_list);

		$json = toJson($user_list);
		if (!defined("ENCRYPT_IV_ACTION") || !defined("ENCRYPT_PASSPHRASE_ACTION")) {
			throw new ParseFatalException("ENCRYPT_IV_DEFAULT or ENCRYPT_PASSPHRASE_ACTION not found");
		}

		// зашифровываем данные
		$iv_length   = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv          = substr(ENCRYPT_IV_ACTION, 0, $iv_length);
		$binary_data = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, ENCRYPT_PASSPHRASE_ACTION, 0, $iv);

		return md5($binary_data) . "_" . $time;
	}

	// очистить все накопленные actions
	public function end():void {

		$this->_ar_need = [];
	}

}