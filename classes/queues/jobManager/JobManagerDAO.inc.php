<?php

/**
 * @file classes/publication/PKPPublicationDAO.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPPublicationDAO
 * @ingroup core
 * @see DAO
 *
 * @brief Operations for retrieving and modifying publication objects.
 */
use Illuminate\Database\Capsule\Manager as Capsule;

import('lib.pkp.classes.db.SchemaDAO');
import('lib.pkp.classes.plugins.PKPPubIdPluginDAO');
import('lib.pkp.classes.queues.jobManager.JobManager');
import('lib.pkp.classes.services.PKPSchemaService'); // SCHEMA_ constants

class JobManagerDAO extends SchemaDAO {
	/** @copydoc SchemaDAO::$schemaName */
	public $schemaName = SCHEMA_JOB_MANAGER;

	/** @copydoc SchemaDAO::$tableName */
	public $tableName = 'job_manager';

	/** @copydoc SchemaDAO::$settingsTableName */
	public $settingsTableName = 'job_manager_settings';

	/** @copydoc SchemaDAO::$primaryKeyColumn */
	public $primaryKeyColumn = 'job_id';

	/** @var array List of properties that are stored in the controlled_vocab tables. */
	public $controlledVocabProps = [];

	/** @var array Maps schema properties for the primary table to their column names */
	var $primaryTableColumns = [
		'id' => 'job_id',
		'status' => 'status',
		'dateProcessed' => 'date_processed',
		'dateCreated' => 'date_created',
		'jobClass' => 'job_class',
		'contextId' => 'context_id',
	];

	/**
	 * Create a new DataObject of the appropriate class
	 *
	 * @return DataObject
	 */
	public function newDataObject() {
		return new JobManager();
	}

	/**
	 * Retrieve an announcement by announcement ID.
	 * @param $announcementId int
	 * @param $assocType int Optional assoc type
	 * @param $assocId int Optional assoc ID
	 * @return Announcement
	 */
	function getById($jobId) {
		$query = Capsule::table($this->tableName)->where($this->primaryKeyColumn, '=', (int) $jobId);

		if ($result = $query->first()) {
			return $this->_fromRow((array) $result);
		}
		return null;
	}

		/**
	 * Return a DataObject from a result row
	 *
	 * @param $primaryRow array The result row from the primary table lookup
	 * @return DataObject
	 */
	public function _fromRow($primaryRow) {
		$schemaService = Services::get('schema');
		$schema = $schemaService->get($this->schemaName);

		$object = $this->newDataObject();

		foreach ($this->primaryTableColumns as $propName => $column) {
			if (isset($primaryRow[$column])) {
				$object->setData(
					$propName,
					$this->convertFromDb($primaryRow[$column], $schema->properties->{$propName}->type)
				);
			}
		}

		return $object;
	}


	/**
	 * Retrieve an array of announcements matching a particular assoc ID.
	 * @param $assocType int ASSOC_TYPE_...
	 * @param $assocId int
	 * @return Generator Matching Announcements
	 */
	function getByContextId($contextId) {
		$result = Capsule::table($this->tableName)
			->where('context_id', '=', (int) $contextId)
			->orderByDesc('date_created')
			->get();
		foreach ($result as $row) {
			yield $this->_fromRow((array) $row);
		}
	}

	/**
	 * Get the ID of the last inserted announcement.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('job_manager', 'job_id');
	}
}
