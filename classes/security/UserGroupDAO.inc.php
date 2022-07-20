<?php

/**
 * @file classes/security/UserGroupDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class UserGroupDAO
 * @ingroup security
 *
 * @see UserGroup
 *
 * @brief Operations for retrieving and modifying User Groups and user group assignments
 */

namespace PKP\security;

use APP\core\Application;
use APP\facades\Repo;
use DomainException;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PKP\core\PKPString;
use PKP\db\DAO;
use PKP\db\DAORegistry;
use PKP\db\DAOResultFactory;
use PKP\facades\Locale;
use PKP\identity\Identity;
use PKP\plugins\HookRegistry;
use PKP\workflow\WorkflowStageDAO;
use PKP\xml\PKPXMLParser;
use PKP\userGroup\relationships\UserGroupStage;

class UserGroupDAO extends DAO
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * create new data object
     * (allows DAO to be subclassed)
     */
    public function newDataObject()
    {
        return new UserGroup();
    }

    /**
     * Internal function to return a UserGroup object from a row.
     *
     * @param array $row
     *
     * @return UserGroup
     */
    public function _returnFromRow($row)
    {
        $userGroup = $this->newDataObject();
        $userGroup->setId($row['user_group_id']);
        $userGroup->setRoleId($row['role_id']);
        $userGroup->setContextId($row['context_id']);
        $userGroup->setDefault($row['is_default']);
        $userGroup->setShowTitle($row['show_title']);
        $userGroup->setPermitSelfRegistration($row['permit_self_registration']);
        $userGroup->setPermitMetadataEdit($row['permit_metadata_edit']);

        $this->getDataObjectSettings('user_group_settings', 'user_group_id', $row['user_group_id'], $userGroup);

        HookRegistry::call('UserGroupDAO::_returnFromRow', [&$userGroup, &$row]);

        return $userGroup;
    }

    /**
     * Retrieves a keyed Collection (key = user_group_id, value = count) with the amount of active users for each user group
     */
    public function getUserCountByContextId(?int $contextId = null): Collection
    {
        return DB::table('user_groups', 'ug')
            ->join('user_user_groups AS uug', 'uug.user_group_id', '=', 'ug.user_group_id')
            ->join('users AS u', 'u.user_id', '=', 'uug.user_id')
            ->when($contextId !== null, fn (Builder $query) => $query->where('ug.context_id', '=', $contextId))
            ->where('u.disabled', '=', 0)
            ->groupBy('ug.user_group_id')
            ->select('ug.user_group_id')
            ->selectRaw('COUNT(0) AS count')
            ->pluck('count', 'user_group_id');
    }

    /**
     * return an Iterator of User objects given the search parameters
     *
     * @param int $contextId
     * @param string $searchType
     * @param string $search
     * @param string $searchMatch
     * @param DBResultRange $dbResultRange
     *
     * @return DAOResultFactory
     */
    public function getUsersByContextId($contextId, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null)
    {
        return $this->getUsersById(null, $contextId, $searchType, $search, $searchMatch, $dbResultRange);
    }

    /**
     * Find users that don't have a given role
     *
     * @param int $roleId ROLE_ID_... int (const)
     * @param int $contextId Optional context ID
     * @param string $search Optional search string
     * @param RangeInfo $rangeInfo Optional range info
     *
     * @return DAOResultFactory
     */
    public function getUsersNotInRole($roleId, $contextId = null, $search = null, $rangeInfo = null)
    {
        $params = isset($search) ? [Identity::IDENTITY_SETTING_GIVENNAME, Identity::IDENTITY_SETTING_FAMILYNAME] : [];
        $params[] = (int) $roleId;
        if ($contextId) {
            $params[] = (int) $contextId;
        }
        if (isset($search)) {
            $params = array_merge($params, array_pad([], 4, '%' . $search . '%'));
        }

        $result = $this->retrieveRange(
            'SELECT DISTINCT u.*
            FROM users u
            ' . (isset($search) ? '
                    LEFT JOIN user_settings usgs ON (usgs.user_id = u.user_id AND usgs.setting_name = ?)
                    LEFT JOIN user_settings usfs ON (usfs.user_id = u.user_id AND usfs.setting_name = ?)
                ' : '') . '
            WHERE u.user_id NOT IN (
                SELECT DISTINCT u.user_id
                FROM users u, user_user_groups uug, user_groups ug
                WHERE u.user_id = uug.user_id
                    AND ug.user_group_id = uug.user_group_id
                    AND ug.role_id = ?' .
                ($contextId ? ' AND ug.context_id = ?' : '') .
                ')' .
            (isset($search) ? ' AND (usgs.setting_value LIKE ? OR usfs.setting_value LIKE ? OR u.email LIKE ? OR u.username LIKE ?)' : ''),
            $params,
            $rangeInfo
        );
        return new DAOResultFactory($result, Repo::user()->dao, 'fromRow');
    }

    // TODO:: May need to go to User Repository
    /**
     * return an Iterator of User objects given the search parameters
     *
     * @param int $userGroupId optional
     * @param int $contextId optional
     * @param string $searchType
     * @param string $search
     * @param string $searchMatch
     * @param DBResultRange $dbResultRange
     *
     * @return DAOResultFactory
     */
    public function getUsersById($userGroupId = null, $contextId = null, $searchType = null, $search = null, $searchMatch = null, $dbResultRange = null)
    {
        $locale = Locale::getLocale();
        // The users register for the site, thus the site primary locale should be the default locale
        $site = Application::get()->getRequest()->getSite();
        $primaryLocale = $site->getPrimaryLocale();

        $settingValue = "(
            SELECT us.setting_value
            FROM user_settings AS us
            WHERE
                us.user_id = u.user_id
                AND us.setting_name = ?
                AND us.locale IN (?, ?)
            -- First non-null/empty values, then give preference to the current locale
            ORDER BY
                COALESCE(us.setting_value, '') = '', us.locale <> ?
            LIMIT 1
        )";
        $params = [
            Identity::IDENTITY_SETTING_GIVENNAME, $locale, $primaryLocale, $locale,
            Identity::IDENTITY_SETTING_FAMILYNAME, $locale, $primaryLocale, $locale
        ];

        $sql = "SELECT u.*, ${settingValue} AS user_given, ${settingValue} AS user_family
            FROM users AS u
            WHERE 1 = 1";

        // Has user group
        if ($contextId || $userGroupId) {
            if ($contextId) {
                $params[] = (int) $contextId;
            }
            if ($userGroupId) {
                $params[] = (int) $userGroupId;
            }
            $sql .= ' AND EXISTS (
                SELECT 0
                FROM user_user_groups uug
                INNER JOIN user_groups ug
                    ON ug.user_group_id = uug.user_group_id
                WHERE
                    uug.user_id = u.user_id
                    ' . ($contextId ? 'AND ug.context_id = ?' : '') . '
                    ' . ($userGroupId ? 'AND ug.user_group_id = ?' : '') . '
            )';
        }
        $sql .= ' ' . $this->_getSearchSql($searchType, $search, $searchMatch, $params);

        // Get the result set
        $result = $this->retrieveRange($sql, $params, $dbResultRange);
        return new DAOResultFactory($result, Repo::user()->dao, 'fromRow', [], $sql, $params, $dbResultRange);
    }

    //
    // UserGroupAssignment related
    //


    

    //
    // Extra settings (not handled by rest of Dao)
    //
    /**
     * Method for updatea userGroup setting
     *
     * @param int $userGroupId
     * @param string $name
     * @param string $type data type of the setting. If omitted, type will be guessed
     * @param bool $isLocalized
     */
    public function updateSetting($userGroupId, $name, $value, $type = null, $isLocalized = false)
    {
        $keyFields = ['setting_name', 'locale', 'user_group_id'];

        if (!$isLocalized) {
            $value = $this->convertToDB($value, $type);
            DB::table('user_group_settings')->updateOrInsert(
                ['user_group_id' => (int) $userGroupId, 'setting_name' => $name, 'locale' => ''],
                ['setting_value' => $value, 'setting_type' => $type]
            );
        } else {
            if (is_array($value)) {
                foreach ($value as $locale => $localeValue) {
                    $this->update('DELETE FROM user_group_settings WHERE user_group_id = ? AND setting_name = ? AND locale = ?', [(int) $userGroupId, $name, $locale]);
                    if (empty($localeValue)) {
                        continue;
                    }
                    $type = null;
                    $this->update(
                        'INSERT INTO user_group_settings
                    (user_group_id, setting_name, setting_value, setting_type, locale)
                    VALUES (?, ?, ?, ?, ?)',
                        [$userGroupId, $name, $this->convertToDB($localeValue, $type), $type, $locale]
                    );
                }
            }
        }
    }


    /**
     * Retrieve a context setting value.
     *
     * @param int $userGroupId
     * @param string $name
     * @param string $locale optional
     */
    public function getSetting($userGroupId, $name, $locale = null)
    {
        $params = [(int) $userGroupId, $name];
        if ($locale) {
            $params[] = $locale;
        }
        $result = $this->retrieve(
            'SELECT setting_name, setting_value, setting_type, locale
            FROM user_group_settings
            WHERE user_group_id = ? AND
                setting_name = ?' .
                ($locale ? ' AND locale = ?' : ''),
            $params
        );

        $returner = false;
        if ($row = $result->current()) {
            return $this->convertFromDB($row->setting_value, $row->setting_type);
        }
        $returner = [];
        foreach ($result as $row) {
            $returner[$row->locale] = $this->convertFromDB($row->setting_value, $row->setting_type);
        }
        return count($returner) ? $returner : false;
    }

    //
    // Install/Defaults with settings
    //

    /**
     * Load the XML file and move the settings to the DB
     *
     * @param int $contextId
     * @param string $filename
     *
     * @return bool true === success
     */
    public function installSettings($contextId, $filename)
    {
        $xmlParser = new PKPXMLParser();
        $tree = $xmlParser->parse($filename);

        $siteDao = DAORegistry::getDAO('SiteDAO'); /** @var SiteDAO $siteDao */
        $site = $siteDao->getSite();
        $installedLocales = $site->getInstalledLocales();

        if (!$tree) {
            return false;
        }

        foreach ($tree->getChildren() as $setting) {
            $roleId = hexdec($setting->getAttribute('roleId'));
            $nameKey = $setting->getAttribute('name');
            $abbrevKey = $setting->getAttribute('abbrev');
            $permitSelfRegistration = $setting->getAttribute('permitSelfRegistration');
            $permitMetadataEdit = $setting->getAttribute('permitMetadataEdit');

            // If has manager role then permitMetadataEdit can't be overriden
            if (in_array($roleId, [Role::ROLE_ID_MANAGER])) {
                $permitMetadataEdit = $setting->getAttribute('permitMetadataEdit');
            }

            $defaultStages = explode(',', $setting->getAttribute('stages'));

            // create a role associated with this user group
            $userGroup = $this->newDataObject();
            $userGroup->setRoleId($roleId);
            $userGroup->setContextId($contextId);
            $userGroup->setPermitSelfRegistration($permitSelfRegistration);
            $userGroup->setPermitMetadataEdit($permitMetadataEdit);
            $userGroup->setDefault(true);

            // insert the group into the DB
            $userGroupId = $this->insertObject($userGroup);

            // Install default groups for each stage
            if (is_array($defaultStages)) { // test for groups with no stage assignments
                foreach ($defaultStages as $stageId) {
                    if (!empty($stageId) && $stageId <= WORKFLOW_STAGE_ID_PRODUCTION && $stageId >= WORKFLOW_STAGE_ID_SUBMISSION) {
                        UserGroupStage::create([
                            'contextId' => $contextId,
                            'userGroupId' => $userGroupId,
                            'stageId' => $stageId
                        ]);
                    }
                }
            }

            // add the i18n keys to the settings table so that they
            // can be used when a new locale is added/reloaded
            $this->updateSetting($userGroup->getId(), 'nameLocaleKey', $nameKey);
            $this->updateSetting($userGroup->getId(), 'abbrevLocaleKey', $abbrevKey);

            // install the settings in the current locale for this context
            foreach ($installedLocales as $locale) {
                $this->installLocale($locale, $contextId);
            }
        }

        return true;
    }

    /**
     * use the locale keys stored in the settings table to install the locale settings
     *
     * @param string $locale
     * @param int $contextId
     */
    public function installLocale($locale, $contextId = null)
    {
        $userGroups = $this->getByContextId($contextId);
        while ($userGroup = $userGroups->next()) {
            $nameKey = $this->getSetting($userGroup->getId(), 'nameLocaleKey');
            $this->updateSetting(
                $userGroup->getId(),
                'name',
                [$locale => __($nameKey, [], $locale)],
                'string',
                $locale,
                true
            );

            $abbrevKey = $this->getSetting($userGroup->getId(), 'abbrevLocaleKey');
            $this->updateSetting(
                $userGroup->getId(),
                'abbrev',
                [$locale => __($abbrevKey, [], $locale)],
                'string',
                $locale,
                true
            );
        }
    }

    /**
     * Remove all settings associated with a locale
     *
     * @param string $locale
     */
    public function deleteSettingsByLocale($locale)
    {
        return $this->update('DELETE FROM user_group_settings WHERE locale = ?', [$locale]);
    }

    /**
     * private function to assemble the SQL for searching users.
     *
     * @param string $searchType the field to search on.
     * @param string $search the keywords to search for.
     * @param string $searchMatch where to match (is, contains, startsWith).
     * @param array $params SQL parameter array reference
     *
     * @return string SQL search snippet
     */
    public function _getSearchSql($searchType, $search, $searchMatch, &$params)
    {
        $userDao = Repo::user()->dao;
        $hasUserSetting = "EXISTS(
            SELECT 0
            FROM user_settings
            WHERE user_id = u.user_id
                AND setting_name = '%s'
                AND LOWER(setting_value) LIKE LOWER(?)
        )";
        $searchTypeMap = [
            Identity::IDENTITY_SETTING_GIVENNAME => sprintf($hasUserSetting, Identity::IDENTITY_SETTING_GIVENNAME),
            Identity::IDENTITY_SETTING_FAMILYNAME => sprintf($hasUserSetting, Identity::IDENTITY_SETTING_FAMILYNAME),
            $userDao::USER_FIELD_USERNAME => 'LOWER(u.username) LIKE LOWER(?)',
            $userDao::USER_FIELD_EMAIL => 'LOWER(u.email) LIKE LOWER(?)',
            $userDao::USER_FIELD_AFFILIATION => sprintf($hasUserSetting, $userDao::USER_FIELD_AFFILIATION)
        ];

        $searchSql = '';
        $search = trim($search);
        if (strlen($search)) {
            if (!isset($searchTypeMap[$searchType])) {
                $terms = array_map(fn (string $term) => '%' . addcslashes($term, '%_') . '%', PKPString::regexp_split('/\s+/', $search));
                $filters = [];

                switch (get_class(DB::connection())) {
                    case MySqlConnection::class:
                        $concatSettingValue = "GROUP_CONCAT(setting_value SEPARATOR '')";
                        break;
                    case PostgresConnection::class:
                        $concatSettingValue = "STRING_AGG(setting_value, '')";
                        break;
                    default:
                        throw new DomainException('Unrecognized database');
                }

                $userSetting = "COALESCE((
                    SELECT ${concatSettingValue}
                    FROM user_settings
                    WHERE user_id = u.user_id
                    AND setting_name = '%s'
                ), '')";

                // Concat key user fields to search
                $filters[] = '(1 = 1' . str_repeat(
                    ' AND LOWER(' . $this->concat(
                        sprintf($userSetting, Identity::IDENTITY_SETTING_GIVENNAME),
                        sprintf($userSetting, Identity::IDENTITY_SETTING_FAMILYNAME),
                        'u.email',
                        sprintf($userSetting, $userDao::USER_FIELD_AFFILIATION),
                        'u.username'
                    ) . ') LIKE LOWER(?)',
                    count($terms)
                ) . ')';
                array_push($params, ...$terms);

                // Search the user interests
                $filters[] = '
                    EXISTS(
                        SELECT 0
                        FROM user_interests ui
                        INNER JOIN controlled_vocab_entry_settings cves
                            ON ui.controlled_vocab_entry_id = cves.controlled_vocab_entry_id
                        WHERE
                            u.user_id = ui.user_id
                            ' . str_repeat(' AND LOWER(cves.setting_value) LIKE LOWER(?)', count($terms)) . '
                    )';
                array_push($params, ...$terms);

                $searchSql .= 'AND (' . implode(' OR ', $filters) . ') ';
            } else {
                $filter = $searchTypeMap[$searchType];
                $searchSql = "AND ${filter}";
                switch ($searchMatch) {
                    case 'is':
                        $params[] = $search;
                        break;
                    case 'contains':
                        $params[] = '%' . $search . '%';
                        break;
                    case 'startsWith':
                        $params[] = $search . '%';
                        break;
                }
            }
        } else {
            switch ($searchType) {
                case $userDao::USER_FIELD_USERID:
                    $searchSql = ' AND u.user_id = ?';
                    break;
            }
        }

        return $searchSql;
    }

    //
    // Public helper methods
    //

    /**
     * Get a list of roles not able to change submissionMetadataEdit permission option.
     *
     * @return array
     */
    public static function getNotChangeMetadataEditPermissionRoles()
    {
        return [Role::ROLE_ID_MANAGER];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\security\UserGroupDAO', '\UserGroupDAO');
}
