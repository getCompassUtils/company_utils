<?php

namespace CompassApp\Pack;

use BaseFrame\Crypt\PackCryptProvider;
use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\ParamException;
use BaseFrame\Crypt\CryptProvider;

/**
 * класс для кодирования/декодирования сущности file_map
 */
class File {

	// название упаковываемой сущности
	public const MAP_ENTITY_TYPE = "file";

	// префексы ключа
	protected const _PREFIX_KEY_PIVOT   = "pivot";
	protected const _PREFIX_KEY_COMPANY = "company";

	// текущая версия пакета
	protected const _CURRENT_MAP_VERSION = 3;

	// структура версий пакета
	protected const _PACKET_SCHEMA = [
		1 => [
			"server_type" => "a",
			"shard_id"    => "b",
			"table_id"    => "c",
			"meta_id"     => "d",
			"file_type"   => "f",
			"created_at"  => "e",
			"file_source" => "g",
			"width"       => "h",
			"height"      => "i",
		],
		2 => [
			"server_type" => "a",
			"shard_id"    => "b",
			"table_id"    => "c",
			"meta_id"     => "d",
			"file_type"   => "f",
			"created_at"  => "e",
			"file_source" => "g",
			"width"       => "h",
			"height"      => "i",
			"company_id"  => "j",
		],

		// сменился тип сервера с cloud на domino
		3 => [
			"server_type" => "a",
			"shard_id"    => "b",
			"table_id"    => "c",
			"meta_id"     => "d",
			"file_type"   => "f",
			"created_at"  => "e",
			"file_source" => "g",
			"width"       => "h",
			"height"      => "i",
			"company_id"  => "j",
		],
	];

	// список доступных дефолтных file_source для использования
	protected const _DEFAULT_FILE_SOURCE_ALLOWED_LIST = [
		\FILE_SOURCE_AVATAR_DEFAULT,
	];

	// список доступных дефолтных file_source для использования с пивота
	protected const _PIVOT_FILE_SOURCE_ALLOWED_LIST = [
		\FILE_SOURCE_MESSAGE_DOCUMENT,
	];

	// список доступных file_source для CDN
	public const _ALLOWED_FILE_SOURCE_CDN_LIST = [
		FILE_SOURCE_AVATAR_CDN,
		FILE_SOURCE_VIDEO_CDN,
		FILE_SOURCE_DOCUMENT_CDN,
	];

	// -------------------------------------------------------
	// PUBLIC
	// -------------------------------------------------------

	// метод для запаковки и получения file_map
	public static function doPack(string $shard_id, int $table_id, int $meta_id, int $created_at, int $file_type, int $file_source, int $width = 0, int $height = 0):string {

		// получаем скелет текущей версии структуры
		$packet_schema = self::_PACKET_SCHEMA[self::_CURRENT_MAP_VERSION];

		// формируем пакет
		$packet = [
			"server_type" => "domino",
			"shard_id"    => $shard_id,
			"table_id"    => $table_id,
			"meta_id"     => $meta_id,
			"file_type"   => $file_type,
			"created_at"  => $created_at,
			"file_source" => $file_source,
			"width"       => $width,
			"height"      => $height,
			"company_id"  => \CompassApp\System\Company::getCompanyId(),
		];

		return self::_convertPacketToMap($packet, $packet_schema);
	}

	// конвертируем пакет в map
	protected static function _convertPacketToMap(array $packet, array $packet_schema):string {

		// упаковываем входящий массив
		$output = [];
		foreach ($packet as $key => $item) {

			// если во входящей структуре имеется некорректный ключ
			if (!isset($packet_schema[$key])) {
				throw new ParamException("Passed incorrect packet_schema in " . __METHOD__);
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

	// получить ширину изображения
	public static function getImageWidth(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);

		if (!isset($packet["width"])) {
			return 0;
		}
		return $packet["width"];
	}

	// получить высоту изображения
	public static function getImageHeight(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);
		if (!isset($packet["height"])) {
			return 0;
		}
		return $packet["height"];
	}

	// получить server_type
	public static function getServerType(string $file_map):string {

		if ($file_map === "") {
			return "";
		}

		$packet = self::_convertMapToPacket($file_map);

		if ($packet["version"] < 3 && $packet["server_type"] == "cloud") {
			$packet["server_type"] = "domino";
		}

		return $packet["server_type"];
	}

	// получить shard_id
	public static function getShardId(string $file_map):string {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["shard_id"];
	}

	// получить version
	public static function getVersion(string $file_map):string {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["version"];
	}

	// получить table_id
	public static function getTableId(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["table_id"];
	}

	// получить meta_id
	public static function getMetaId(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["meta_id"];
	}

	// получить file_type
	public static function getFileType(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["file_type"];
	}

	// получить file_source
	public static function getFileSource(string $file_map):string {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["file_source"];
	}

	// получить created_at
	public static function getCreatedAt(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);
		return $packet["created_at"];
	}

	// получить shard_id из времени
	public static function getShardIdByTime(int $time):string {

		return date("Y", $time);
	}

	// получить table_id из времени
	public static function getTableIdByTime(int $time):int {

		return date("n", $time);
	}

	// получить company_id
	public static function getCompanyId(string $file_map):int {

		$packet = self::_convertMapToPacket($file_map);

		if ($packet["version"] === self::_CURRENT_MAP_VERSION) {
			return $packet["company_id"];
		}

		return \CompassApp\System\Company::getCompanyId();
	}

	// превратить map в key
	public static function doEncrypt(string $file_map, string|false $encrypt_iv = false, string|false $encrypt_key = false):string {

		if (isset($GLOBALS[__CLASS__]["key_list"][$file_map])) {
			return $GLOBALS[__CLASS__]["key_list"][$file_map];
		}

		$encrypt_iv  = $encrypt_iv ?: CryptProvider::default()->vector();
		$encrypt_key = $encrypt_key ?: CryptProvider::default()->key();

		// формируем массив для зашифровки
		$arr = [
			"file_map" => $file_map,
		];

		// переводим сформированный file_key в JSON
		$json = toJson($arr);

		// зашифровываем данные
		$iv_length = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv        = substr($encrypt_iv, 0, $iv_length);
		$file_key  = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, $encrypt_key, 0, $iv);

		// добавляем название сервера в начало key
		$server_type = self::getServerType($file_map);
		$prefix_key  = $server_type === self::_PREFIX_KEY_PIVOT ? self::_PREFIX_KEY_PIVOT : self::_PREFIX_KEY_COMPANY;

		$GLOBALS[__CLASS__]["key_list"][$file_map] = "$prefix_key.$file_key";
		return $GLOBALS[__CLASS__]["key_list"][$file_map];
	}

	/**
	 * превратить key в map
	 *
	 * @param string       $file_key
	 * @param string|false $encrypt_iv
	 * @param string|false $encrypt_key
	 *
	 * @return string
	 * @throws ParamException
	 * @throws ParseFatalException
	 * @throws \cs_DecryptHasFailed
	 */
	public static function doDecrypt(string $file_key, string|false $encrypt_iv = false, string|false $encrypt_key = false):string {

		if (isset($GLOBALS[__CLASS__]["map_list"][$file_key])) {
			return $GLOBALS[__CLASS__]["map_list"][$file_key];
		}

		$encrypt_iv  = $encrypt_iv ?: CryptProvider::default()->vector();
		$encrypt_key = $encrypt_key ?: CryptProvider::default()->key();

		$tt       = Main::tryExplodeKey($file_key);
		$file_key = Main::checkCorrectKey($tt[1]);

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr($encrypt_iv, 0, $iv_length);
		$decrypt_result = openssl_decrypt($file_key, ENCRYPT_CIPHER_METHOD, $encrypt_key, 0, $iv);

		// если расшировка закончилась неудачно
		if ($decrypt_result === false) {
			throw new \cs_DecryptHasFailed("Decryption failed");
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["file_map"])) {
			throw new \cs_DecryptHasFailed("Missing required field");
		}

		// проверяем доступность использования файла в модуле
		if (self::getCompanyId($decrypt_result["file_map"]) !== \CompassApp\System\Company::getCompanyId()
			&& !in_array(self::getFileSource($decrypt_result["file_map"]), self::_DEFAULT_FILE_SOURCE_ALLOWED_LIST)
			&& !in_array(self::getFileSource($decrypt_result["file_map"]), self::_PIVOT_FILE_SOURCE_ALLOWED_LIST)
			&& !in_array(self::getFileSource($decrypt_result["file_map"]), self::_ALLOWED_FILE_SOURCE_CDN_LIST)) {
			throw new \cs_DecryptHasFailed("The file is not available in the module");
		}

		// возвращаем file_map
		$GLOBALS[__CLASS__]["map_list"][$file_key] = $decrypt_result["file_map"];
		return $decrypt_result["file_map"];
	}

	/**
	 * Попробовать декриптнуть ключ файла
	 *
	 * @param string $file_key
	 *
	 * @return string
	 * @throws ParamException
	 */
	public static function tryDecrypt(string $file_key):string {

		try {
			return self::doDecrypt($file_key);
		} catch (\cs_DecryptHasFailed) {
			throw new ParamException("failed to decrypt file key");
		} catch (\cs_UnpackHasFailed) {
			throw new ParamException("failed to unpack after decrypt file key");
		}
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	// конвертируем map в пакет
	protected static function _convertMapToPacket(string $file_map):array {

		if (isset($GLOBALS[__CLASS__]["packet_list"][$file_map])) {
			return $GLOBALS[__CLASS__]["packet_list"][$file_map];
		}
		$packet  = self::_unpackFileMap($file_map);
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

		$output["version"]                            = $version;
		$GLOBALS[__CLASS__]["packet_list"][$file_map] = $output;
		return $output;
	}

	// распаковываем file_map
	protected static function _unpackFileMap(string $file_map):array {

		// получаем пакет из JSON
		$packet = fromJson($file_map);

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

		$sign     = hash_hmac("sha1", $string_for_sign, PackCryptProvider::file()->salt($version));
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