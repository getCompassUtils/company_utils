<?php

namespace CompassApp\Pack;

use BaseFrame\Exception\Domain\ParseFatalException;
use BaseFrame\Exception\Request\ParamException;

/**
 * класс для работы с незашифрованными map
 */
class Main {

	// исключения для объектов
	protected const _SECURITY_TEST_EXCLUDE_OBJECT_LIST = [

		// локализация
		"body_localization",
		"title_localization",

		// поиск
		"spot_detail_list",
		"message_text_replacement_list",
	];

	// исключения для полей, вводимых пользователем
	protected const _SECURITY_TEST_EXCLUDE_PROPERTY_LIST = [

		// user
		"username",
		"full_name",
		"short_description",

		// leftMenu / conversation
		"name",
		"text",

		// message
		"client_message_id",
		"group_name",
		"new_text",

		// invite
		"conversation_name",
		"single_conversation_map",

		// file
		"file_name",
		"file_extension",
		"avatar_file_map",

		// thread
		"file_extension",

		// push notification
		"title",
		"body",

		// search
		"query",
	];

	/**
	 * Получить тип сущности
	 *
	 * @param string $map
	 *
	 * @return string
	 */
	public static function getEntityType(string $map):string {

		$arr = fromJson($map);
		return $arr["?"];
	}

	// заменяет map на key в любой структуре
	public static function replaceMapWithKeys(array $array):array {

		// заменяем все map в массиве на key
		$array = self::_replaceMaps($array);

		foreach ($array as &$v) {

			// преобразуем объект в массив, в конце делаем обратное преобразование
			$is_object = is_object($v);
			$v         = self::_convertObjectToArray($is_object, $v);

			if (!is_array($v)) {
				continue;
			}

			// рекурсивно вызываем ту же функцию на измененней массив
			$v = self::replaceMapWithKeys($v);

			// преобразуем массив обратно в объект
			$v = self::_convertArrayToObject($is_object, $v);
		}

		return $array;
	}

	// заменяем все map в массиве на key
	protected static function _replaceMaps(array $array):array {

		// проверяем каждую сущность по названию
		$array = self::_replaceMessageMap($array);
		$array = self::_replaceConversationMap($array);
		$array = self::_replaceThreadMap($array);
		$array = self::_replaceParentMap($array);
		$array = self::_replaceFileMap($array);
		$array = self::_replaceInviteMap($array);
		$array = self::_replacePreviewMap($array);
		$array = self::_replaceCallMap($array);

		$array = self::_replaceMessageMapList($array);
		$array = self::_replaceConversationMapList($array);
		$array = self::_replaceThreadMapList($array);
		$array = self::_replaceFileMapList($array);
		$array = self::_replaceInviteMapList($array);
		$array = self::_replacePreviewMapList($array);
		return self::_replaceCallMapList($array);
	}

	// заменяем message_map на message_key
	protected static function _replaceMessageMap(array $array):array {

		// если пришел message_map - заменяем
		if (isset($array["message_map"])) {

			$array["message_key"] = Message::doEncrypt($array["message_map"]);
			unset($array["message_map"]);
		}

		return $array;
	}

	// заменяем conversation_map на conversation_key
	protected static function _replaceConversationMap(array $array):array {

		if (isset($array["conversation_map"])) {

			$array["conversation_key"] = Conversation::doEncrypt($array["conversation_map"]);
			unset($array["conversation_map"]);
		}

		return $array;
	}

	// заменяем thread_map на thread_key
	protected static function _replaceThreadMap(array $array):array {

		if (isset($array["thread_map"])) {

			$array["thread_key"] = Thread::doEncrypt($array["thread_map"]);
			unset($array["thread_map"]);
		}

		return $array;
	}

	// заменяем parent_map на parent_key
	protected static function _replaceParentMap(array $array):array {

		if (isset($array["parent_map"])) {

			$array["parent_key"] = match (self::getEntityType($array["parent_map"])) {

				Conversation::MAP_ENTITY_TYPE => Conversation::doEncrypt($array["parent_map"]),
				Thread::MAP_ENTITY_TYPE       => Thread::doEncrypt($array["parent_map"]),
				default                       => throw new ParseFatalException("invalid entity type")
			};

			unset($array["parent_map"]);
		}

		return $array;
	}

	// заменяем file_map на file_key
	protected static function _replaceFileMap(array $array):array {

		if (isset($array["file_map"])) {

			$array["file_key"] = mb_strlen($array["file_map"]) != 0 ? File::doEncrypt($array["file_map"]) : "";
			unset($array["file_map"]);
		}

		return $array;
	}

	// заменяем invite_map на invite_key
	protected static function _replaceInviteMap(array $array):array {

		if (isset($array["invite_map"])) {

			$array["invite_key"] = Invite::doEncrypt($array["invite_map"]);
			unset($array["invite_map"]);
		}

		return $array;
	}

	// заменяем preview_map на preview_key
	protected static function _replacePreviewMap(array $array):array {

		if (isset($array["preview_map"])) {

			$array["preview_key"] = Preview::doEncrypt($array["preview_map"]);
			unset($array["preview_map"]);
		}

		return $array;
	}

	// заменяем call_map на call_key
	protected static function _replaceCallMap(array $array):array {

		if (isset($array["call_map"])) {

			$array["call_key"] = Call::doEncrypt($array["call_map"]);
			unset($array["call_map"]);
		}

		return $array;
	}

	// заменяем message_map_list на message_key_list
	protected static function _replaceMessageMapList(array $array):array {

		// если пришел message_map - заменяем
		if (isset($array["message_map_list"])) {

			foreach ($array["message_map_list"] as $k => $v) {
				$array["message_key_list"][$k] = Message::doEncrypt($v);
			}
			unset($array["message_map_list"]);
		}

		return $array;
	}

	// заменяем conversation_map_list на conversation_key_list
	protected static function _replaceConversationMapList(array $array):array {

		if (isset($array["conversation_map_list"])) {

			foreach ($array["conversation_map_list"] as $k => $v) {
				$array["conversation_key_list"][$k] = Conversation::doEncrypt($v);
			}
			unset($array["conversation_map_list"]);
		}

		return $array;
	}

	// заменяем thread_map_list на thread_key_list
	protected static function _replaceThreadMapList(array $array):array {

		if (isset($array["thread_map_list"])) {

			foreach ($array["thread_map_list"] as $k => $v) {
				$array["thread_key_list"][$k] = Thread::doEncrypt($v);
			}
			unset($array["thread_map_list"]);
		}

		return $array;
	}

	// заменяем file_map_list на file_key_list
	protected static function _replaceFileMapList(array $array):array {

		if (isset($array["file_map_list"])) {

			foreach ($array["file_map_list"] as $k => $v) {
				$array["file_key_list"][$k] = File::doEncrypt($v);
			}
			unset($array["file_map_list"]);
		}

		return $array;
	}

	// заменяем invite_map_list на invite_key_list
	protected static function _replaceInviteMapList(array $array):array {

		if (isset($array["invite_map_list"])) {

			foreach ($array["invite_map_list"] as $k => $v) {
				$array["invite_key_list"][$k] = Invite::doEncrypt($v);
			}
			unset($array["invite_map_list"]);
		}

		return $array;
	}

	// заменяем preview_map_list на preview_key_list
	protected static function _replacePreviewMapList(array $array):array {

		if (isset($array["preview_map_list"])) {

			foreach ($array["preview_map_list"] as $k => $v) {
				$array["preview_key_list"][$k] = Preview::doEncrypt($v);
			}
			unset($array["preview_map_list"]);
		}

		return $array;
	}

	// заменяем call_map_list на call_key_list
	protected static function _replaceCallMapList(array $array):array {

		if (isset($array["call_map_list"])) {

			foreach ($array["call_map_list"] as $k => $v) {
				$array["call_key_list"][$k] = Call::doEncrypt($v);
			}
			unset($array["call_map_list"]);
		}

		return $array;
	}

	/**
	 * преобразуем объект в массив
	 *
	 * @param bool  $is_object
	 * @param mixed $object
	 *
	 * @return mixed
	 */
	protected static function _convertObjectToArray(bool $is_object, mixed $object):mixed {

		if ($is_object) {
			return (array) $object;
		}

		return $object;
	}

	// преобразуем массив в объект
	protected static function _convertArrayToObject(bool $is_object, mixed $array):mixed {

		if ($is_object) {
			return (object) $array;
		}

		return $array;
	}

	// проверяет, что в структуре не осталось незашифрованных map
	public static function doSecurityTest(array $array):array {

		// проходимся по каждому элементу массива
		foreach ($array as $k => $v) {

			// если это массив|объект, рекурсивно применяем к нему туже функцию
			if (is_array($v) || is_object($v)) {

				// если объект не в исключениях - проверяем
				if (!in_array($k, self::_SECURITY_TEST_EXCLUDE_OBJECT_LIST)) {
					self::doSecurityTest((array) $v);
				}
				continue;
			}

			// выбрасываем ошибку если пришел незакодированный map
			self::_throwIfArrayElementIsJson($k, is_null($v) ? "" : $v);
		}

		return $array;
	}

	/**
	 * выбрасываем ошибку, если пришел json
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 * @throws ParseFatalException
	 */
	protected static function _throwIfArrayElementIsJson(string $key, string $value):void {

		// если это поле исключение - пропускаем
		if (in_array($key, self::_SECURITY_TEST_EXCLUDE_PROPERTY_LIST, true)) {
			return;
		}

		// если первый символ строки не { — это не json
		if (!str_starts_with($value, "{")) {
			return;
		}

		// пробуем выполнить fromJson и смотрим что вернулось
		$json = fromJson($value);
		if (count($json) > 0) {
			throw new ParseFatalException("Key security test was failed!");
		}
	}

	// проверяем key на корректность
	public static function checkCorrectKey(string $key):string {

		// если передали пустой ключ мы отправляем ошибку
		if (isEmptyString($key)) {
			throw new ParamException("passed key is empty");
		}

		// если передали некорректное значение для base64
		// функция base64_decode() вернет FALSE в случае, если входные данные содержат символы, не входящие в алфавит base64
		if (!base64_decode($key, true)) {
			throw new ParamException("passed incorrect value contains character from outside alphabet");
		}

		return $key;
	}

	// пробуем разбить key на server_type и сам key
	public static function tryExplodeKey(string $key):array {

		// отрезаем server_type
		$tt = explode(".", $key);

		// если получилось не 2 элемента
		if (count($tt) != 2) {
			throw new ParamException("passed malformed key in request");
		}

		// если передали некорректное значение для base64
		// функция base64_decode() вернет FALSE в случае, если входные данные содержат символы, не входящие в алфавит base64
		if (!base64_decode($tt[1], true)) {
			throw new ParamException("passed incorrect value contains character from outside alphabet");
		}

		return $tt;
	}
}
