<?php

namespace CompassApp\Domain\Member\Struct;

/**
 * Структура для информации о пользователе
 */
class Main {

	public int    $user_id;
	public int    $role;
	public int    $npc_type;
	public int    $permissions;
	public int    $created_at;
	public int    $updated_at;
	public int    $company_joined_at;
	public int    $left_at;
	public int    $full_name_updated_at;
	public string $full_name;
	public string $mbti_type;
	public string $short_description;
	public string $avatar_file_key;
	public string $comment;
	public array  $extra;

	/**
	 * Struct_Bus_CompanyCache_Member constructor.
	 *
	 * @param int    $user_id
	 * @param int    $role
	 * @param int    $npc_type
	 * @param int    $permissions
	 * @param int    $created_at
	 * @param int    $updated_at
	 * @param int    $company_joined_at
	 * @param int    $left_at
	 * @param int    $full_name_updated_at
	 * @param string $full_name
	 * @param string $mbti_type
	 * @param string $short_description
	 * @param string $avatar_file_key
	 * @param string $comment
	 * @param array  $extra
	 */
	public function __construct(
		int    $user_id,
		int    $role,
		int    $npc_type,
		int    $permissions,
		int    $created_at,
		int    $updated_at,
		int    $company_joined_at,
		int    $left_at,
		int    $full_name_updated_at,
		string $full_name,
		string $mbti_type,
		string $short_description,
		string $avatar_file_key,
		string $comment,
		array  $extra,
	) {

		$this->user_id              = $user_id;
		$this->role                 = $role;
		$this->npc_type             = $npc_type;
		$this->permissions          = $permissions;
		$this->created_at           = $created_at;
		$this->updated_at           = $updated_at;
		$this->company_joined_at    = $company_joined_at;
		$this->left_at    	    = $left_at;
		$this->full_name_updated_at = $full_name_updated_at;
		$this->full_name            = $full_name;
		$this->mbti_type            = $mbti_type;
		$this->short_description    = $short_description;
		$this->avatar_file_key      = $avatar_file_key;
		$this->comment              = $comment;
		$this->extra                = $extra;
	}
}