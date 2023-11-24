<?php

namespace CompassApp\Domain\Member\Entity;

use CompassApp\Domain\Member\Exception\AccountDeleted;
use CompassApp\Domain\Member\Struct\Main;
use JetBrains\PhpStorm\ArrayShape;

/**
 * класс для взаимодействия с extra участника компании
 */
class Extra {

	// -------------------------------------------------------
	// EXTRA VARIABLES
	// -------------------------------------------------------

	protected const _EXTRA_VERSION = 7; // текущая версия extra
	protected const _EXTRA_SCHEME  = [  // массив с версиями extra

		1 => [
			"badge"        => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at" => 0,
		],
		2 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0,
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
		],
		3 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0,
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
			"is_deleted"                               => 0,  // удаление аккаунта из системы
		],
		4 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0,
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
			"is_deleted"                               => 0,  // удаление аккаунта из системы
			"alias_avg_screen_time"                    => 0, // среднее экранное время пользователя
			"alias_total_action_count"                 => 0, // общее количество действий пользователя
		],
		5 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0,
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
			"is_deleted"                               => 0,  // удаление аккаунта из системы
			"alias_avg_screen_time"                    => 0, // среднее экранное время пользователя
			"alias_total_action_count"                 => 0, // общее количество действий пользователя
			"alias_avg_message_answer_time"            => 0, // среднее время ответа пользователя на сообщения
		],
		6 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0, // временная метка покидания пространства
			"alias_disabled_at"                        => 0, // временная метка удаления аккаунта
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
			"is_deleted"                               => 0,  // удаление аккаунта из системы
			"alias_avg_screen_time"                    => 0, // среднее экранное время пользователя
			"alias_total_action_count"                 => 0, // общее количество действий пользователя
			"alias_avg_message_answer_time"            => 0, // среднее время ответа пользователя на сообщения
		],
		7 => [
			"badge"                                    => [
				"content"  => "", // текст баджа
				"color_id" => 0,  // цвет баджа
			],
			"dismissed_at"                             => 0, // временная метка покидания пространства
			"alias_disabled_at"                        => 0, // временная метка удаления аккаунта
			"user_disabled_analytics_event_group_list" => [], // отключенные для логирования события
			"is_deleted"                               => 0,  // удаление аккаунта из системы
			"alias_avg_screen_time"                    => 0, // среднее экранное время пользователя
			"alias_total_action_count"                 => 0, // общее количество действий пользователя
			"alias_avg_message_answer_time"            => 0, // среднее время ответа пользователя на сообщения
			"avatar_color_id"                          => 0, // цвет аватарки
		],
	];

	// -------------------------------------------------------
	// EXTRA METHODS
	// -------------------------------------------------------

	/**
	 * Получаем цвет badge
	 *
	 * @param array $extra
	 *
	 * @return int
	 */
	public static function getBadgeColor(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		// отдаем цвет badge
		return $extra["extra"]["badge"]["color_id"] ?? 0;
	}

	/**
	 * Получаем content badge
	 *
	 * @param array $extra
	 *
	 * @return string
	 */
	public static function getBadgeContent(array $extra):string {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		// отдаем контент баджа
		return $extra["extra"]["badge"]["content"] ?? "";
	}

	/**
	 * Получаем время увольнения
	 *
	 * @param array $extra
	 *
	 * @return int
	 */
	public static function getDismissedAt(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["dismissed_at"] ?? 0;
	}

	/**
	 * Устанавливаем badge в extra
	 *
	 * @param array  $extra
	 * @param int    $color_id
	 * @param string $content
	 *
	 * @return array
	 */
	public static function setBadgeInExtra(array $extra, int $color_id, string $content):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		// обновляем бадж
		$extra["extra"]["badge"]["color_id"] = $color_id;
		$extra["extra"]["badge"]["content"]  = $content;

		return $extra;
	}

	/**
	 * Устанавливаем время увольнения
	 *
	 * @param array $extra
	 * @param int   $dismissed_at
	 *
	 * @return array
	 */
	public static function setDismissedAt(array $extra, int $dismissed_at):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["dismissed_at"] = $dismissed_at;

		return $extra;
	}

	/**
	 * Удаляем badge из extra
	 *
	 * @param array $extra
	 *
	 * @return array
	 */
	public static function doRemoveBadgeFromExtra(array $extra):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		// удаляем badge
		$extra["extra"]["badge"] = self::_EXTRA_SCHEME[self::_EXTRA_VERSION]["badge"];

		return $extra;
	}

	/**
	 * Проверяем существует ли badge
	 *
	 * @param array $extra
	 *
	 * @return bool
	 */
	public static function isBadgeExist(array $extra):bool {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return isset($extra["extra"]["badge"]["content"]) && $extra["extra"]["badge"]["content"] != "";
	}

	/**
	 * Получить отключенные для пользователя группы логирования событий
	 *
	 * @param array $extra
	 *
	 * @return array
	 */
	public static function getDisabledAnalyticsEventGroupList(array $extra):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["user_disabled_analytics_event_group_list"];
	}

	/**
	 * Устанавливаем флаг удаления пользователя
	 */
	public static function setIsDeleted(array $extra, int $is_deleted):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["is_deleted"] = $is_deleted;

		return $extra;
	}

	/**
	 * Получаем флаг удаления пользователя
	 */
	public static function getIsDeleted(array $extra):bool {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["is_deleted"] == 1;
	}

	/**
	 * Сохраняем среднее экранное время пользователя
	 */
	public static function setAliasAvgScreenTime(array $extra, int $avg_screen_time):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["alias_avg_screen_time"] = $avg_screen_time;

		return $extra;
	}

	/**
	 * Получаем среднее экранное время пользователя
	 */
	public static function getAliasAvgScreenTime(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["alias_avg_screen_time"];
	}

	/**
	 * Сохраняем общее количество действий пользователя
	 */
	public static function setAliasTotalActionCount(array $extra, int $total_action_count):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["alias_total_action_count"] = $total_action_count;

		return $extra;
	}

	/**
	 * Получаем общее количество действий пользователя
	 */
	public static function getAliasTotalActionCount(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["alias_total_action_count"];
	}

	/**
	 * Сохраняем среднее время ответа пользователя на сообщения
	 */
	public static function setAliasAvgMessageAnswerTime(array $extra, int $avg_message_answer_time):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["alias_avg_message_answer_time"] = $avg_message_answer_time;

		return $extra;
	}

	/**
	 * Получаем среднее время ответа пользователя на сообщения
	 */
	public static function getAliasAvgMessageAnswerTime(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["alias_avg_message_answer_time"];
	}

	/**
	 * Устанавливаем время удаления аккаунта
	 *
	 * @param array $extra
	 * @param int   $dismissed_at
	 *
	 * @return array
	 */
	public static function setAliasDisabledAt(array $extra, int $dismissed_at):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["alias_disabled_at"] = $dismissed_at;

		return $extra;
	}

	/**
	 * Получаем время удаления аккаунта
	 *
	 * @param array $extra
	 *
	 * @return int
	 */
	public static function getAliasDisabledAt(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		return $extra["extra"]["alias_disabled_at"] ?? 0;
	}

	/**
	 * Проверяем, не удален ли аккаунт пользователя
	 * @throws AccountDeleted
	 */
	public static function assertIsNotDeleted(array $extra):void {

		if (self::getIsDeleted($extra)) {
			throw new AccountDeleted("member has deleted account");
		}
	}

	/**
	 * Получаем цвет аватарки
	 *
	 * @param array $extra
	 *
	 * @return int
	 */
	public static function getAvatarColorId(array $extra):int {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		// отдаем цвет badge
		return $extra["extra"]["avatar_color_id"] ?? 0;
	}

	/**
	 * Устанавливаем цвет аватарки
	 *
	 * @param array $extra
	 * @param int   $avatar_color_id
	 *
	 * @return array
	 */
	public static function setAvatarColorId(array $extra, int $avatar_color_id):array {

		// актуализируем структуру
		$extra = self::_getExtra($extra);

		$extra["extra"]["avatar_color_id"] = $avatar_color_id;

		return $extra;
	}

	/**
	 * Возвращает текущую структуру extra с default значениями
	 *
	 * @return array
	 */
	#[ArrayShape(["handler_version" => "int", "extra" => "array[]"])]
	public static function initExtra():array {

		return [
			"handler_version" => self::_EXTRA_VERSION,
			"extra"           => self::_EXTRA_SCHEME[self::_EXTRA_VERSION],
		];
	}

	// -------------------------------------------------------
	// PROTECTED
	// -------------------------------------------------------

	/**
	 * Актуализирует структуру extra
	 *
	 * @param array $extra
	 *
	 * @return array
	 */
	protected static function _getExtra(array $extra):array {

		// если extra не проинициализированна
		if (!isset($extra["handler_version"])) {

			// сливаем текущую версию extra и ту, что пришла
			$extra["extra"]           = array_merge(self::_EXTRA_SCHEME[self::_EXTRA_VERSION], []);
			$extra["handler_version"] = self::_EXTRA_VERSION;
		}

		// сравниваем версию пришедшей extra с текущей
		if ($extra["handler_version"] != self::_EXTRA_VERSION) {

			// сливаем текущую версию extra и ту, что пришла
			$extra["extra"]           = array_merge(self::_EXTRA_SCHEME[self::_EXTRA_VERSION], $extra["extra"]);
			$extra["handler_version"] = self::_EXTRA_VERSION;
		}

		return $extra;
	}

	/**
	 * получаем extra для возврата клиентам
	 */
	public static function formatMemberExtra(Main $member):array {

		$extra = [];

		// если системный бот
		if (\CompassApp\Domain\User\Main::isSystemBot($member->npc_type)) {
			$extra["subtype"] = (string) \CompassApp\Domain\User\Main::getSystemBotSubtype($member->npc_type);
		}

		return $extra;
	}
}