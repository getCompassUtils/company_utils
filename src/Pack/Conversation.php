<?php

namespace CompassApp\Pack;

use BaseFrame\Crypt\CryptProvider;
use BaseFrame\Crypt\PackCryptProvider;
use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\ParamException;

/**
 * класс для кодирования/декодирования сущности conversation_map
 * все взаимодействие с conversation_map происходит в рамках этого класса
 * за его пределами conversation_map существует только как обычная строка
 */
class Conversation {

	// название упаковываемой сущности
	public const MAP_ENTITY_TYPE = "conversation";

	// текущая версия пакета
	protected const _CURRENT_MAP_VERSION = 1;

	// структура версий пакета
	protected const _PACKET_SCHEMA = [
		1 => [
			"shard_id" => "a",
			"table_id" => "b",
			"meta_id"  => "c",
			"uniq"     => "d",
		],
	];

	// -------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------

	// метод для запаковки и получения conversation_map
	public static function doPack(int $shard_id, int $table_id, int $meta_id):string {

		// получаем скелет текущей версии структуры
		$packet_schema = self::_PACKET_SCHEMA[self::_CURRENT_MAP_VERSION];

		// формируем пакет
		$packet = [
			"shard_id" => $shard_id,
			"table_id" => $table_id,
			"meta_id"  => $meta_id,
			"uniq"     => \CompassApp\System\Company::getCompanyId() . "_" . time(),
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

		// получаем подпись
		$output["z"] = self::_getSignature($output);

		return toJson($output);
	}

	// получить shard_id
	public static function getShardId(string $conversation_map):int {

		$packet = self::_convertMapToPacket($conversation_map);
		return $packet["shard_id"];
	}

	// получить table_id
	public static function getTableId(string $conversation_map):int {

		$packet = self::_convertMapToPacket($conversation_map);
		return $packet["table_id"];
	}

	/**
	 * получить meta_id
	 *
	 * @throws \cs_UnpackHasFailed|\cs_DecryptHasFailed
	 */
	public static function getMetaId(string $conversation_map):int {

		$packet = self::_convertMapToPacket($conversation_map);
		return $packet["meta_id"];
	}

	/**
	 * получить company_id
	 *
	 * @param string $conversation_map
	 *
	 * @return int
	 * @throws ParseFatalException
	 * @throws \cs_DecryptHasFailed
	 * @throws \cs_UnpackHasFailed
	 */
	public static function getCompanyId(string $conversation_map):int {

		$packet = self::_convertMapToPacket($conversation_map);
		$result = explode("_", $packet["uniq"]);
		if (count($result) !== 2) {
			throw new ParseFatalException("incorrect value for uniq parameter of packet");
		}

		return $result[0];
	}

	// получить shard_id из времени
	public static function getShardByTime(int $time):array {

		return [date("Y", $time), date("n", $time)];
	}

	// превратить map в key
	public static function doEncrypt(string $conversation_map):string {

		if (isset($GLOBALS[__CLASS__]["key_list"][$conversation_map])) {
			return $GLOBALS[__CLASS__]["key_list"][$conversation_map];
		}

		// формируем массив для зашифровки
		$arr = [
			"conversation_map" => $conversation_map,
		];

		// переводим сформированный conversation_key в JSON
		$json = toJson($arr);

		// зашифровываем данные
		$iv_length        = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv               = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$conversation_key = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		$GLOBALS[__CLASS__]["key_list"][$conversation_map] = $conversation_key;
		return $GLOBALS[__CLASS__]["key_list"][$conversation_map];
	}

	// превратить key в map
	public static function doDecrypt(string $conversation_key):string {

		if (isset($GLOBALS[__CLASS__]["map_list"][$conversation_key])) {
			return $GLOBALS[__CLASS__]["map_list"][$conversation_key];
		}

		// проверяем ключ на корректность
		Main::checkCorrectKey($conversation_key);

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$decrypt_result = openssl_decrypt($conversation_key, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		// если расшировка закончилась неудачно
		if ($decrypt_result === false) {
			throw new \cs_DecryptHasFailed();
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["conversation_map"])) {
			throw new \cs_DecryptHasFailed();
		}

		if (self::getCompanyId($decrypt_result["conversation_map"]) !== \CompassApp\System\Company::getCompanyId()) {
			throw new ParamException("passed conversation_key from another company");
		}

		// возвращаем conversation_map
		$GLOBALS[__CLASS__]["map_list"][$conversation_key] = $decrypt_result["conversation_map"];
		return $decrypt_result["conversation_map"];
	}

	/**
	 * Попробовать декриптнуть ключ чата
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
			throw new ParamException("failed to decrypt conversation key");
		}
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	/**
	 * конвертируем map в пакет
	 *
	 * @throws \cs_UnpackHasFailed|\cs_DecryptHasFailed
	 */
	protected static function _convertMapToPacket(string $conversation_map):array {

		if (isset($GLOBALS[__CLASS__]["packet_list"][$conversation_map])) {
			return $GLOBALS[__CLASS__]["packet_list"][$conversation_map];
		}
		$packet  = self::_unpackConversationMap($conversation_map);
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

		// проверяем наличие обязательных полей
		self::_throwIfNotExistField($output, self::_PACKET_SCHEMA[$version]);

		$output["version"]                                    = $version;
		$GLOBALS[__CLASS__]["packet_list"][$conversation_map] = $output;

		return $output;
	}

	// распаковываем conversation_map
	protected static function _unpackConversationMap(string $conversation_map):array {

		// получаем пакет из JSON
		$packet = fromJson($conversation_map);

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

		$sign     = hash_hmac("sha1", $string_for_sign, PackCryptProvider::conversation()->salt($version));
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

	/**
	 * проверяем наличие обязательных полей
	 *
	 * @throws \cs_DecryptHasFailed
	 */
	protected static function _throwIfNotExistField(array $output, array $expected_field_list):void {

		// проверяем разницу в ключах
		$diff_key_list = array_diff_key($expected_field_list, $output);
		if (count($diff_key_list) > 0) {
			throw new \cs_DecryptHasFailed();
		}
	}
}