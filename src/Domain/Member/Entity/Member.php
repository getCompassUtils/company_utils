<?php

namespace CompassApp\Domain\Member\Entity;

use BaseFrame\Domain\User\Avatar;
use BaseFrame\Exception\Domain\ParseFatalException;
use CompassApp\Domain\Member\Exception\UserIsGuest;
use CompassApp\Domain\Member\Exception\IsLeft;
use CompassApp\Domain\Member\Struct\Main;
use JetBrains\PhpStorm\Pure;
use CompassApp\Domain\Member\Exception\IsAdministrator;
use CompassApp\Domain\Member\Exception\IsNotAdministrator;
use CompassApp\Gateway\Bus\CompanyCache;

/**
 * класс для взаимодействия с участниками компании
 */
class Member {

	public const ROLE_LEFT          = 0; // статус пользователя в пространстве - покинувший
	public const ROLE_MEMBER        = 1; // статус пользователя в пространстве - рядовой участник
	public const ROLE_ADMINISTRATOR = 2; // статус пользователя в пространстве - администратор пространства
	public const ROLE_USERBOT       = 3; // роль пользовательского бота
	public const ROLE_GUEST         = 4; // статус пользователя в пространстве - гость

	// роли для ответа клиентам
	protected const _ROLE_OUTPUT_SCHEMA = [
		self::ROLE_LEFT          => "left",
		self::ROLE_ADMINISTRATOR => "administrator",
		self::ROLE_MEMBER        => "member",
		self::ROLE_USERBOT       => "userbot",
		self::ROLE_GUEST         => "guest",
	];

	/**
	 * роли, являющиеся полноценными в пространстве (сюда относятся member, administrator)
	 */
	public const SPACE_RESIDENT_ROLE_LIST = [
		self::ROLE_MEMBER,
		self::ROLE_ADMINISTRATOR,
	];

	public const LEAVE_REASON_BLOCKED_IN_SYSTEM = "blocked_in_system"; // заблокирован в системе
	public const LEAVE_REASON_KICKED            = "dismissed";         // исключен из пространства
	public const LEAVE_REASON_LEAVE             = "leave";             // сам ушел

	// наименования списков ролей для 1 версии прав
	public const USER_ROLE_LIST_NAMES_V1 = [
		self::ROLE_ADMINISTRATOR => "owner_list",
		self::ROLE_MEMBER        => "default_list",
		self::ROLE_LEFT          => "kicked_list",
	];

	// разрешенные для установки роли
	public const ALLOWED_SET_ROLES = [
		self::ROLE_MEMBER,
		self::ROLE_ADMINISTRATOR,
	];

	// разрешенные для получения списка
	public const ALLOWED_FOR_GET_LIST = [
		self::ROLE_MEMBER,
		self::ROLE_ADMINISTRATOR,
	];

	/**
	 * участник ли пользователь компании
	 *
	 * @param int $user_id
	 *
	 * @throws \cs_UserIsNotMember
	 */
	public static function assertIsMember(int $user_id):void {

		try {
			$member_row = CompanyCache::getMember($user_id);
		} catch (\cs_RowIsEmpty) {
			throw new \cs_UserIsNotMember();
		}

		if ($member_row->role == self::ROLE_LEFT) {
			throw new \cs_UserIsNotMember();
		}
	}

	/**
	 * Проверяем что пользователь пространства имеет ожидаемую роль
	 *
	 * @throws \Throwable
	 */
	public static function assertRole(Main $member, int $expected_role, \Throwable $exception):void {

		if ($member->role !== $expected_role) {
			throw $exception;
		}
	}

	/**
	 * получаем НЕзаблокированных в системе пользователей
	 *
	 * @param array $user_info_list
	 * @param bool  $is_need_grouped
	 *
	 * @return array
	 */
	#[Pure] public static function getNotDisabledUsers(array $user_info_list, bool $is_need_grouped = false):array {

		$not_disabled_user_list = [];
		foreach ($user_info_list as $v) {

			// если пользователь заблокирован, то пропускаем
			if (self::isDisabledProfile($v->role)) {
				continue;
			}

			if ($is_need_grouped) {

				$not_disabled_user_list[$v->user_id] = $v;
				continue;
			}
			$not_disabled_user_list[] = $v;
		}

		return $not_disabled_user_list;
	}

	/**
	 * проверяем, был ли пользователь исключен из компании.
	 *
	 * @param int $role
	 *
	 * @return bool
	 */
	public static function isDisabledProfile(int $role):bool {

		return $role == self::ROLE_LEFT;
	}

	/**
	 * получаем заблокированных в системе пользователей
	 *
	 * @param array $user_info_list
	 * @param bool  $is_need_grouped
	 *
	 * @return array
	 */
	#[Pure] public static function getDisabledUsers(array $user_info_list, bool $is_need_grouped = false):array {

		$not_disabled_user_list = [];
		foreach ($user_info_list as $v) {

			// если пользователь НЕзаблокирован, то пропускаем
			if (!self::isDisabledProfile($v->role)) {
				continue;
			}

			if ($is_need_grouped) {

				$not_disabled_user_list[$v->user_id] = $v;
				continue;
			}
			$not_disabled_user_list[] = $v;
		}

		return $not_disabled_user_list;
	}

	/**
	 * Выбрасываем исключение если пользователь не администратор компании
	 *
	 * @param int $user_status
	 *
	 * @throws IsNotAdministrator
	 */
	public static function assertUserAdministrator(int $user_status):void {

		// проверяем что пользователь владелец компании
		if ($user_status != self::ROLE_ADMINISTRATOR) {
			throw new IsNotAdministrator("user is not administrator");
		}
	}

	/**
	 * Выбрасываем исключение если пользователь владелец компании
	 *
	 * @param int $user_status
	 *
	 * @throws IsAdministrator
	 */
	public static function assertUserNotAdministrator(int $user_status):void {

		// проверяем что пользователь владелец компании
		if ($user_status === self::ROLE_ADMINISTRATOR) {
			throw new IsAdministrator("user is administrator");
		}
	}

	/**
	 * Выбрасываем исключение если пользователь гость пространства
	 *
	 * @param int $user_role
	 *
	 * @throws UserIsGuest
	 */
	public static function assertUserNotGuest(int $user_role):void {

		// проверяем что пользователь гость пространства
		if ($user_role === self::ROLE_GUEST) {
			throw new UserIsGuest("user is guest");
		}
	}

	/**
	 * выбрасываем ошибку, если у пользователя роль кикнутого из компании
	 *
	 * @param int $role
	 *
	 * @throws IsLeft
	 */
	public static function assertIsNotLeftRole(int $role):void {

		if ($role == self::ROLE_LEFT) {
			throw new IsLeft("member has left");
		}
	}

	/**
	 * Выбрасываем исключение, если пользователь пытается изменить свою роль
	 *
	 * @param int $user_id
	 * @param int $member_id
	 *
	 * @throws \cs_UserChangeSelfRole
	 */
	public static function assertUserChangeSelfRole(int $user_id, int $member_id):void {

		if ($user_id == $member_id) {

			throw new \cs_UserChangeSelfRole();
		}
	}

	/**
	 * Выбрасываем исключение если роли нет в списке доступных для установки
	 *
	 * @param int $role
	 *
	 * @throws \cs_CompanyUserIncorrectRole
	 */
	public static function assertUserNotAllowedRole(int $role):void {

		// проверяем что роль есть в списке доступных для установки
		if (!in_array($role, self::ALLOWED_SET_ROLES)) {
			throw new \cs_CompanyUserIncorrectRole();
		}
	}

	/**
	 * получаем идентификаторы всех участников, из выбранных групп
	 *
	 * @param Main[] $member_list
	 *
	 * @return array
	 */
	public static function getUserIdListFromMemberStruct(array $member_list):array {

		return array_map(function(Main $member) {

			return $member->user_id;
		}, $member_list);
	}

	/**
	 * Понижен ли сотруднки до обычного
	 *
	 * @param int $actual_role
	 * @param int $before_role
	 *
	 * @return bool
	 */
	public static function isDownshiftToDefault(int $actual_role, int $before_role):bool {

		// если назначен обычным сотрудником
		if ($before_role != self::ROLE_MEMBER && $actual_role === self::ROLE_MEMBER) {
			return true;
		}

		return false;
	}

	/**
	 * Необходимо ли покидание диалога найма и увольнения
	 *
	 * @param int $actual_role
	 *
	 * @return bool
	 */
	public static function isKickedHiringConversation(int $actual_role):bool {

		// если назначен обычным сотрудником
		if ($actual_role === self::ROLE_MEMBER) {
			return true;
		}

		// если уволен
		if ($actual_role === self::ROLE_LEFT) {
			return true;
		}

		return false;
	}

	/**
	 * Возвращает роль пользователя для клиентов
	 *
	 * @param int $role
	 *
	 * @return string
	 * @throws ParseFatalException
	 */
	public static function getRoleOutputType(int $role):string {

		if (!isset(self::_ROLE_OUTPUT_SCHEMA[$role])) {
			throw new ParseFatalException("there is no format output for role {$role}");
		}

		return self::_ROLE_OUTPUT_SCHEMA[$role];
	}

	/**
	 * Возвращает роль пользователя, пришедшую от клиентов
	 *
	 * @param string $role
	 *
	 * @return string
	 */
	public static function formatRoleToInt(string $role):string {

		$flipped_role_output_schema = array_flip(self::_ROLE_OUTPUT_SCHEMA);

		// если роли не нашли, то возвращаем ошибку
		if (!isset($flipped_role_output_schema[$role])) {
			throw new \cs_CompanyUserIncorrectRole();
		}

		return $flipped_role_output_schema[$role];
	}

	/**
	 * Возвращает тип пользователя для фронта на основе его npc_type
	 *
	 * @param string $user_type
	 *
	 * @return string
	 * @throws ParseFatalException
	 */
	public static function getUserOutputType(string $user_type):string {

		if (!isset(\CompassApp\Domain\User\Main::USER_TYPE_SCHEMA[$user_type])) {
			throw new ParseFatalException("there is no format output for npc type {$user_type}");
		}

		return \CompassApp\Domain\User\Main::USER_TYPE_SCHEMA[$user_type];
	}

	/**
	 * Форматируем данные о пользователе
	 *
	 * @param Main $member
	 *
	 * @return array
	 * @throws ParseFatalException
	 * @long большая структура для сущности
	 */
	public static function formatMember(Main $member):array {

		$legacy_role = $member->role;

		// если пользователь не имеет всех прав - для легаси клиента он участник обыкновенный
		if ($member->role === Member::ROLE_ADMINISTRATOR && !Permission::hasOwnerPermissions($member->permissions)) {
			$legacy_role = Member::ROLE_MEMBER;
		}

		$avg_screen_time         = Extra::getAliasAvgScreenTime($member->extra);
		$total_action_count      = Extra::getAliasTotalActionCount($member->extra);
		$avg_message_answer_time = Extra::getAliasAvgMessageAnswerTime($member->extra);

		if (Extra::getIsDeleted($member->extra)) {

			$avg_screen_time         = 0;
			$total_action_count      = 0;
			$avg_message_answer_time = 0;
		}

		// права админа приоритетнее
		if (Permission::isAdministratorStatisticInfinite($member->role, $member->permissions)) {

			$avg_screen_time         = -1;
			$total_action_count      = -1;
			$avg_message_answer_time = -1;
		}

		$avatar_color_id = Extra::getAvatarColorId($member->extra);
		$avatar_color_id = $avatar_color_id === 0 ? Avatar::getColorByUserId($member->user_id) : $avatar_color_id;

		$output = [
			"user_id"                 => (int) $member->user_id,
			"full_name"               => (string) $member->full_name,
			"full_name_updated_at"    => (int) $member->full_name_updated_at,
			"mbti_type"               => (string) $member->mbti_type,
			"description"             => (string) $member->short_description,
			"company_joined_at"       => (int) $member->company_joined_at,
			"status"                  => (string) $member->comment,
			"role_name"               => (string) self::getRoleOutputType($member->role),
			"type"                    => (string) self::getUserOutputType(\CompassApp\Domain\User\Main::getUserType($member->npc_type)),
			"badge"                   => (array) [
				"color_id" => (int) Extra::getBadgeColor($member->extra),
				"content"  => (string) Extra::getBadgeContent($member->extra),
			],
			"dismissed_at"            => (int) $member->left_at,
			"is_account_deleted"      => Extra::getIsDeleted($member->extra) ? 1 : 0,
			"avg_screen_time"         => (int) $avg_screen_time,
			"total_action_count"      => (int) $total_action_count,
			"avg_message_answer_time" => (int) $avg_message_answer_time,
			"extra"                   => (object) Extra::formatMemberExtra($member),
			"avatar_color"            => (string) Avatar::getColorOutput($avatar_color_id),
		];

		// если у пользователя есть аватар
		if ($member->avatar_file_key !== "") {

			$output["avatar"] = (object) [
				"file_key" => (string) $member->avatar_file_key,
			];
		}
		return $output;
	}

	# endregion
}
