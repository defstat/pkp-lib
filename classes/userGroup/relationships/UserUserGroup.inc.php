<?php

/**
 * @file classes/userGroup/relationships/UserUserGroup.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class \PKP\userGroup\UserGroup
 * @ingroup userGroup
 *
 * @see DAO
 *
 * @brief UserGroup metadata class.
 */

namespace PKP\userGroup\relationships;

use PKP\facades\Locale;
use PKP\db\DAORegistry;
use PKP\user\User;
use PKP\userGroup\UserGroup;
use APP\facades\Repo;

class UserUserGroup extends \Illuminate\Database\Eloquent\Model
{
    //
    // The following attributes configure Eloquent's expectations for this model
    //
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
    protected $fillable = ['userGroupId', 'userId'];

    //
    // The following two functions allow callers to get User and UserGroup objects from this model by
    // accessing the ->user and ->userGroup attributes
    //
    public function user(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => \APP\facades\Repo::user()->get($attributes['user_id']),
            set: fn($value) => $value->getId()
        );
    }

    public function userGroup(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => \PKP\db\DAORegistry::getDAO('UserGroupDAO')->getById($attributes['user_group_id']),
            set: fn($value) => $value->getId()
        );
    }

    //
    // The following two functions permit lowerCamelCase accessors in place of snake_case column names
    // (e.g. $userUserGroup->userGroupId rather than $userUserGroup->user_group_id)
    //
    public function userId(): Attribute
    {
        return Attribute::make(
            get: fn($user, $attributes) => $attributes['user_id'],
            set: fn($value) => ['user_id' => $value]
        );
    }

    public function userGroupId(): Attribute
    {
        return Attribute::make(
            get: fn($user, $attributes) => $attributes['user_group_id'],
            set: fn($value) => ['user_group_id' => $value]
        );
    }

    //
    // The following two functions allow for queries to be executed that don't require the database column
    // names to be exposed in calling code (as ->where would do). Called e.g.: UserUserGroup::withUserId(123)
    //
    public function scopeWithUserId(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeWithUserGroupId(Builder $query, int $userGroupId): Builder
    {
        return $query->where('user_group_id', $userGroupId);
    }
}