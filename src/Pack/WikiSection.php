<?php

declare(strict_types = 1);

namespace CompassApp\Pack;

use BaseFrame\Crypt\PackCryptProvider;
use BaseFrame\Exception\Domain\ParseFatalException;
use JetBrains\PhpStorm\Pure;
use BaseFrame\Crypt\CryptProvider;

/**
 * Класс для кодирования/декодирования сущности wiki_section —
 * все взаимодействие с wiki_section_map происходит в рамках этого класса,
 * за его пределами wiki_section_map существует только как обычная строка.
 *
 * Непосредственно в вики не используется, нужно для адкватного
 * шардинга в других сервисах приложения.
 */
class WikiSection {

	// название упаковываемой сущности
	protected const _MAP_ENTITY_TYPE = "wiki_section";

	// текущая версия пакета
	protected const _CURRENT_MAP_VERSION = 1;

	// структура версий пакета
	protected const _PACKET_SCHEMA = [
		1 => [
			"shard_id"   => "a",
			"table_id"   => "b",
			"section_id" => "c",
			"uniq"       => "d",
		],
	];

	// -------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------

	// метод для запаковки и получения section_map
	public static function doPack(string $shard_id, int $table_id, string $section_id):string {

		// получаем скелет текущей версии структуры
		$packet_schema = self::_PACKET_SCHEMA[self::_CURRENT_MAP_VERSION];

		// формируем пакет
		$packet = [
			"shard_id"   => $shard_id,
			"table_id"   => $table_id,
			"section_id" => $section_id,
			"uniq"       => \CompassApp\System\Company::getCompanyId(),
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
		$output["?"] = self::_MAP_ENTITY_TYPE;

		// получаем подпись
		$output["z"] = self::_getSignature($output);

		return toJson($output);
	}

	// получить shard_id
	public static function getShardId(string $section_map):string {

		$packet = self::_convertMapToPacket($section_map);
		return $packet["shard_id"];
	}

	// получить table_id
	public static function getTableId(string $section_map):int {

		$packet = self::_convertMapToPacket($section_map);
		return $packet["table_id"];
	}

	// получить meta_id
	public static function getSectionId(string $section_map):string {

		$packet = self::_convertMapToPacket($section_map);
		return $packet["section_id"];
	}

	/**
	 * получить company_id
	 *
	 * @param string $section_map
	 *
	 * @return int
	 * @throws ParseFatalException
	 */
	public static function getCompanyId(string $section_map):int {

		$packet = self::_convertMapToPacket($section_map);
		$result = explode("_", $packet["uniq"]);
		if (count($result) !== 2) {
			throw new ParseFatalException("incorrect value for uniq parameter of packet");
		}

		return (int) $result[0];
	}

	// получить shard_id из времени
	public static function getShardIdByTime(int $time):string {

		return date("Y", $time);
	}

	// получить shard_id из времени в микросекундах
	#[Pure]
	public static function getShardIdByUsTime(int $time):string {

		return self::getShardIdByTime(timeUsToSeconds($time));
	}

	// получить table_id из времени
	public static function getTableIdByTime(int $time):int {

		return (int) date("j", $time);
	}

	// получить table_id из времени в микросекундах
	#[Pure]
	public static function getTableIdByUsTime(int $time):int {

		return self::getTableIdByTime(timeUsToSeconds($time));
	}

	// превратить map в key
	public static function doEncrypt(string $section_map):string {

		if (isset($GLOBALS[__CLASS__]["key_list"][$section_map])) {
			return $GLOBALS[__CLASS__]["key_list"][$section_map];
		}

		// формируем массив для зашифровки
		$arr = [
			"section_map" => $section_map,
		];

		// переводим сформированный section_map в JSON
		$json = toJson($arr);

		// зашифровываем данные
		$iv_length   = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv          = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$section_key = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		$GLOBALS[__CLASS__]["key_list"][$section_map] = $section_key;
		return $GLOBALS[__CLASS__]["key_list"][$section_map];
	}

	/**
	 * превратить key в map
	 *
	 * @param string $section_key
	 *
	 * @return string
	 * @throws \cs_DecryptHasFailed
	 * @throws \paramException
	 * @throws \parseException
	 */
	public static function doDecrypt(string $section_key):string {

		if (isset($GLOBALS[__CLASS__]["map_list"][$section_key])) {
			return $GLOBALS[__CLASS__]["map_list"][$section_key];
		}

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$decrypt_result = openssl_decrypt($section_key, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		// если расшировка закончилась неудачно
		if (!$decrypt_result) {
			throw new \cs_DecryptHasFailed();
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["section_map"])) {
			throw new \cs_DecryptHasFailed();
		}

		// возвращаем section_map
		$GLOBALS[__CLASS__]["map_list"][$section_key] = $decrypt_result["section_map"];
		return $decrypt_result["section_map"];
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	// конвертируем map в пакет
	protected static function _convertMapToPacket(string $section_map):array {

		if (isset($GLOBALS[__CLASS__]["packet_list"][$section_map])) {
			return $GLOBALS[__CLASS__]["packet_list"][$section_map];
		}
		$packet  = self::_unpackSectionMap($section_map);
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
		$GLOBALS[__CLASS__]["packet_list"][$section_map] = $output;
		return $output;
	}

	// распаковываем section_map
	protected static function _unpackSectionMap(string $section_map):array {

		// получаем пакет из JSON
		$packet = fromJson($section_map);

		if (!isset($packet["_"], $packet["?"])) {
			throw new \cs_UnpackHasFailed();
		}

		// проверяем что передали ожидаемую сущность
		if ($packet["?"] != self::_MAP_ENTITY_TYPE) {
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

		$sign     = hash_hmac("sha1", $string_for_sign, PackCryptProvider::wiki()->salt($version));
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