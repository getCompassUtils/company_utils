<?php

namespace CompassApp\Domain\Member\Entity;

/**
 * Типы вхождений пользователя
 */
class Entry {

	public const ENTRY_CREATOR_TYPE     = 0;
	public const ENTRY_INVITE_LINK_TYPE = 1;
	public const ENTRY_WITHOUT_TYPE     = 99;
}
