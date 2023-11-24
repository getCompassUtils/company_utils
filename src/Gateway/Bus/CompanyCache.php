<?php

namespace CompassApp\Gateway\Bus;

use BaseFrame\Exception\Gateway\BusFatalException;
use BaseFrame\Exception\Request\ControllerMethodNotFoundException;
use BaseFrame\Module\ModuleProvider;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use CompassApp\Company\CompanyProvider;
use BaseFrame\Exception\Domain\ReturnFatalException;
use BaseFrame\Socket\SocketProvider;

/**
 * класс для работы с go_company_cache
 */
class CompanyCache {

	/**
	 * Возвращает информацию по сессии.
	 *
	 * @param string $session_uniq
	 *
	 * @return array
	 *
	 * @throws BusFatalException
	 * @throws \cs_SessionNotFound
	 */
	#[ArrayShape([0 => \CompassApp\Domain\Member\Struct\Main::class, 1 => "array"])]
	public static function getSessionInfo(string $session_uniq):array {

		$request = new \CompanyCacheGrpc\SessionGetInfoRequestStruct([
			"session_uniq" => $session_uniq,
			"company_id"   => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\SessionGetInfoResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("SessionGetInfo", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			// если go_session не смог получить данные из БД
			if ($status->code == 500) {
				throw new BusFatalException("database error in go_session");
			}

			// если сессия не найдена
			if ($status->code == 902) {
				throw new \cs_SessionNotFound();
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code . " company_id: " . \CompassApp\System\Company::getCompanyId());
		}

		/** @var \CompanyCacheGrpc\MemberInfoStruct $member */
		return [self::_doFormatMember($response->getMember()), $response->getExtra()];
	}

	/**
	 * очищаем session-cache по user_id
	 *
	 * @param int $user_id
	 *
	 * @throws BusFatalException
	 */
	public static function clearSessionCacheByUserId(int $user_id):void {

		$request = new \CompanyCacheGrpc\SessionDeleteByUserIdRequestStruct([
			"user_id"    => $user_id,
			"company_id" => CompanyProvider::id(),
		]);
		[, $status] = static::_doCallGrpc("SessionDeleteByUserId", $request);
		if ($status->code !== \Grpc\STATUS_OK) {
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}
	}

	/**
	 * Получить сессии пользователя
	 *
	 * @param int $user_id
	 *
	 * @return array
	 * @throws BusFatalException
	 */
	public static function getSessionListByUserId(int $user_id):array {

		$session_uniq_list = [];
		$request           = new \CompanyCacheGrpc\SessionGetListByUserIdRequestStruct([
			"user_id"    => $user_id,
			"company_id" => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\SessionGetListByUserIdResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("SessionGetListByUserId", $request);

		if ($status->code !== \Grpc\STATUS_OK) {

			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code . " company_id: " . \CompassApp\System\Company::getCompanyId());
		}

		foreach ($response->getSessionUniqList() as $session_uniq) {

			array_push($session_uniq_list, $session_uniq);
		}

		return $session_uniq_list;
	}

	/**
	 * очищаем member-cache по user_id
	 *
	 * @param int $user_id
	 *
	 * @throws BusFatalException
	 */
	public static function clearMemberCacheByUserId(int $user_id):void {

		$request = new \CompanyCacheGrpc\MemberDeleteFromCacheByUserIdRequestStruct([
			"user_id"    => $user_id,
			"company_id" => CompanyProvider::id(),
		]);
		[, $status] = static::_doCallGrpc("MemberDeleteFromCacheByUserId", $request);
		if ($status->code !== \Grpc\STATUS_OK) {
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}
	}

	// возвращает статус микросервиса
	public static function getStatus():array {

		$request = new \CompanyCacheGrpc\SystemStatusRequestStruct();
		[$response, $status] = static::_doCallGrpc("SystemStatus", $request);
		if ($status->code !== \Grpc\STATUS_OK) {
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		return self::_formatSystemStatusResponse($response);
	}

	/**
	 * получить информацию по пользователям
	 *
	 * @param array $user_id_list
	 *
	 * @return \CompassApp\Domain\Member\Struct\Main[]
	 * @throws BusFatalException
	 * @throws ControllerMethodNotFoundException
	 */
	public static function getMemberList(array $user_id_list):array {

		$request = new \CompanyCacheGrpc\MemberGetListRequestStruct([
			"user_id_list" => $user_id_list,
			"company_id"   => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\MemberGetListResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("MemberGetList", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			if ($status->code == 14) {
				self::_checkCompanyExists(ModuleProvider::current());
			}

			// если go_session не смог получить данные из БД
			if ($status->code == 500) {
				throw new BusFatalException("database error in go_session");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		$output = [];
		foreach ($response->getMemberList() as $member) {

			/** @var \CompanyCacheGrpc\MemberInfoStruct $member */
			$output[$member->getUserId()] = self::_doFormatMember($member);
		}
		return $output;
	}

	/**
	 * получить информацию по пользователям (краткую)
	 *
	 * @param array $user_id_list
	 * @param bool  $is_only_human
	 *
	 * @return \CompassApp\Domain\Member\Struct\Short[]
	 * @throws BusFatalException
	 */
	public static function getShortMemberList(array $user_id_list, bool $is_only_human = true):array {

		$request = new \CompanyCacheGrpc\MemberGetShortListRequestStruct([
			"user_id_list" => $user_id_list,
			"company_id"   => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\MemberGetShortListResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("MemberGetShortList", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			// если go_session не смог получить данные из БД
			if ($status->code == 500) {
				throw new BusFatalException("database error in go_session");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		$output = [];
		/** @var \CompanyCacheGrpc\MemberShortInfoStruct $member */
		foreach ($response->getMemberList() as $member) {

			// если нужны только человеки и участник не человек (например, бот), то пропускаем
			if ($is_only_human && !\CompassApp\Domain\User\Main::isHuman($member->getNpcType())) {
				continue;
			}

			$output[$member->getUserId()] = self::_doFormatShortMember($member);
		}
		return $output;
	}

	/**
	 * получить информацию по одному пользователю
	 *
	 * @param int $user_id
	 *
	 * @return \CompassApp\Domain\Member\Struct\Main
	 * @throws BusFatalException
	 * @throws \cs_RowIsEmpty
	 */
	public static function getMember(int $user_id):\CompassApp\Domain\Member\Struct\Main {

		$request = new \CompanyCacheGrpc\MemberGetOneRequestStruct([
			"user_id"    => $user_id,
			"company_id" => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\MemberGetOneResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("MemberGetOne", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			// если go_company_cache не смог получить данные из БД
			if ($status->code == 500) {
				throw new BusFatalException("database error in go_company_cache");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		if (!$response->getExist()) {

			throw new \cs_RowIsEmpty();
		}

		/** @var \CompanyCacheGrpc\MemberInfoStruct $member */
		return self::_doFormatMember($response->getMember());
	}

	/**
	 * Получить значение конфига по массиву ключей
	 *
	 * @throws ControllerMethodNotFoundException
	 * @throws BusFatalException
	 */
	public static function getConfigKeyList(array $key_list):array {

		$request = new \CompanyCacheGrpc\ConfigGetListRequestStruct([
			"key_list"   => $key_list,
			"company_id" => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\ConfigGetListResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("ConfigGetList", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			if ($status->code === 14) {
				self::_checkCompanyExists(ModuleProvider::current());
			}

			// если go_session не смог получить данные из БД
			if ($status->code === 500) {
				throw new BusFatalException("database error in go_session");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		$output = [];
		/** @var \CompanyCacheGrpc\KeyInfoStruct $key_item */
		foreach ($response->getKeyList() as $key_item) {

			$output[$key_item->getKey()] = self::_doFormatConfig($key_item);
		}
		return $output;
	}

	/**
	 * Получаем значение конфига по одному ключу
	 *
	 * @throws ControllerMethodNotFoundException
	 * @throws \cs_RowIsEmpty
	 * @throws BusFatalException
	 */
	public static function getConfigKey(string $key):\CompassApp\Domain\Space\Struct\Config {

		$request = new \CompanyCacheGrpc\ConfigGetOneRequestStruct([
			"key"        => $key,
			"company_id" => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\ConfigGetOneResponseStruct $response */
		[$response, $status] = static::_doCallGrpc("ConfigGetOne", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			if ($status->code === 14) {
				self::_checkCompanyExists(ModuleProvider::current());
			}

			// если go_company_cache не смог получить данные из БД
			if ($status->code === 500) {
				throw new BusFatalException("database error in go_company_cache");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}

		if (!$response->getExist()) {
			throw new \cs_RowIsEmpty();
		}

		/** @var \CompanyCacheGrpc\MemberInfoStruct $member */
		return self::_doFormatConfig($response->getKey());
	}

	/**
	 * Очищаем кеш конфига пространства по ключу
	 *
	 * @throws BusFatalException
	 */
	public static function clearConfigCacheByKey(string $key):void {

		$request = new \CompanyCacheGrpc\ConfigDeleteFromCacheByKeyRequestStruct([
			"key"        => $key,
			"company_id" => CompanyProvider::id(),
		]);
		[, $status] = static::_doCallGrpc("ConfigDeleteFromCacheByKey", $request);
		if ($status->code !== \Grpc\STATUS_OK) {
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}
	}

	/**
	 * Очистить кэш с пользователями
	 *
	 * @throws BusFatalException
	 * @throws ControllerMethodNotFoundException
	 */
	public static function clearConfigCache():void {

		$request = new \CompanyCacheGrpc\ConfigClearCacheRequestStruct([
			"company_id" => CompanyProvider::id(),
		]);

		/** @var \CompanyCacheGrpc\ConfigClearCacheResponseStruct $response */
		[, $status] = static::_doCallGrpc("ConfigClearCache", $request);
		if ($status->code !== \Grpc\STATUS_OK) {

			if ($status->code === 14) {
				self::_checkCompanyExists(ModuleProvider::current());
			}

			// если go_company_cache не смог получить данные из БД
			if ($status->code === 500) {
				throw new BusFatalException("database error in go_company_cache");
			}
			throw new BusFatalException("undefined error_code in " . __CLASS__ . " code " . $status->code);
		}
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	// делаем grpc запрос к указанному методу с переданными данными
	protected static function _doCallGrpc(string $method_name, mixed $request):array {

		$connection = \Bus::getConnection("company_cache", \CompanyCacheGrpc\companyCacheClient::class);

		return $connection->callGrpc($method_name, $request);
	}

	/**
	 * Проверка, существует ли такая компания
	 *
	 * @throws ControllerMethodNotFoundException
	 */
	protected static function _checkCompanyExists(string $current_module):void {

		if (!self::_exists($current_module)) {
			throw new ControllerMethodNotFoundException("company does not exist, probably subdomain is wrong");
		}
	}

	/**
	 * Существует ли такая компания
	 *
	 * @return bool
	 * @throws \returnException|ReturnFatalException
	 */
	protected static function _exists(string $current_module):bool {

		[$status, $response] = self::_doCallCompany("company.main.exists", [], $current_module);

		// если сокет-запрос не вернул ok
		if ($status != "ok") {

			$txt = toJson($response);
			throw new ReturnFatalException("Socket request member.getUserRoleList status != ok. Response: {$txt}");
		}

		return $response["exists"];
	}

	// получаем подпись из массива параметров
	protected static function _doCallCompany(string $method, array $params, string $current_module, int $user_id = 0):array {

		// получаем url и подпись
		$url = self::_getSocketCompanyUrl("company");
		return self::_doCall($url, $method, $params, $current_module, $user_id);
	}

	// получаем подпись из массива параметров
	protected static function _doCall(string $url, string $method, array $params, string $current_module, int $company_id, int $user_id = 0):array {

		// переводим в json параметры
		$json_params = toJson($params);

		// получаем url и подпись
		$signature = \BaseFrame\Socket\Authorization\Handler::getSignature(
			\BaseFrame\Socket\Authorization\Handler::AUTH_TYPE_KEY, SocketProvider::keyMe(), $json_params
		);

		return \BaseFrame\Socket\Main::doCall($url, $method, $json_params, $signature, $current_module, $company_id, $user_id);
	}

	/**
	 * получаем url
	 *
	 */
	protected static function _getSocketCompanyUrl(string $module):string {

		$socket_url_config    = SocketProvider::configUrl();
		$socket_module_config = SocketProvider::configModule();
		return $socket_url_config["company"] . $socket_module_config[$module]["socket_path"];
	}

	/**
	 * Форматируем в структуру
	 */
	protected static function _doFormatConfig(\CompanyCacheGrpc\KeyInfoStruct $key_info_struct):\CompassApp\Domain\Space\Struct\Config {

		return new \CompassApp\Domain\Space\Struct\Config(
			$key_info_struct->getKey(),
			$key_info_struct->getCreatedAt(),
			$key_info_struct->getUpdatedAt(),
			fromJson($key_info_struct->getValue())
		);
	}

	/**
	 * форматируем в структуру
	 *
	 * @param \CompanyCacheGrpc\MemberInfoStruct $member
	 *
	 * @return \CompassApp\Domain\Member\Struct\Main
	 */
	protected static function _doFormatMember(\CompanyCacheGrpc\MemberInfoStruct $member):\CompassApp\Domain\Member\Struct\Main {

		return new \CompassApp\Domain\Member\Struct\Main(
			$member->getUserId(),
			$member->getRole(),
			$member->getNpcType(),
			$member->getPermissions(),
			$member->getCreatedAt(),
			$member->getUpdatedAt(),
			$member->getCompanyJoinedAt(),
			$member->getLeftAt(),
			$member->getFullnameUpdatedAt(),
			$member->getFullname(),
			$member->getMbtiType(),
			$member->getShortDescription(),
			$member->getAvatarFileKey(),
			$member->getComment(),
			fromJson($member->getExtra())
		);
	}

	/**
	 * форматируем в структуру
	 *
	 * @param \CompanyCacheGrpc\MemberShortInfoStruct $member
	 *
	 * @return \CompassApp\Domain\Member\Struct\Short
	 */
	protected static function _doFormatShortMember(\CompanyCacheGrpc\MemberShortInfoStruct $member):\CompassApp\Domain\Member\Struct\Short {

		return new \CompassApp\Domain\Member\Struct\Short(
			$member->getUserId(),
			$member->getRole(),
			$member->getNpcType(),
			$member->getPermissions(),
		);
	}

	// форматируем ответ system.status
	#[Pure] #[ArrayShape(["name" => "string", "goroutines" => "int|string", "memory" => "int|string", "memory_kb" => "string", "memory_mb" => "string", "uptime" => "int"])]
	protected static function _formatSystemStatusResponse(\CompanyCacheGrpc\SystemStatusResponseStruct $response):array {

		// формируем ответ
		return [
			"name"       => $response->getName(),
			"goroutines" => $response->getGoroutines(),
			"memory"     => $response->getMemory(),
			"memory_kb"  => $response->getMemoryKb(),
			"memory_mb"  => $response->getMemoryMb(),
			"uptime"     => $response->getUptime(),
		];
	}
}
