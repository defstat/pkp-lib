<?php
/**
 * @file classes/userGroup/Repository.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class userGroup
 *
 * @brief A repository to find and manage userGroups.
 */

namespace PKP\userGroup;

use PKP\userGroup\DAO;
use APP\core\Request;
use APP\core\Services;
use APP\facades\Repo;
use APP\submission\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use PKP\plugins\HookRegistry;
use PKP\services\PKPSchemaService;
use PKP\validation\ValidatorFactory;
use PKP\userGroup\relationships\UserUserGroup;
use PKP\userGroup\relationships\UserGroupStage;
use PKP\workflow\WorkflowStageDAO;

class Repository
{
    /** @var DAO */
    public $dao;

    /** @var string $schemaMap The name of the class to map this entity to its schema */
    public $schemaMap = maps\Schema::class;

    /** @var Request */
    protected $request;

    /** @var PKPSchemaService */
    protected $schemaService;

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        $this->dao = $dao;
        $this->request = $request;
        $this->schemaService = $schemaService;
    }

    /** @copydoc DAO::newDataObject() */
    public function newDataObject(array $params = []): UserGroup
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    /** @copydoc DAO::get() */
    public function get(int $id): ?UserGroup
    {
        return $this->dao->get($id);
    }

    /** @copydoc DAO::getCount() */
    public function getCount(Collector $query): int
    {
        return $this->dao->getCount($query);
    }

    /** @copydoc DAO::getIds() */
    public function getIds(Collector $query): Collection
    {
        return $this->dao->getIds($query);
    }

    /** @copydoc DAO::getMany() */
    public function getMany(Collector $query): LazyCollection
    {
        return $this->dao->getMany($query);
    }

    /** @copydoc DAO::getCollector() */
    public function getCollector(): Collector
    {
        return app(Collector::class);
    }

    /**
     * Get an instance of the map class for mapping
     * authors to their schema
     */
    public function getSchemaMap(): maps\Schema
    {
        return app('maps')->withExtensions($this->schemaMap);
    }

    /**
     * Validate properties for an author
     *
     * Perform validation checks on data used to add or edit an author.
     *
     * @param UserGroup|null $author The author being edited. Pass `null` if creating a new author
     * @param array $props A key/value array with the new data to validate
     * @param array $allowedLocales The context's supported submission locales
     * @param string $primaryLocale The submission's primary locale
     *
     * @return array A key/value array with validation errors. Empty if no errors
     */
    public function validate($userGroup, $props, $allowedLocales, $primaryLocale)
    {
        $schemaService = Services::get('schema');

        $validator = ValidatorFactory::make(
            $props,
            $schemaService->getValidationRules(PKPSchemaService::SCHEMA_USER_GROUP, $allowedLocales)
        );

        // Check required fields
        ValidatorFactory::required(
            $validator,
            $userGroup,
            $schemaService->getRequiredProps(PKPSchemaService::SCHEMA_USER_GROUP),
            $schemaService->getMultilingualProps(PKPSchemaService::SCHEMA_USER_GROUP),
            $allowedLocales,
            $primaryLocale
        );

        // Check for input from disallowed locales
        ValidatorFactory::allowedLocales($validator, $schemaService->getMultilingualProps(PKPSchemaService::SCHEMA_USER_GROUP), $allowedLocales);

        // The publicationId must match an existing publication that is not yet published
        // $validator->after(function ($validator) use ($props) {
        //     if (isset($props['publicationId']) && !$validator->errors()->get('publicationId')) {
        //         $publication = Repo::publication()->get($props['publicationId']);
        //         if (!$publication) {
        //             $validator->errors()->add('publicationId', __('author.publicationNotFound'));
        //         } elseif ($publication->getData('status') === PKPSubmission::STATUS_PUBLISHED) {
        //             $validator->errors()->add('publicationId', __('author.editPublishedDisabled'));
        //         }
        //     }
        // });

        $errors = [];
        if ($validator->fails()) {
            $errors = $schemaService->formatValidationErrors($validator->errors());
        }

        HookRegistry::call('UserGroup::validate', [$errors, $userGroup, $props, $allowedLocales, $primaryLocale]);

        return $errors;
    }

    /**
     * @copydoc \PKP\services\entityProperties\EntityWriteInterface::add()
     */
    public function add(UserGroup $userGroup): int
    {
        $userGroupId = $this->dao->insert($userGroup);
        $userGroup = Repo::userGroup()->get($userGroupId);

        HookRegistry::call('UserGroup::add', [$userGroup]);

        return $userGroup->getId();
    }

    /**
     * @copydoc \PKP\services\entityProperties\EntityWriteInterface::edit()
     */
    public function edit(UserGroup $userGroup, array $params)
    {
        $newUserGroup = Repo::userGroup()->newDataObject(array_merge($userGroup->_data, $params));

        HookRegistry::call('UserGroup::edit', [$newUserGroup, $userGroup, $params]);

        $this->dao->update($newUserGroup);

        Repo::userGroup()->get($newUserGroup->getId());
    }

    /**
     * @copydoc \PKP\services\entityProperties\EntityWriteInterface::delete()
     */
    public function delete(UserGroup $userGroup)
    {
        HookRegistry::call('UserGroup::delete::before', [$userGroup]);
        
        $this->dao->delete($userGroup);

        HookRegistry::call('UserGroup::delete', [$userGroup]);
    }

    /**
    * Delete all user groups assigned to a certain context by contextId
    * 
    * @param int $contextId 
    */
    public function deleteByContextId($contextId)
    {
        // I may have to use transactions here
        $collector = Repo::userGroup()->getCollector()->filterByContextIds([$contextId]);
        $userGroupIds = Repo::userGroup()->getIds($collector);
        foreach ($userGroupIds as $userGroupId) {
            $this->dao->deleteById($userGroupId);
        }
    }

    /**
    * Get all user groups assigned to a certain context by contextId
    */
    public function getByContextId(int $contextId)
    {
        // I may have to use transactions here
        $collector = Repo::userGroup()
            ->getCollector()
            ->filterByContextIds([$contextId]);

        return Repo::userGroup()->getMany($collector); 
    }

    /**
    * return all user group ids given a certain role id
    * 
    * @param int $roleId 
    * @param int|null $contextId 
    */
    public function getArrayIdByRoleId($roleId, $contextId = null) : array
    {
        $collector = Repo::userGroup()
            ->getCollector()->filterByRoleIds([$roleId]);
        
        if ($contextId) {
            $collector->filterByContextIds([$contextId]);
        }

        return Repo::userGroup()->getIds($collector)->toArray();
    }

    /**
    * return all user group ids given a certain role id
    * 
    * @param int $roleId 
    * @param int $contextId 
    * @param bool $default 
    */
    public function getByRoleIds(array $roleIds, int $contextId, ?bool $default = false) : LazyCollection
    {
        $collector = Repo::userGroup()
            ->getCollector()
            ->filterByRoleIds($roleIds)
            ->filterByContextIds([$contextId]);
        
        if (!is_null($default)) {
            $collector->filterByIsDefault($default);
        }

        return Repo::userGroup()->getMany($collector);
    }

    /**
    * return all user groups ids for a user id
    */
    public function userUserGroups(int $userId, ?int $contextId = null) : LazyCollection
    {
        $collector = Repo::userGroup()
            ->getCollector()
            ->filterByUserIds([$userId]);

        if ($contextId) {
            $collector->filterByContextIds([$contextId]);
        }

        return Repo::userGroup()->getMany($collector);
    }

    /**
    * return whether a user is in a user group
    */
    public function userInGroup(int $userId, int $userGroupId) : bool
    {
        return UserUserGroup::withUserId($userId)
            ->withUserGroupId($userGroupId)
            ->get()
            ->isNotEmpty();
        // $collector = Repo::userGroup()
        //     ->getCollector()
        //     ->filterByUserIds([$userId]);

        // $userGroups = $this->getMany($collector);

        // return $userGroups->where('id', $userGroupId)->count();
    }

    /**
    * return whether a context has a specific user group
    */
    public function contextHasGroup(int $contextId, int $userGroupId) : bool
    {
        $collector = Repo::userGroup()
            ->getCollector()
            ->filterByContextIds([$contextId]);

        $userGroups = $this->getMany($collector);

        return $userGroups->where('id', $userGroupId)->count();
    }

    public function assignUserToGroup(int $userId, int $userGroupId) : bool
    {
        return UserUserGroup::create([
            'userId' => $userId,
            'userGroupId' => $userGroupId
        ]);

        // $newUserUserGroup = new UserUserGroup;

        // $newUserUserGroup->userId = $userId;
        // $newUserUserGroup->userGrouId = $userGroupId;

        // return $newUserUserGroup->save();
    }

    public function removeUserFromGroup($userId, $userGroupId, $contextId) : bool
    {
        return UserUserGroup::withUserId($userId)
            ->withUserGroupId($userGroupId)
            ->withContextId($contextId)
            ->delete();
    }

    public function deleteAssignmentsByUserId(int $userId, ?int $userGroupId = null) : bool
    {
        $query = UserUserGroup::withUserId($userId);

        if ($userGroupId) {
            $query->withUserGroupId($userGroupId);
        }

        return $query->delete();
    }

    public function deleteAssignmentsByContextId(int $contextId, ?int $userId = null) : bool
    {
        $userUserGroups = UserUserGroup::withContextId($contextId);

        if ($userId) {
            $userUserGroups->withUserId($userId);
        }
        
        return $userUserGroups->delete();
    }

    /**
    * Get the user groups assigned to each stage.
    * 
    * @return LazyCollection<UserGroup>
    */
    public function getUserGroupsByStage($contextId, $stageId, $roleId = null, $count = null) : LazyCollection
    {
        $userGroups = $this->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStageIds([$stageId]);
        
        if ($roleId) {
            $userGroups->filterByRoleIds([$roleId]);
        }

        $userGroups->orderBy(Collector::ORDERBY_ROLE_ID);

        return Repo::userGroup()->getMany($userGroups);
    }

    /**
    * Remove a user group from a stage
    */
    public function removeGroupFromStage(int $contextId, int $userGroupId, int $stageId) : bool
    {
        return UserGroupStage::withContextId($contextId)
            ->withUserGroupId($userGroupId)
            ->withStageId($stageId)
            ->delete();
    }

    /**
     * Get all stages assigned to one user group in one context.
     *
     * @param int $contextId The context ID.
     * @param int $userGroupId The user group ID
     *
     * @return array
     */
    public function getAssignedStagesByUserGroupId(int $contextId, int $userGroupId) : array
    {
        return UserGroupStage::withContextId($contextId)
            ->withUserGroupId($userGroupId)
            ->pluck('stage_id')
            ->toArray();
    }
}
