<?php
/**
 * @file classes/services/QueryBuilders/PKPAnnouncementQueryBuilder.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPAnnouncementQueryBuilder
 * @ingroup query_builders
 *
 * @brief Class for building database queries for announcements
 */

namespace PKP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;
use PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface;

class PKPJobManagerQueryBuilder implements EntityQueryBuilderInterface {

	/** @var array get announcements for one or more contexts */
	protected $contextIds = [];

	/** @var string get announcements matching one or more words in this phrase */
	protected $searchPhrase = '';

	/** @var array get announcements with one of these typeIds */
	protected $typeIds = [];

	/**
	 * Set contextIds filter
	 *
	 * @param array|int $contextIds
	 * @return \PKP\Services\QueryBuilders\PKPAnnouncementQueryBuilder
	 */
	public function filterByContextIds($contextIds) {
		$this->contextIds = is_array($contextIds) ? $contextIds : [$contextIds];
		return $this;
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getCount()
	 */
	public function getCount() {
		return $this
			->getQuery()
			->select('a.job_id')
			->get()
			->count();
	}

	/**
	 * @copydoc PKP\Services\QueryBuilders\Interfaces\EntityQueryBuilderInterface::getIds()
	 */
	public function getIds() {
		return $this
			->getQuery()
			->select('a.job_id')
			->pluck('a.job_id')
			->toArray();
	}

	/**
	 * Execute query builder
	 *
	 * @return object Query object
	 */
	public function getQuery() {
		$this->columns = ['a.*'];
		$q = Capsule::table('job_manager as a');

		if (!empty($this->contextIds)) {
			$q->whereIn('a.context_id', $this->contextIds);
		}

		$q->orderBy('a.date_created', 'desc');
		$q->groupBy('a.job_id');

		// Add app-specific query statements
		\HookRegistry::call('JobManager::getMany::queryObject', array(&$q, $this));

		$q->select($this->columns);

		return $q;
	}
}
