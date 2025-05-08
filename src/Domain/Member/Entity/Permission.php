<?php

namespace CompassApp\Domain\Member\Entity;

use CompassApp\Domain\Member\Exception\ActionNotAllowed;
use JetBrains\PhpStorm\Pure;
use BaseFrame\Exception\Domain\ParseFatalException;

/**
 * класс взаимодействия с группами пользователей
 *
 * группы в таблице company_data.member_list хранятся в виде результата побитового |(ИЛИ) всех id групп, в десятичном формате
 * этот результат называется маской групп (group_mask)
 *
 * Важно: все id прав, для корректной работы, ДОЛЖНЫ принимать значения в различных степенях двойки: 1, 2, 4, 8, 16
 */
class Permission {

	/**
	 * Ошибка, если нет прав для выполнения действия
	 */
	public const ACTION_NOT_ALLOWED_ERROR_CODE = 2238001;

	/**
	 * Легаси права
	 */
	public const FULL_LEGACY      = 1 << 0; // все права
	public const HR_LEGACY        = 1 << 1; // права для чата Найма и увольнения
	public const ADMIN_LEGACY     = 1 << 3; // админ групп и ботов
	public const DEVELOPER_LEGACY = 1 << 4; // права программиста ботов

	/** @var array[] Правила конвертации старых прав в новые */
	public const CONVERT_PERMISSIONS_RULES = [

		Permission::FULL_LEGACY => [
			Permission::GROUP_ADMINISTRATOR,
			Permission::BOT_MANAGEMENT,
			Permission::MESSAGE_DELETE,
			Permission::MEMBER_PROFILE_EDIT,
			Permission::MEMBER_INVITE,
			Permission::MEMBER_KICK,
			Permission::SPACE_SETTINGS_LEGACY,
			Permission::SPACE_SETTINGS,
			Permission::ADMINISTRATOR_MANAGEMENT,
		],

		Permission::HR_LEGACY => [
			Permission::MEMBER_INVITE,
			Permission::MEMBER_KICK,
			Permission::MEMBER_PROFILE_EDIT,
		],

		Permission::DEVELOPER_LEGACY => [
			Permission::BOT_MANAGEMENT,
		],

		Permission::ADMIN_LEGACY => [
			Permission::ADMINISTRATOR_MANAGEMENT,
		],
	];

	/**
	 * Легаси права маски permissions
	 */
	public const SPACE_SETTINGS_LEGACY = 1 << 6;

	/**
	 * Список легаси прав, которые пропускаем при выдаче, если нет для них вывода клиентам
	 */
	protected const _LEGACY_PERMISSION_LIST          = [
		self::SPACE_SETTINGS_LEGACY,
	];

	/**
	 * Права администратора для маски permissions
	 */
	public const DEFAULT                             = 0;
	public const    GROUP_ADMINISTRATOR              = 1 << 0; // администратор всех групп
	public const    BOT_MANAGEMENT                   = 1 << 1; // управление ботами
	public const    MESSAGE_DELETE                   = 1 << 2; // удаление сообщений
	public const    MEMBER_PROFILE_EDIT              = 1 << 3; // изменения профиля участника
	public const    MEMBER_INVITE                    = 1 << 4; // приглашение участников
	public const    MEMBER_KICK                      = 1 << 5; // удаление участников
	public const    ADMINISTRATOR_MANAGEMENT         = 1 << 7; // управление администраторами
	public const    SPACE_SETTINGS                   = 1 << 8; // настройка и удаление пространства
	public const    ADMINISTRATOR_STATISTIC_INFINITE = 1 << 9; // скрытие статистики администратора в пространстве

	/**
	 * Права ограничивающие настройки полей в карточке у пользователя
	 */
	public const RESTRICT_BADGE_PROFILE_EDIT       = 1 << 10; // запрет изменения бейджа в своей карточке пользователя
	public const RESTRICT_STATUS_PROFILE_EDIT      = 1 << 11; // запрет изменения статуса в своей карточке пользователя
	public const RESTRICT_DESCRIPTION_PROFILE_EDIT = 1 << 12; // запрет изменения описания в своей карточке пользователя

	public const HIDDEN_READ_MESSAGE_STATUS = 1 << 13; // скрывать статус о прочтении

	// разрешенные права для установки и получения
	public const ALLOWED_PERMISSION_LIST = [
		self::GROUP_ADMINISTRATOR,
		self::BOT_MANAGEMENT,
		self::MESSAGE_DELETE,
		self::MEMBER_PROFILE_EDIT,
		self::MEMBER_INVITE,
		self::MEMBER_KICK,
		self::SPACE_SETTINGS_LEGACY,
		self::SPACE_SETTINGS,
		self::ADMINISTRATOR_MANAGEMENT,
		self::SPACE_SETTINGS,
		self::ADMINISTRATOR_STATISTIC_INFINITE,
		self::RESTRICT_BADGE_PROFILE_EDIT,
		self::RESTRICT_STATUS_PROFILE_EDIT,
		self::RESTRICT_DESCRIPTION_PROFILE_EDIT,
		self::HIDDEN_READ_MESSAGE_STATUS,
	];

	// разрешенные права для установки себе
	public const ALLOWED_SELF_PERMISSION_LIST = [
		self::GROUP_ADMINISTRATOR,
		self::BOT_MANAGEMENT,
		self::MESSAGE_DELETE,
		self::MEMBER_PROFILE_EDIT,
		self::MEMBER_INVITE,
		self::MEMBER_KICK,
		self::SPACE_SETTINGS_LEGACY,
		self::SPACE_SETTINGS,
		self::ADMINISTRATOR_STATISTIC_INFINITE,
		self::HIDDEN_READ_MESSAGE_STATUS,
	];

	// список прав ограничивающих настройки полей в карточке пользователем у себя
	public const ALLOWED_PERMISSION_PROFILE_CARD_LIST = [
		self::RESTRICT_BADGE_PROFILE_EDIT,
		self::RESTRICT_STATUS_PROFILE_EDIT,
		self::RESTRICT_DESCRIPTION_PROFILE_EDIT,
	];

	/**
	 * Права, определяющие владельца пространства.
	 */
	public const OWNER_PERMISSION_LIST = [
		self::GROUP_ADMINISTRATOR,
		self::BOT_MANAGEMENT,
		self::MESSAGE_DELETE,
		self::MEMBER_PROFILE_EDIT,
		self::MEMBER_INVITE,
		self::MEMBER_KICK,
		self::SPACE_SETTINGS_LEGACY,
		self::ADMINISTRATOR_MANAGEMENT,
		self::SPACE_SETTINGS,
	];

	public const CURRENT_PERMISSIONS_OUTPUT_SCHEMA_VERSION = 2;

	public const PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION = [
		1 => [
			self::GROUP_ADMINISTRATOR              => "group_administrator",
			self::BOT_MANAGEMENT                   => "bot_management",
			self::MESSAGE_DELETE                   => "message_delete",
			self::MEMBER_PROFILE_EDIT              => "member_profile_edit",
			self::MEMBER_INVITE                    => "member_invite",
			self::MEMBER_KICK                      => "member_kick",
			self::SPACE_SETTINGS_LEGACY            => "space_settings",
			self::ADMINISTRATOR_MANAGEMENT         => "administrator_management",
			self::SPACE_SETTINGS                   => "space_delete",
			self::ADMINISTRATOR_STATISTIC_INFINITE => "administrator_statistic_infinite",
			self::HIDDEN_READ_MESSAGE_STATUS       => "hidden_read_message_status",
		],
		2 => [
			self::GROUP_ADMINISTRATOR              => "group_administrator",
			self::BOT_MANAGEMENT                   => "bot_management",
			self::MESSAGE_DELETE                   => "message_delete",
			self::MEMBER_PROFILE_EDIT              => "member_profile_edit",
			self::MEMBER_INVITE                    => "member_invite",
			self::MEMBER_KICK                      => "member_kick",
			self::SPACE_SETTINGS                   => "space_settings",
			self::ADMINISTRATOR_MANAGEMENT         => "administrator_management",
			self::ADMINISTRATOR_STATISTIC_INFINITE => "administrator_statistic_infinite",
			self::HIDDEN_READ_MESSAGE_STATUS       => "hidden_read_message_status",
		],
	];

	// список прав ограничивающих настройки полей в карточке пользователем у себя
	protected const _PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA = [
		self::RESTRICT_BADGE_PROFILE_EDIT       => "restrict_badge_profile_edit",
		self::RESTRICT_STATUS_PROFILE_EDIT      => "restrict_status_profile_edit",
		self::RESTRICT_DESCRIPTION_PROFILE_EDIT => "restrict_description_profile_edit",
	];

	/**
	 * Ограничение для всех участников
	 */
	public const IS_DOWNLOAD_VIDEO_ENABLED      = "is_download_video_enabled";
	public const IS_REPOST_MESSAGE_ENABLED      = "is_repost_message_enabled";
	public const IS_VOICE_MESSAGE_ENABLED       = "is_voice_message_enabled";
	public const IS_SHOW_COMPANY_MEMBER_ENABLED = "is_show_company_member_enabled";
	public const IS_SET_MEMBER_PROFILE_ENABLED  = "is_set_member_profile_enabled";
	public const IS_SHOW_GROUP_MEMBER_ENABLED   = "is_show_group_member_enabled";
	public const IS_GET_REACTION_LIST_ENABLED   = "is_get_reaction_list_enabled";
	public const IS_ADD_SINGLE_ENABLED          = "is_add_single_enabled";
	public const IS_ADD_GROUP_ENABLED           = "is_add_group_enabled";
	public const IS_CALL_ENABLED                = "is_call_enabled";
	public const IS_MEDIA_CONFERENCE_ENABLED    = "is_media_conference_enabled";
	public const IS_READ_MESSAGE_STATUS_SHOWN   = "is_read_message_status_shown";

	/**
	 * Добавить права в маску
	 *
	 * @param int   $permission_mask
	 * @param array $permission_list
	 *
	 * @return int
	 */
	public static function addPermissionListToMask(int $permission_mask, array $permission_list):int {

		foreach ($permission_list as $permission) {

			// выполняем побитовое ИЛИ для включения новой группы в маску групп
			$permission_mask = $permission_mask | $permission;
		}

		return $permission_mask;
	}

	/**
	 * Удалить список прав из маски
	 *
	 * @param int   $permission_mask
	 * @param array $permission_list
	 *
	 * @return int
	 */
	#[Pure] public static function removePermissionListFromMask(int $permission_mask, array $permission_list):int {

		foreach ($permission_list as $permission) {

			if (self::hasPermission($permission_mask, $permission)) {

				// Выполняем исключающее ИЛИ для исключения группы из маски групп
				$permission_mask ^= $permission;
			}
		}

		return $permission_mask;
	}

	/**
	 * Проверить, есть ли у участника права
	 *
	 * @param int $permission_mask
	 * @param int $permission
	 *
	 * @return bool
	 */
	public static function hasPermission(int $permission_mask, int $permission):bool {

		return $permission_mask & $permission;
	}

	/**
	 * Проверить, есть ли у участник список прав
	 *
	 * @param int   $permission_mask
	 * @param array $permission_list
	 *
	 * @return bool
	 */
	public static function hasPermissionList(int $permission_mask, array $permission_list):bool {

		return array_reduce($permission_list, function(int $current, int $permission) use ($permission_mask) {

			return $current && ($permission_mask & $permission);
		}, true);
	}

	/**
	 * Трансформировать id группы, чтобы его можно было использовать в маске
	 *
	 * @param int $permission
	 *
	 * @return int
	 */
	public static function transformForMask(int $permission):int {

		return 1 << $permission;
	}

	/**
	 * Получить список групп пользователя
	 *
	 * @param int $permission_mask
	 *
	 * @return array
	 */
	public static function getPermissionList(int $permission_mask):array {

		$permission_list = [];

		// превращаем двоичное число в строку, чтобы узнать группы пользователя
		$binary = strrev(decbin($permission_mask));

		for ($i = 0; $i < strlen($binary); $i++) {

			if ((int) $binary[$i]) {

				$permission_list[] = self::transformForMask($i);
			}
		}

		return $permission_list;
	}

	/**
	 * Получить легаси список групп пользователя
	 *
	 * @param int $permission_mask
	 *
	 * @return array
	 */
	public static function getPermissionListLegacy(int $permission_mask):array {

		$permissions = self::DEFAULT;

		// для каждого права смотрим, можно ли обратно его сконвертировать, и конвертим, если да
		foreach (self::CONVERT_PERMISSIONS_RULES as $old_permission => $new_permissions) {

			if (Permission::hasPermissionList($permission_mask, $new_permissions)) {
				$permissions = Permission::addPermissionListToMask($permissions, [$old_permission]);
			}
		}

		$permission_list = [];

		// превращаем двоичное число в строку, чтобы узнать группы пользователя
		$binary = strrev(decbin($permissions));

		for ($i = 0; $i < strlen($binary); $i++) {

			if ((int) $binary[$i]) {

				$permission_list[] = $i;
			}
		}

		return $permission_list;
	}

	/**
	 * Получить список прав участника для клиента
	 *
	 * @param int $role
	 * @param int $permission_mask
	 * @param int $version
	 *
	 * @return array
	 * @long
	 */
	public static function formatToOutput(int $role, int $permission_mask, int $version = self::CURRENT_PERMISSIONS_OUTPUT_SCHEMA_VERSION):array {

		$permissions     = [];
		$permission_list = self::getPermissionList($permission_mask);

		// вычисляем отсутствующие права у участника
		$missing_permission_list = array_diff(self::ALLOWED_PERMISSION_LIST, $permission_list);

		// включаем разрешенные права
		foreach ($permission_list as $permission) {

			// если права из карточки, то пропускаем (отдаются в самой карточке пользователя)
			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				continue;
			}

			if (!isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission]) && $permission) {
				continue;
			}

			// если не являемся администратором - прав у нас вообще нет :(
			$permissions[self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission]] = $role === Member::ROLE_ADMINISTRATOR ? 1 : 0;
		}

		// отключаем запрещенные права
		foreach ($missing_permission_list as $permission) {

			// если права из карточки, то пропускаем (отдаются в самой карточке пользователя)
			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				continue;
			}

			if (!isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission])) {
				continue;
			}
			$permissions[self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission]] = 0;
		}

		return $permissions;
	}

	/**
	 * Получить список прав в карточке профиля для клиента
	 *
	 * @param int $permission_mask
	 *
	 * @return array
	 */
	public static function formatProfileCardToOutput(int $permission_mask):array {

		$permissions     = [];
		$permission_list = self::getPermissionList($permission_mask);

		// оставляем только права карточки
		$permission_list = array_intersect(self::ALLOWED_PERMISSION_PROFILE_CARD_LIST, $permission_list);

		// вычисляем отсутствующие права у участника
		$missing_permission_list = array_diff(self::ALLOWED_PERMISSION_PROFILE_CARD_LIST, $permission_list);

		// включаем разрешенные права
		foreach ($permission_list as $permission) {

			if (!isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				continue;
			}

			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				$permissions[self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]] = 1;
			}
		}

		// отключаем запрещенные права
		foreach ($missing_permission_list as $permission) {

			if (!isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				continue;
			}

			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				$permissions[self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]] = 0;
			}
		}

		return $permissions;
	}

	/**
	 * Получить список прав с правами карточки
	 *
	 * @param int $role
	 * @param int $permission_mask
	 *
	 * @return array
	 * @throws ParseFatalException
	 * @long
	 */
	public static function formatWithProfileCardToOutput(int $permission_mask, int $version = self::CURRENT_PERMISSIONS_OUTPUT_SCHEMA_VERSION):array {

		$permissions     = [];
		$permission_list = self::getPermissionList($permission_mask);

		// вычисляем отсутствующие права у участника
		$missing_permission_list = array_diff(self::ALLOWED_PERMISSION_LIST, $permission_list);

		// включаем разрешенные права
		foreach ($permission_list as $permission) {

			if (!isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]) && !isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission])) {
				continue;
			}

			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				$permissions[self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]] = 1;
			}

			if (isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission])) {
				$permissions[self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission]] = 1;
			}
		}

		// отключаем запрещенные права
		foreach ($missing_permission_list as $permission) {

			if (!isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]) && !isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission])) {
				continue;
			}

			if (isset(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission])) {
				$permissions[self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA[$permission]] = 0;
			}

			if (isset(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission])) {
				$permissions[self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version][$permission]] = 0;
			}
		}

		return $permissions;
	}

	/**
	 * Форматируем в список из запроса клиента
	 *
	 * @param array $permissions
	 *
	 * @return array
	 */
	public static function formatToList(array $permissions, int $version = self::CURRENT_PERMISSIONS_OUTPUT_SCHEMA_VERSION):array {

		$enabled_permission_list  = [];
		$disabled_permission_list = [];

		// переворачиваем массив со схемой прав, чтобы получить значения для маски
		$flipped_permission_schema = array_flip(self::PERMISSIONS_OUTPUT_SCHEMA_BY_VERSION[$version]);

		foreach ($permissions as $permission => $value) {

			// если такого права в схеме нет, пропускаем
			if (!isset($flipped_permission_schema[$permission])) {
				continue;
			}

			// если право включено - добавляем в маску
			if ($value === 1) {

				$enabled_permission_list[] = $flipped_permission_schema[$permission];
				continue;
			}

			if ($value === 0) {
				$disabled_permission_list[] = $flipped_permission_schema[$permission];
			}
		}

		return [$enabled_permission_list, $disabled_permission_list];
	}

	/**
	 * Форматируем в список из запроса клиента для прав в карточке пользователя
	 *
	 * @param array $permissions
	 *
	 * @return array
	 */
	public static function formatProfileCardToList(array $permissions):array {

		$enabled_permission_list  = [];
		$disabled_permission_list = [];

		// переворачиваем массив со схемой прав, чтобы получить значения для маски
		$flipped_permission_schema = array_flip(self::_PERMISSIONS_PROFILE_CARD_OUTPUT_SCHEMA);

		foreach ($permissions as $permission => $value) {

			// если такого права в схеме нет, пропускаем
			if (!isset($flipped_permission_schema[$permission])) {
				continue;
			}

			// если право включено - добавляем в маску
			if ($value === 1) {

				$enabled_permission_list[] = $flipped_permission_schema[$permission];
				continue;
			}

			if ($value === 0) {
				$disabled_permission_list[] = $flipped_permission_schema[$permission];
			}
		}

		return [$enabled_permission_list, $disabled_permission_list];
	}

	/**
	 * Необходимо ли вступление в диалог найма и увольнения по group_mask
	 *
	 * @param int $role
	 * @param int $actual_group_mask
	 * @param int $before_group_mask
	 *
	 * @return bool
	 */
	#[Pure] public static function isJoinHiringConversationByPermissionMask(int $role, int $actual_group_mask, int $before_group_mask):bool {

		// если пользователь ранее не имел прав приглашать и увольнять, а теперь имеет одно из этих прав
		if ((self::hasPermission($actual_group_mask, self::MEMBER_INVITE) || self::hasPermission($actual_group_mask, self::MEMBER_KICK))
			&& !self::hasPermission($before_group_mask, self::MEMBER_INVITE) && !self::hasPermission($before_group_mask, self::MEMBER_KICK)) {

			if ($role == Member::ROLE_ADMINISTRATOR) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Необходимо ли покидание диалога найма и увольнения
	 *
	 * @param int $actual_group_mask
	 * @param int $before_group_mask
	 *
	 * @return bool
	 */
	#[Pure] public static function isKickedHiringConversationByPermissionMask(int $actual_group_mask, int $before_group_mask):bool {

		// если пользователь ранее имел права приглашать или увольнять, а теперь не имеет ни одно из этих прав
		if (!self::hasPermission($actual_group_mask, self::MEMBER_INVITE) && !self::hasPermission($actual_group_mask, self::MEMBER_KICK)
			&& (self::hasPermission($before_group_mask, self::MEMBER_INVITE) || self::hasPermission($before_group_mask, self::MEMBER_KICK))) {
			return true;
		}

		return false;
	}

	/**
	 * Проверяем, может ли участник изменять настройки пространства
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canEditSpaceSettings(int $role, int $permissions):bool {

		if ($role === Member::ROLE_ADMINISTRATOR && self::hasPermission($permissions, self::SPACE_SETTINGS)) {
			return true;
		}

		return false;
	}

	/**
	 * Выбрасываем исключение, если пользовать не может редактировать настройки компании
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @throws ActionNotAllowed
	 */
	public static function assertCanEditSpaceSettings(int $role, int $permissions):void {

		if (!self::canEditSpaceSettings($role, $permissions)) {
			throw new ActionNotAllowed("can't edit space settings");
		}
	}

	/**
	 * Проверить, может ли пользователь приглашать в пространство
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canInviteMember(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::MEMBER_INVITE);
	}

	/**
	 * Выбрасываем исключение если пользователь не имеет доступа к найму
	 *
	 * @param int $role
	 * @param int $user_permissions
	 *
	 * @throws ActionNotAllowed
	 */
	public static function assertCanInviteMember(int $role, int $user_permissions):void {

		if (!self::canInviteMember($role, $user_permissions)) {
			throw new ActionNotAllowed("can't invite member");
		}
	}

	/**
	 * Проверить, может ли пользователь удалять из пространства
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canKickMember(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::MEMBER_KICK);
	}

	/**
	 * Выбрасываем исключение если пользователь не имеет доступа к увольнению
	 *
	 * @param int $role
	 * @param int $user_permissions
	 *
	 * @throws ActionNotAllowed
	 */
	public static function assertCanKickMember(int $role, int $user_permissions):void {

		if (!self::canKickMember($role, $user_permissions)) {
			throw new ActionNotAllowed("can't kick member");
		}
	}

	/**
	 * Проверяем, является пользователь администратором всех групп
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function isGroupAdministrator(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::GROUP_ADMINISTRATOR);
	}

	/**
	 * Проверяем что пользователь может менять профиль другого пользователя
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	#[Pure]
	public static function canEditMemberProfile(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::MEMBER_PROFILE_EDIT);
	}

	/**
	 * Утверждаем, что пользователь может менять профиль другого пользователя
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @throws ActionNotAllowed
	 */
	public static function assertCanEditMemberProfile(int $role, int $permissions):void {

		if (!self::canEditMemberProfile($role, $permissions)) {
			throw new ActionNotAllowed("can't edit member profile");
		}
	}

	/**
	 * проверяем, имеет ли пользователь права программиста бота
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canManageBots(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::BOT_MANAGEMENT);
	}

	/**
	 * Выбрасываем исключение, если пользователь не имеет прав программиста бота
	 *
	 * @throws ActionNotAllowed
	 */
	public static function assertCanManageBots(int $role, int $permissions):void {

		// проверяем что пользователь владелец компании
		if (!self::canManageBots($role, $permissions)) {
			throw new ActionNotAllowed("can't manage bots");
		}
	}

	/**
	 * Может ли управлять администраторами
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canManageAdministrators(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::ADMINISTRATOR_MANAGEMENT);
	}

	/**
	 * Выбрасываем исключение, если участник не может управлять администраторами
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return void
	 * @throws ActionNotAllowed
	 */
	public static function assertCanManageAdministrators(int $role, int $permissions):void {

		if (!self::canManageAdministrators($role, $permissions)) {
			throw new ActionNotAllowed("can't manage administrators");
		}
	}

	/**
	 * Может ли удалить пространство
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canDeleteSpace(int $role, int $permissions):bool {

		$has_permission = self::hasPermission($permissions, self::SPACE_SETTINGS);

		return $role === Member::ROLE_ADMINISTRATOR && $has_permission;
	}

	/**
	 * Может ли удалить пространство
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return void
	 */
	public static function assertCanDeleteSpace(int $role, int $permissions):void {

		if (!self::canDeleteSpace($role, $permissions)) {
			throw new ActionNotAllowed("can't manage administrators");
		}
	}

	/**
	 * Может ли удалить пространство
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function canDeleteMessage(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::MESSAGE_DELETE);
	}

	/**
	 * Может ли удалить пространство
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return void
	 */
	public static function assertCanDeleteMessage(int $role, int $permissions):void {

		if (!self::canDeleteMessage($role, $permissions)) {
			throw new ActionNotAllowed("can't delete message");
		}
	}

	/**
	 * Отображать фейковую или реальную статистику администратора
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function isAdministratorStatisticInfinite(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::ADMINISTRATOR_STATISTIC_INFINITE);
	}

	/**
	 * Форматируем новые права в легаси (используется для экшна member)
	 *
	 * @param array $permission_list
	 *
	 * @return array
	 */
	public static function convertPermissionListToLegacy(array $permission_list):array {

		$legacy_permission_list = [];

		foreach ($permission_list as $permission) {

			$legacy_permission = match ($permission) {
				self::MEMBER_KICK, self::MEMBER_INVITE => self::HR_LEGACY,
				self::BOT_MANAGEMENT => self::DEVELOPER_LEGACY,
				self::GROUP_ADMINISTRATOR => self::ADMIN_LEGACY,
				default => false,
			};

			if ($legacy_permission !== false) {
				$legacy_permission_list[$legacy_permission] = true;
			}
		}

		return array_keys($legacy_permission_list);
	}

	/**
	 * проверяем что права которые можно изменять себя
	 */
	public static function isCanSelfPermissionList(array $permission_list):bool {

		if (count(array_diff($permission_list, self::ALLOWED_SELF_PERMISSION_LIST)) > 0) {
			return false;
		}

		return true;
	}

	/**
	 * Проверяет, что указанный набор прав соответствует правам владельца.
	 */
	public static function hasOwnerPermissions(int $permission_mask):bool {

		return static::hasPermissionList($permission_mask, static::OWNER_PERMISSION_LIST);
	}

	/**
	 * Проверяет наличие хотя бы одного права из списка
	 */
	public static function hasOneFromPermissionList(int $permission_mask, array $permission_list):bool {

		foreach ($permission_list as $permission) {

			if (static::hasPermission($permission_mask, $permission)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Проверяем, можно ли менять права в карточке другому администратору
	 * нельзя менять права в карточке другому администратору, если у того есть права:
	 * member_profile_edit, administrator_management, space_settings, space_settings_legacy
	 */
	public static function assertSetPermissionsProfileCardAnotherAdministrator(\CompassApp\Domain\Member\Struct\Main $member):void {

		if ($member->role !== Member::ROLE_ADMINISTRATOR) {
			return;
		}

		if (Permission::hasOneFromPermissionList($member->permissions, [self::MEMBER_PROFILE_EDIT, self::ADMINISTRATOR_MANAGEMENT, self::SPACE_SETTINGS])) {
			throw new \CompassApp\Domain\Member\Exception\PermissionNotAllowedSetAnotherAdministrator("permission can't be set to another administrator");
		}
	}

	/**
	 * Проверяем, что у пользователя скрыт статус прочитанных сообщений
	 *
	 * @param int $role
	 * @param int $permissions
	 *
	 * @return bool
	 */
	public static function isReadMessageStatusHidden(int $role, int $permissions):bool {

		return $role === Member::ROLE_ADMINISTRATOR && Permission::hasPermission($permissions, Permission::HIDDEN_READ_MESSAGE_STATUS);
	}
}
