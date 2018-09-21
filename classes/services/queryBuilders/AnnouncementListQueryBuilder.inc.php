<?php
/**
 * @file classes/services/QueryBuilders/AnnouncementListQueryBuilder.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementListQueryBuilder
 * @ingroup query_builders
 *
 * @brief Submission list Query builder
 */

namespace PKP\Services\QueryBuilders;

use Illuminate\Database\Capsule\Manager as Capsule;

class AnnouncementListQueryBuilder extends BaseQueryBuilder {

	/** @var int Context ID */
	protected $contextId = null;

	/** @var array list of columns for query */
	protected $columns = array();

	/** @var string order by column */
	protected $orderColumn = 'a.date_posted';

	/** @var string order by direction */
	protected $orderDirection = 'DESC';

	/** @var integer  */
	protected $typeIds = null;

	/** @var string search phrase */
	protected $searchPhrase = null;

	/** @var bool whether to return only a count of results */
	protected $countOnly = null;

	/**
	 * Constructor
	 *
	 * @param $contextId int context ID
	 */
	public function __construct($contextId) {
		parent::__construct();
		$this->contextId = $contextId;
	}

	/**
	 * Set type filter
	 *
	 * @param $typeIds integer
	 *
	 * @return \PKP\Services\QueryBuilders\AnnouncementListQueryBuilder
	 */
	public function filterByType($typeIds) {
		$this->typeIds = $typeIds;
		return $this;
	}

	/**
	 * Set query search phrase
	 *
	 * @param $phrase string
	 *
	 * @return \PKP\Services\QueryBuilders\AnnouncementListQueryBuilder
	 */
	public function searchPhrase($phrase) {
		$this->searchPhrase = $phrase;
		return $this;
	}

	/**
	 * Whether to return only a count of results
	 *
	 * @param $enable bool
	 *
	 * @return \PKP\Services\QueryBuilders\AnnouncementListQueryBuilder
	 */
	public function countOnly($enable = true) {
		$this->countOnly = $enable;
		return $this;
	}

	/**
	 * Execute query builder
	 *
	 * @return object Query object
	 */
	public function get() {
		$locale = \AppLocale::getLocale();

		$this->columns[] = 'a.*';
		$q = Capsule::table('announcements as a')
					->leftJoin('announcement_settings as as', 'as.announcement_id', '=', 'a.announcement_id')
					->where('a.assoc_id','=', $this->contextId)
					->where('a.assoc_type', '=', \Application::getContextAssocType());

		// types
		if (!is_null($this->typeIds)) {
			$q->whereIn('a.type_id', $this->typeIds);
		}

		// search phrase
		if (!empty($this->searchPhrase)) {
			$words = explode(' ', $this->searchPhrase);
			if (count($words)) {
				foreach ($words as $word) {
					$word = strtolower(addcslashes($word, '%_'));
					$q->where(function($q) use ($word) {
						$q->where(Capsule::raw('lower(a.announcement_id)'), '=', "$word")
							->orWhere(function($q) use ($word) {
								$q->where('as.setting_name', 'title');
								$q->where(Capsule::raw('lower(as.setting_value)'), 'LIKE', "%{$word}%");
							})
							->orWhere(function($q) use ($word) {
								$q->where('as.setting_name', 'descriptionShort');
								$q->where(Capsule::raw('lower(as.setting_value)'), 'LIKE', "%{$word}%");
							})
							->orWhere(function($q) use ($word) {
								$q->where('as.setting_name', 'description');
								$q->where(Capsule::raw('lower(as.setting_value)'), 'LIKE', "%{$word}%");
							});
					});
				}
			}
		}

		\HookRegistry::call('Announcement::getAnnouncements::queryObject', array(&$q, $this));

		if (!empty($this->countOnly)) {
			$q->select(Capsule::raw('count(*) as announcement_count'))
				->groupBy('a.announcement_id');
		} else {
			$q->select($this->columns)
				->groupBy('a.announcement_id')
				->orderBy($this->orderColumn, $this->orderDirection);
		}

		return $q;
	}
}
