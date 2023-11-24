<?php

namespace CompassApp\Pack\Message;

use BaseFrame\Crypt\PackCryptProvider;
use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\ParamException;
use CompassApp\Pack\Main;
use BaseFrame\Crypt\CryptProvider;

/**
 * класс для кодирования/декодирования сущности message_map для ТРЕДА
 * все взаимодействие с message_map происходит в рамках этого класса
 * за его пределами message_map существует только как обычная строка
 */
class Thread {

	// название упаковываемой сущности
	public const MAP_ENTITY_TYPE = "thread_message";

	// текущая версия пакета
	protected const _CURRENT_MAP_VERSION = 1;

	// структура версий пакета
	protected const _PACKET_SCHEMA = [
		1 => [
			"thread_map"           => "a",
			"block_id"             => "b",
			"block_message_index"  => "c",
			"thread_message_index" => "d",
		],
	];

	// -------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------

	// метод для запаковки и получения message_map
	public static function doPack(string $thread_map, int $block_id, int $block_message_index, int $thread_message_index):string {

		// получаем скелет текущей версии структуры
		$packet_schema = self::_PACKET_SCHEMA[self::_CURRENT_MAP_VERSION];

		// формируем пакет
		$packet = [
			"thread_map"           => $thread_map,
			"block_id"             => $block_id,
			"block_message_index"  => $block_message_index,
			"thread_message_index" => $thread_message_index,
		];

		return self::_convertPacketToMap($packet, $packet_schema);
	}

	/**
	 * конвертируем пакет в map
	 *
	 * @param array $packet
	 * @param array $packet_schema
	 *
	 * @return string
	 * @throws ParseFatalException
	 */
	protected static function _convertPacketToMap(array $packet, array $packet_schema):string {

		// упаковываем входящий массив
		$output = [];
		foreach ($packet as $key => $item) {

			// если во входящей структуре имеется некорректный ключ
			if (!isset($packet_schema[$key])) {
				throw new ParseFatalException("Passed incorrect packet schema in " . __METHOD__);
			}

			// добавляем ключ
			$output[$packet_schema[$key]] = $item;
		}

		// добавляем версию пакета
		$output["_"] = self::_CURRENT_MAP_VERSION;

		// добавляем название сущности
		$output["?"] = self::MAP_ENTITY_TYPE;

		$output["z"] = self::_getSignature($output);

		return toJson($output);
	}

	// получить thread_map
	public static function getThreadMap(string $message_map):string {

		$packet = self::_convertMapToPacket($message_map);
		return $packet["thread_map"];
	}

	// получить shard_id
	public static function getShardId(string $message_map):string {

		$packet = self::_convertMapToPacket($message_map);
		return \CompassApp\Pack\Thread::getShardId($packet["thread_map"]);
	}

	// получить table_id
	public static function getTableId(string $message_map):int {

		$packet = self::_convertMapToPacket($message_map);
		return \CompassApp\Pack\Thread::getTableId($packet["thread_map"]);
	}

	// получить block_id
	public static function getBlockId(string $message_map):int {

		$packet = self::_convertMapToPacket($message_map);
		return $packet["block_id"];
	}

	// получаем block_message_index из message_map
	public static function getBlockMessageIndex(string $message_map):int {

		$packet = self::_convertMapToPacket($message_map);
		return $packet["block_message_index"];
	}

	// получаем thread_message_index из message_map
	public static function getThreadMessageIndex(string $message_map):int {

		$packet = self::_convertMapToPacket($message_map);

		return $packet["thread_message_index"];
	}

	// превратить map в key
	public static function doEncrypt(string $message_map):string {

		if (isset($GLOBALS[__CLASS__]["key_list"][$message_map])) {
			return $GLOBALS[__CLASS__]["key_list"][$message_map];
		}

		// формируем массив для зашифровки
		$arr = [
			"message_map" => $message_map,
		];

		// переводим сформированный message_key в JSON
		$json = toJson($arr);

		// зашифровываем данные
		$iv_length   = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv          = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$message_key = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		$GLOBALS[__CLASS__]["key_list"][$message_map] = $message_key;
		return $GLOBALS[__CLASS__]["key_list"][$message_map];
	}

	// превратить key в map
	public static function doDecrypt(string $message_key):string {

		if (isset($GLOBALS[__CLASS__]["map_list"][$message_key])) {
			return $GLOBALS[__CLASS__]["map_list"][$message_key];
		}

		// проверяем ключ на корректность
		Main::checkCorrectKey($message_key);

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$decrypt_result = openssl_decrypt($message_key, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		// если расшировка закончилась неудачно
		if ($decrypt_result === false) {
			throw new \cs_DecryptHasFailed();
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["message_map"])) {
			throw new \cs_DecryptHasFailed();
		}
		$message_map = $decrypt_result["message_map"];

		// проверяем, что message_map относится к conversation_map, который в текущей компании
		if (!\CompassApp\Pack\Message::isFromThread($message_map)) {
			throw new ParamException("passed message_key from another location");
		}

		$thread_map = self::getThreadMap($message_map);
		if (\CompassApp\Pack\Thread::getCompanyId($thread_map) !== \CompassApp\System\Company::getCompanyId()) {
			throw new ParamException("passed message_key from another company");
		}

		// возвращаем message_map
		$GLOBALS[__CLASS__]["map_list"][$message_key] = $message_map;
		return $decrypt_result["message_map"];
	}

	/**
	 * Попробовать декриптнуть ключ сообщения треда
	 *
	 * @param string $message_key
	 *
	 * @return string
	 * @throws ParamException
	 */
	public static function tryDecrypt(string $message_key):string {

		try {
			return self::doDecrypt($message_key);
		} catch (\cs_DecryptHasFailed) {
			throw new ParamException("failed to decrypt thread message key");
		}
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	// конвертируем map в пакет
	protected static function _convertMapToPacket(string $message_map):array {

		if (isset($GLOBALS[__CLASS__]["packet_list"][$message_map])) {
			return $GLOBALS[__CLASS__]["packet_list"][$message_map];
		}

		$packet  = self::_unpackMessageMap($message_map);
		$version = self::_throwIfUnsupportedVersion($packet);

		$passed_sign = $packet["z"];
		unset($packet["z"]);

		self::_checkSignature($passed_sign, $packet);

		// убираем добавляемые поля
		unset($packet["_"]);
		unset($packet["?"]);

		// распаковываем пакет
		$output                = [];
		$flipped_packet_schema = array_flip(self::_PACKET_SCHEMA[$version]);

		foreach ($packet as $key => $item) {

			self::_throwIfReplacementKeyNotSet($flipped_packet_schema, $key);
			$output[$flipped_packet_schema[$key]] = $item;
		}
		$output["version"]                    = $version;
		$GLOBALS[__CLASS__]["packet_list"][$message_map] = $output;
		return $output;
	}

	// распаковываем message_map
	protected static function _unpackMessageMap(string $message_map):array {

		// получаем пакет из JSON
		$packet = fromJson($message_map);

		if (!isset($packet["_"], $packet["?"])) {
			throw new \cs_UnpackHasFailed();
		}

		// проверяем что передали ожидаемую сущность
		if ($packet["?"] != self::MAP_ENTITY_TYPE) {
			throw new \cs_UnpackHasFailed();
		}

		return $packet;
	}

	// получаем версию пакета
	protected static function _throwIfUnsupportedVersion(array $packet):int {

		// получаем версию пакета
		$version = $packet["_"];

		// проверяем существование такой версии
		if (!isset(self::_PACKET_SCHEMA[$version])) {
			throw new \cs_UnpackHasFailed();
		}

		return $version;
	}

	// проверяем пришедшую подпись на подлинность
	protected static function _checkSignature(string $passed_sign, array $packet):void {

		// получаем подпись и сверяем с пришедшей
		$sign = self::_getSignature($packet);

		// если подпись не совпала
		if ($sign != $passed_sign) {
			throw new \cs_UnpackHasFailed();
		}
	}

	// получает подпись для пакета
	protected static function _getSignature(array $packet):string {

		// получаем версию пакета
		$version = $packet["_"];

		// сортируем массив пакета по ключам
		ksort($packet);

		// формируем подпись
		$string_for_sign = implode(",", $packet);

		$sign     = hash_hmac("sha1", $string_for_sign, PackCryptProvider::message()->salt($version));
		$sign_len = mb_strlen($sign);

		// получаем ее короткую версию (каждый 5 символ из подписи)
		$short_sign = "";
		for ($i = 1; $i <= $sign_len; $i++) {

			if ($i % 5 == 0) {
				$short_sign .= $sign[$i - 1];
			}
		}

		return $short_sign;
	}

	// выбрасываем ошибку, если заменяемый ключ отсутствует в пакете
	protected static function _throwIfReplacementKeyNotSet(array $flipped_packet_schema, string $key):void {

		if (!isset($flipped_packet_schema[$key])) {
			throw new \cs_UnpackHasFailed();
		}
	}
}