<?php

namespace CompassApp\Domain\ActivityToken\Entity;

use BaseFrame\Crypt\CryptProvider;
use CompassApp\Domain\ActivityToken\Struct\Header;
use CompassApp\Domain\ActivityToken\Struct\Payload;
use CompassApp\Domain\ActivityToken\Exception\DecryptFailed;

/**
 * Основной класс сущности "Токен активности"
 */
class Main {

	protected const _ENCRYPT_ALGORITHM = "SHA256";
	protected const _TOKEN_TYPE = "JWT";
	protected const _EXPIRE_TIME = 5 * 60;

	public const TOKEN_KEY_PREFIX = "company_hibernation_delay_key_"; // ключ, передаваемый в cookie пользователя

	public static function generate(int $user_id, int $company_id):\CompassApp\Domain\ActivityToken\Struct\Main {

		$header = new Header(
			self::_ENCRYPT_ALGORITHM,
			self::_TOKEN_TYPE
		);

		$payload = new Payload(
			generateRandomString(12),
			$user_id,
			$company_id,
			time() + self::_EXPIRE_TIME,
		);

		// создаем подпись
		$signature = self::makeSignature($header, $payload);

		// собираем токен и возвращаем
		return new \CompassApp\Domain\ActivityToken\Struct\Main(
			$header,
			$payload,
			$signature
		);
	}

	/**
	 * Сформировать подпись
	 *
	 * @param Header  $header
	 * @param Payload $payload
	 *
	 * @return string
	 */
	public static function makeSignature(Header $header, Payload $payload):string {

		return hash(self::_ENCRYPT_ALGORITHM, CryptProvider::default()->key() . "." . base64_encode(toJson($header)) . "." . base64_encode(toJson($payload)));
	}

	/**
	 * Сверить, что подпись валидна
	 *
	 * @param \CompassApp\Domain\ActivityToken\Struct\Main $activity_token
	 *
	 * @return bool
	 */
	public static function assertValidSignature(\CompassApp\Domain\ActivityToken\Struct\Main $activity_token):bool {

		return self::makeSignature($activity_token->header, $activity_token->payload) === $activity_token->signature;
	}

	/**
	 * Зашифровать токен
	 *
	 * @param \CompassApp\Domain\ActivityToken\Struct\Main $token
	 *
	 * @return string
	 */
	public static function encrypt(\CompassApp\Domain\ActivityToken\Struct\Main $token):string {

		if (isset($GLOBALS["activity_token_key_list"][$token->payload->token_uniq])) {
			return $GLOBALS["activity_token_key_list"][$token->payload->token_uniq];
		}

		// переводим token в JSON
		$json = toJson(["activity_token" => $token]);

		// зашифровываем данные
		$iv_length   = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv          = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$activity_token_key = openssl_encrypt($json, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		$GLOBALS["activity_token_key_list"][$token->payload->token_uniq] = $activity_token_key;
		return $GLOBALS["activity_token_key_list"][$token->payload->token_uniq];
	}

	/**
	 * Расшифровать токен
	 *
	 * @param string $activity_token_key
	 *
	 * @return \CompassApp\Domain\ActivityToken\Struct\Main
	 */
	public static function decrypt(string $activity_token_key):\CompassApp\Domain\ActivityToken\Struct\Main {

		if (isset($GLOBALS["activity_token_list"][$activity_token_key])) {
			return $GLOBALS["activity_token_list"][$activity_token_key];
		}

		// расшифровываем
		$iv_length      = openssl_cipher_iv_length(ENCRYPT_CIPHER_METHOD);
		$iv             = substr(CryptProvider::default()->vector(), 0, $iv_length);
		$decrypt_result = openssl_decrypt($activity_token_key, ENCRYPT_CIPHER_METHOD, CryptProvider::default()->key(), 0, $iv);

		// если расшировка закончилась неудачно
		if ($decrypt_result === false) {
			throw new DecryptFailed("could not decrypt activity key");
		}

		$decrypt_result = fromJson($decrypt_result);

		// проверяем наличие обязательных полей
		if (!isset($decrypt_result["activity_token"])) {
			throw new DecryptFailed("could not decrypt acitivity key");
		}

		$activity_token = self::_convertToObject($decrypt_result["activity_token"]);

		// проверяем, что подпись токена валидная
		if (!self::assertValidSignature($activity_token)) {
			throw new DecryptFailed("signature of activity token is invalid");
		}

		// возвращаем call_map
		$GLOBALS["activity_token_list"][$activity_token_key] = $activity_token;
		return $activity_token;
	}

	/**
	 * Конвертировать массив в объект
	 *
	 * @param array $activity_token_arr
	 *
	 * @return \CompassApp\Domain\ActivityToken\Struct\Main
	 */
	protected static function _convertToObject(array $activity_token_arr):\CompassApp\Domain\ActivityToken\Struct\Main {

		return new \CompassApp\Domain\ActivityToken\Struct\Main(
			new Header(
				$activity_token_arr["header"]["algorithm"],
				$activity_token_arr["header"]["type"],
			),
			new Payload(
				$activity_token_arr["payload"]["token_uniq"],
				$activity_token_arr["payload"]["user_id"],
				$activity_token_arr["payload"]["company_id"],
				$activity_token_arr["payload"]["expires_at"],
			),
			$activity_token_arr["signature"],
		);
	}
}