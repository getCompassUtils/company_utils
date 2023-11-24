<?php

namespace CompassApp\Pack;

use BaseFrame\Crypt\CryptProvider;
use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\ParamException;
use BaseFrame\Crypt\PackCryptProvider;

/**
 * класс для кодирования/декодирования сущности preview_map
 */
class Preview {

	// название упаковываемой сущности
	public const MAP_ENTITY_TYPE = "preview";

	// текущая версия пакета
	protected const _CURRENT_MAP_VERSION = 1;

	// структура версий пакета
	protected const _PACKET_SCHEMA = [
		1 => [
			"table_id"     => "a",
			"preview_hash" => "b",
			"created_at"   => "c",
		],
	];

	// -------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------

	// метод для запаковки и получения preview
	public static function doPack(int $table_id, string $preview_hash, int $created_at):string {

		// получаем скелет текущей версии структуры
		$packet_schema = self::_PACKET_SCHEMA[self::_CURRENT_MAP_VERSION];

		// формируем пакет
		$packet = [
			"table_id"     => $table_id,
			"preview_hash" => $preview_hash,
			"created_at"   => $created_at,
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

	// получить table_id
	public static function getTableId(string $preview_map):int {

		$packet = self::_convertMapToPacket($preview_map);
		return $packet["table_id"];
	}

	// получить preview_hash
	public static function getPreviewHash(string $preview_map):string {

		$packet = self::_convertMapToPacket($preview_map);

		return $packet["preview_hash"];
	}

	// получить table_id из времени
	public static function getTableIdByTime(int $time):string {

		return date("n", $time);
	}

	// сгенерировать preview_hash
	public static function getPreviewHashByUrl(string $prepared_url):string {

		return sha1($prepared_url);
	}

	// сгенерировать preview_hash по ссылке и языку
	public static function getPreviewHashByLangAndUrl(string $lang, string $prepared_url):string {

		return sha1($lang . "_" . $prepared_url);
	}

	/**
	 * Сгенерировать хеш превью
	 *
	 * @param string $lang
	 * @param string $prepared_url
	 * @param string $salt
	 *
	 * @return string
	 */
	public static function generatePreviewHash(string $lang, string $prepared_url, string $salt = ""):string {

		$preview_string = $lang . "_" . $prepared_url;
		$preview_string .= $salt !== "" ? "" : ("_" . $salt);

		return sha1($preview_string);
	}

	// превратить map в key
	public static function doEncrypt(string $preview_map):string {

		if (isset($GLOBALS[__CLASS__]["key_list"][$preview_map])) {
			return $GLOBALS[__CLASS__]["key_list"][$preview_map];
		}

		// формируем массив для зашифровки
		$arr = [
			"preview_map" => $preview_map,
		];

		// переводим сформированный preview_map в JSON
		$json = toJson($arr);

		// зашифровываем данные
		$iv_length   = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv          = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$preview_key = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		$GLOBALS[__CLASS__]["key_list"][$preview_map] = $preview_key;
		return $GLOBALS[__CLASS__]["key_list"][$preview_map];
	}

	/**
	 * превратить key в map
	 *
	 * @param string $preview_key
	 *
	 * @return string
	 * @throws \cs_DecryptHasFailed
	 * @throws \paramException
	 * @throws \parseException
	 */
	public static function doDecrypt(string $preview_key):string {

		if (isset($GLOBALS[__CLASS__]["map_list"][$preview_key])) {
			return $GLOBALS[__CLASS__]["map_list"][$preview_key];
		}

		// проверяем ключ на корректность
		Main::checkCorrectKey($preview_key);

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$decrypt_result = openssl_decrypt($preview_key, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		// если расшировка закончилась неудачно
		if ($decrypt_result === false) {
			throw new \cs_DecryptHasFailed();
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["preview_map"])) {
			throw new \cs_DecryptHasFailed();
		}

		// возвращаем preview_map
		$GLOBALS[__CLASS__]["map_list"][$preview_key] = $decrypt_result["preview_map"];
		return $decrypt_result["preview_map"];
	}

	/**
	 * Попробовать декриптнуть ключ превью
	 *
	 * @param string $preview_key
	 *
	 * @return string
	 * @throws \paramException
	 * @throws \parseException
	 */
	public static function tryDecrypt(string $preview_key):string {

		try {
			return self::doDecrypt($preview_key);
		} catch (\cs_DecryptHasFailed) {
			throw new ParamException("failed to decrypt preview key");
		}
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	// конвертируем map в пакет
	protected static function _convertMapToPacket(string $preview_map):array {

		if (isset($GLOBALS[__CLASS__]["packet_list"][$preview_map])) {
			return $GLOBALS[__CLASS__]["packet_list"][$preview_map];
		}
		$packet  = self::_unpackPreviewMap($preview_map);
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
		$output["version"]                               = $version;
		$GLOBALS[__CLASS__]["packet_list"][$preview_map] = $output;
		return $output;
	}

	// распаковываем preview_map
	protected static function _unpackPreviewMap(string $preview_map):array {

		// получаем пакет из JSON
		$packet = fromJson($preview_map);

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

		$sign     = hash_hmac("sha1", $string_for_sign, PackCryptProvider::preview()->salt($version));
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