<?php

namespace CompassApp\Domain\User;

use BaseFrame\Exception\Domain\ParseFatalException;

/**
 * класс для работы с данными пользователя
 */
class Main {

	// -------------------------------------------------------
	// USER TYPES VARIABLES
	// -------------------------------------------------------

	public const USER_HUMAN       = "human";
	public const USER_SYSTEM_BOT  = "systembot";
	public const USER_SUPPORT_BOT = "supportbot";
	public const USER_OUTER_BOT   = "outerbot";
	public const USER_BOT         = "userbot";
	public const OPERATOR         = "operator";

	public const DEFAULT_SHORT_DESCRIPTION = "Пользователь Compass";

	public const    NPC_TYPE_HUMAN               = 1; // пользователь
	protected const _NPC_TYPE_SYSTEM_BOT_NOTICE  = 5; // тип системный бот подтип Оповещение
	protected const _NPC_TYPE_SYSTEM_BOT_REMIND  = 6; // тип системный бот подтип Напоминание
	protected const _NPC_TYPE_SYSTEM_BOT_SUPPORT = 7; // тип системный бот, подтип Отдел поддержки
	protected const _NPC_TYPE_OPERATOR           = 171; // оператор поддержки

	protected const _SYSTEM_BOT_NPC_TYPE_FROM  = 5;
	protected const _SYSTEM_BOT_NPC_TYPE_TO    = 50;
	protected const _SUPPORT_BOT_NPC_TYPE_FROM = 51;
	protected const _SUPPORT_BOT_NPC_TYPE_TO   = 100;
	protected const _OUTER_BOT_NPC_TYPE_FROM   = 101;
	protected const _OUTER_BOT_NPC_TYPE_TO     = 127;
	protected const _USER_BOT_NPC_TYPE_FROM    = 128;
	protected const _USER_BOT_NPC_TYPE_TO      = 170;

	// массив для преобразования внутреннего типа во внешний
	public const USER_TYPE_SCHEMA = [
		self::USER_HUMAN       => "user",
		self::USER_SYSTEM_BOT  => "system_bot",
		self::USER_SUPPORT_BOT => "support_bot",
		self::USER_OUTER_BOT   => "bot",
		self::USER_BOT         => "userbot",
		self::OPERATOR         => "operator",
	];

	// список подтипов для системного бота
	protected const _SYSTEM_BOT_SUBTYPE = [
		self::_NPC_TYPE_SYSTEM_BOT_NOTICE  => "notice_bot",
		self::_NPC_TYPE_SYSTEM_BOT_REMIND  => "remind_bot",
		self::_NPC_TYPE_SYSTEM_BOT_SUPPORT => "support_bot",
	];

	// -------------------------------------------------------
	// PUBLIC METHODS Определение типа пользователя
	// -------------------------------------------------------

	// возвращает тип пользователя
	public static function getUserType(int $npc_type):string {

		if (self::isHuman($npc_type)) {

			// человек
			return self::USER_HUMAN;
		} elseif (self::isSystemBot($npc_type)) {

			// системный бот
			return self::USER_SYSTEM_BOT;
		} elseif (self::isSupportBot($npc_type)) {

			// системный бот поддержки
			return self::USER_SUPPORT_BOT;
		} elseif (self::isOuterBot($npc_type)) {

			// внешний бот
			return self::USER_OUTER_BOT;
		} elseif (self::isUserbot($npc_type)) {

			// пользовательский бот
			return self::USER_BOT;
		} elseif (self::isOperator($npc_type)) {

			// оператор поддержки
			return self::OPERATOR;
		}

		throw new ParseFatalException("can't get user type");
	}

	// решаем, является ли пользователь человеком
	public static function isHuman(int $npc_type):bool {

		return $npc_type == self::NPC_TYPE_HUMAN;
	}

	// решаем, является ли пользователь оператором
	public static function isOperator(int $npc_type):bool {

		return $npc_type == self::_NPC_TYPE_OPERATOR;
	}

	// решаем, является ли пользователь системным ботом
	public static function isSystemBot(int $npc_type):bool {

		return $npc_type >= self::_SYSTEM_BOT_NPC_TYPE_FROM && $npc_type <= self::_SYSTEM_BOT_NPC_TYPE_TO;
	}

	/**
	 * решаем, является ли пользователь ботом поддержки
	 *
	 * @return bool
	 * @deprecated удалять страшно (legacy еще то), поэтому не используйте в новом коде!
	 */
	public static function isSupportBot(int $npc_type):bool {

		return $npc_type >= self::_SUPPORT_BOT_NPC_TYPE_FROM && $npc_type <= self::_SUPPORT_BOT_NPC_TYPE_TO;
	}

	/**
	 * проверяем, является ли пользователь системным ботом поддержки
	 *
	 * @return bool
	 */
	public static function isSystemBotSupport(int $npc_type):bool {

		return $npc_type === self::_NPC_TYPE_SYSTEM_BOT_SUPPORT;
	}

	// решаем, является ли пользователь внешним ботом
	public static function isOuterBot(int $npc_type):bool {

		return ($npc_type >= self::_OUTER_BOT_NPC_TYPE_FROM && $npc_type <= self::_OUTER_BOT_NPC_TYPE_TO)
			|| ($npc_type > self::NPC_TYPE_HUMAN && $npc_type < self::_SYSTEM_BOT_NPC_TYPE_FROM);
	}

	// решаем, является ли пользователь пользовательским ботом
	public static function isUserbot(int $npc_type):bool {

		return $npc_type >= self::_USER_BOT_NPC_TYPE_FROM && $npc_type <= self::_USER_BOT_NPC_TYPE_TO;
	}

	// проверяем, является ли пользователь ботом
	public static function isBot(int $npc_type):bool {

		return self::isSystemBot($npc_type) || self::isSupportBot($npc_type) || self::isOuterBot($npc_type) || self::isUserbot($npc_type);
	}

	/**
	 * получаем подтип системного бота
	 */
	public static function getSystemBotSubtype(int $npc_type):string {

		// если это не системный бот возвращаем пустую строку
		// не ругаемся, так как используется в батчинге
		if (!self::isSystemBot($npc_type)) {
			return "";
		}

		// если это неизвестный тип системного бота
		if (!isset(self::_SYSTEM_BOT_SUBTYPE[$npc_type])) {
			return "";
		}

		// возвращаем подтип системного бота
		return self::_SYSTEM_BOT_SUBTYPE[$npc_type];
	}
}
