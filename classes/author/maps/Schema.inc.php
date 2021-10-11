<?php
/**
 * @file classes/author/maps/Schema.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class author
 *
 * @brief Map authors to the properties defined in the announcement schema
 */

namespace PKP\author\maps;

use APP\author\Author;
use Illuminate\Support\Enumerable;
use PKP\security\Role;
use PKP\services\PKPSchemaService;

class Schema extends \PKP\core\maps\Schema
{
    /** @var Enumerable */
    public $collection;

    /** @var string */
    public $schema = PKPSchemaService::SCHEMA_AUTHOR;

    /**
     * Map an author
     *
     * Includes all properties in the announcement schema.
     */
    public function map(Author $item): array
    {
        return $this->mapByProperties($this->getProps(), $item);
    }

    /**
     * Summarize an author
     *
     * Includes properties with the apiSummary flag in the author schema.
     */
    public function summarize(Author $item): array
    {
        return $this->mapByProperties($this->getSummaryProps(), $item);
    }

    /**
     * Map a collection of Authors
     *
     * @see self::map
     */
    public function mapMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->map($item);
        });
    }

    /**
     * Summarize a collection of Authors
     *
     * @see self::summarize
     */
    public function summarizeMany(Enumerable $collection): Enumerable
    {
        $this->collection = $collection;
        return $collection->map(function ($item) {
            return $this->summarize($item);
        });
    }

    /**
     * Map schema properties of an Author to an assoc array
     */
    protected function mapByProperties(array $props, Author $item): array
    {
        $output = [];
        foreach ($props as $prop) {
            switch ($prop) {
                case 'userGroupLabel':
                    $userGroupDao = \DAORegistry::getDAO('UserGroupDAO'); /** @var \UserGroupDAO $userGroupDao */
                    $authorUserGroups = $userGroupDao->getByRoleId($this->context->getId(), Role::ROLE_ID_AUTHOR)->toArray();

                    $currentAuthorUserGroupFiltered = array_filter($authorUserGroups, function ($authorUserGroup) use ($item) {
                        return $authorUserGroup->getId() == $item->getData('userGroupId');
                    });

                    if (!empty($currentAuthorUserGroupFiltered)) {
                        $currentAuthorUserGroup = array_values($currentAuthorUserGroupFiltered)[0];

                        $output[$prop] = $currentAuthorUserGroup->getLocalizedName();
                    }

                    break;
                default:
                    $output[$prop] = $item->getData($prop);
                    break;
            }
        }

        $output = $this->schemaService->addMissingMultilingualValues($this->schema, $output, $this->context->getSupportedSubmissionLocales());

        ksort($output);

        return $this->withExtensions($output, $item);
    }
}
