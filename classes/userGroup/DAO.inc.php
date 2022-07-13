<?php

/**
 * @file classes/userGroup/DAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DAO
 * @ingroup userGroup
 *
 * @see \PKP\userGroup\UserGroup
 *
 * @brief Operations for retrieving and modifying UserGroup objects.
 */

namespace PKP\userGroup;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\services\PKPSchemaService;
use PKP\facades\Repo;
use PKP\userGroup\UserGroup;

class DAO extends EntityDAO
{
    /** @copydoc EntityDAO::$schema */
    public $schema = PKPSchemaService::SCHEMA_USER_GROUP;

    /** @copydoc EntityDAO::$table */
    public $table = 'user_groups';

    /** @copydoc EntityDAO::$settingsTable */
    public $settingsTable = 'user_group_settings';

    /** @copydoc EntityDAO::$primarykeyColumn */
    public $primaryKeyColumn = 'user_group_id';

    /** @copydoc EntityDAO::$primaryTableColumns */
    public $primaryTableColumns = [
        'id' => 'user_group_id',
        'contextId' => 'context_id',
        'roleId' => 'role_id',
        'isDefault' => 'is_default',
        'showTitle' => 'show_title',
        'permitSelfRegistration' => 'permit_self_registration',
        'permitMetadataEdit' => 'permit_metadata_edit',
    ];

    /**
     * Instantiate a new DataObject
     */
    public function newDataObject(): UserGroup
    {
        return app(UserGroup::class);
    }

    // /**
    //  * @copydoc EntityDAO::get()
    //  */
    // public function get(int $id): ?UserGroup
    // {
    //     // This is ovveriden due to the need to include submission_locale
    //     // to the fromRow function
    //     $row = DB::table('user_groups as a')
    //         ->where('a.user_group_id', '=', $id)
    //         ->select(['a.*'])
    //         ->first();

    //     return $row ? $this->fromRow($row) : null;
    // }

    /**
     * Get the total count of rows matching the configured query
     */
    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->count();
    }

    /**
     * Get a list of ids matching the configured query
     */
    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('a.' . $this->primaryKeyColumn)
            ->pluck('a.' . $this->primaryKeyColumn);
    }

    /**
     * Get a collection of publications matching the configured query
     */
    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->user_group_id => $this->fromRow($row);
            }
        });
    }

    /**
     * @copydoc EntityDAO::fromRow()
     */
    public function fromRow(object $row): UserGroup
    {
        $userGroup = parent::fromRow($row);

        return $userGroup;
    }

    /**
     * @copydoc EntityDAO::insert()
     */
    public function insert(UserGroup $userGroup): int
    {
        return parent::_insert($userGroup);
    }

    /**
     * @copydoc EntityDAO::update()
     */
    public function update(UserGroup $userGroup)
    {
        parent::_update($userGroup);
    }

    /**
     * @copydoc EntityDAO::delete()
     */
    public function delete(UserGroup $userGroup)
    {
        parent::_delete($userGroup);
    }

    /**
     * @copydoc EntityDAO::deleteById()
     */
    public function deleteById(int $userGroupId)
    {
        DB::beginTransaction();

        try {
            DB::table('user_user_groups')
                ->where('user_group_id', '=', $userGroupId)
                ->delete();

            DB::table('user_group_stage')
                ->where('user_group_id', '=', $userGroupId)
                ->delete();

            parent::deleteById($userGroupId);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get the next sequence that should be used when adding a contributor to a publication
     */
    // public function getNextSeq(int $publicationId): int
    // {
    //     $nextSeq = 0;
    //     $seq = DB::table('authors as a')
    //         ->join('publications as p', 'a.publication_id', '=', 'p.publication_id')
    //         ->where('p.publication_id', '=', $publicationId)
    //         ->max('a.seq');

    //     if ($seq) {
    //         $nextSeq = $seq + 1;
    //     }

    //     return $nextSeq;
    // }

    /**
     * Reset the order of contributors in a publication
     *
     * This method resets the seq property for each contributor in a publication
     * so that they are numbered sequentially without any gaps.
     *
     * eg - 1, 3, 4, 6 will become 1, 2, 3, 4
     */
    // public function resetContributorsOrder(int $publicationId)
    // {
    //     $authorIds = $this->getIds(Repo::author()
    //         ->getCollector()
    //         ->filterByPublicationIds([$publicationId])
    //         ->orderBy(Repo::author()->getCollector()::ORDERBY_SEQUENCE)
    //     );
    //     foreach ($authorIds as $seq => $authorId) {
    //         DB::table('authors')->where('author_id', '=', $authorId)->update(['seq' => $seq]);
    //     }
    // }
}
