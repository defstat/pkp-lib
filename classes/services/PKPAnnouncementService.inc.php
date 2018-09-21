<?php
/**
 * @file classes/services/PKPAnnouncementService.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPAnnouncementService
 * @ingroup services
 *
 * @brief Helper class that encapsulates author business logic
 */

namespace PKP\Services;

use \Application;
use \DBResultRange;
use \DAOResultFactory;
use \DAORegistry;
use \PKP\Services\EntityProperties\PKPBaseEntityPropertyService;

class PKPAnnouncementService extends PKPBaseEntityPropertyService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct($this);
	}

	/**
	 * Get announcements
	 *
	 * @param int $contextId
	 * @param array $args {
	 * 		@option string type
	 * 		@option string searchPhrase
	 * 		@option int count
	 * 		@option int offset
	 * }
	 *
	 * @return array
	 */
	public function getAnnouncements($contextId, $args = array()) {
		$announcementsListQB = $this->_buildGetAnnouncementsQueryObject($contextId, $args);
		$announcementsListQO = $announcementsListQB->get();
		$range = new DBResultRange(isset($args['count'])?$args['count']:0, null, isset($args['offset'])?$args['offset']:0);
		$announcementDAO = DAORegistry::getDAO('AnnouncementDAO');
		$result = $announcementDAO->retrieveRange($announcementsListQO->toSql(), $announcementsListQO->getBindings(), $range);
		$queryResults = new DAOResultFactory($result, $announcementDAO, '_fromRow');

		return $queryResults->toArray();
	}

	/**
	 * Get max count of announcements matching a query request
	 *
	 * @see self::getAnnouncements()
	 * @return int
	 */
	public function getAnnouncementsMax($contextId, $args = array()) {
		$announcementsListQB = $this->_buildGetAnnouncementsQueryObject($contextId, $args);
		$countQO = $announcementsListQB->countOnly()->get();
		$countRange = new DBResultRange($args['count'], 1);
		$announcementDAO = DAORegistry::getDAO('AnnouncementDAO');
		$countResult = $announcementDAO->retrieveRange($countQO->toSql(), $countQO->getBindings(), $countRange);
		$countQueryResults = new DAOResultFactory($countResult, $announcementDAO, '_fromRow');

		return (int) $countQueryResults->getCount();
	}

	/**
	 * Build the announcement query object for getAnnouncements requests
	 *
	 * @see self::getAnnouncements()
	 * @return object Query object
	 */
	private function _buildGetAnnouncementsQueryObject($contextId, $args = array()) {

		$defaultArgs = array(
			'typeId' => null,
			'searchPhrase' => null,
			'count' => 20,
			'offset' => 0,
		);

		$args = array_merge($defaultArgs, $args);

		$announcementListQB = new QueryBuilders\AnnouncementListQueryBuilder($contextId);
		$announcementListQB
			->filterByTypeId($args['typeId'])
			->searchPhrase($args['searchPhrase']);

		\HookRegistry::call('Announcement::getAnnouncements::queryBuilder', array($announcementListQB, $contextId, $args));

		return $announcementListQB;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($announcement, $props, $args = null) {
		$request = $args['request'];

		$values = array();
		foreach ($props as $prop) {
			switch ($prop) {
				case 'id':
				case 'typeId':
					$values[$prop] = (int) $announcement->getData($prop);
					break;
				default:
					$values[$prop] = $announcement->getData($prop);
					break;
			}

			\HookRegistry::call('Announcement::getProperties::values', array(&$values, $announcement, $props, $args));
		}

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($announcement, $args = null) {
		$props = array (
			'id','title','datePosted','typeId','dateExpire',
		);

		\HookRegistry::call('Announcement::getProperties::summaryProperties', array(&$props, $announcement, $args));

		return $this->getProperties($announcement, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($announcement, $args = null) {
		$props = $this->getSummaryProperties($announcement, $args);

		\HookRegistry::call('Announcement::getProperties::fullProperties', array(&$props, $announcement, $args));

		return $this->getProperties($announcement, $props, $args);
	}
}
