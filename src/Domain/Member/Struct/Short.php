<?php

namespace CompassApp\Domain\Member\Struct;

/**
 * Структура для информации о пользователе (кратко)
 */
class Short {

	public int $user_id;
	public int $role;
	public int $permissions;
	public int $npc_type;

	/**
	 * Struct_Bus_CompanyCache_ShortMember constructor.
	 *
	 * @param int $user_id
	 * @param int $role
	 * @param int $npc_type
	 * @param int $permissions
	 */
	public function __construct(int $user_id, int $role, int $npc_type, int $permissions) {

		$this->user_id     = $user_id;
		$this->role        = $role;
		$this->npc_type    = $npc_type;
		$this->permissions = $permissions;
	}
}