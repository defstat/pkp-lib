<?php

/**
 * @file classes/query/Query.inc.php
 *
 * Copyright (c) 2016-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Query
 * @ingroup submission
 * @see QueryDAO
 *
 * @brief Class for Query.
 */

import('lib.pkp.classes.note.NoteDAO'); // Constants

class JobManager extends DataObject {

	/**
	 * Get query assoc type
	 * @return int ASSOC_TYPE_...
	 */
	function getContextId() {
		return $this->getData('contextId');
	}

	/**
	 * Set query assoc type
	 * @param $assocType int ASSOC_TYPE_...
	 */
	function setContextId($contextId) {
		$this->setData('contextId', $contextId);
	}

	// /**
	//  * Get query assoc type
	//  * @return int ASSOC_TYPE_...
	//  */
	// function getStatus() {
	// 	return $this->getData('contextId');
	// }

	// /**
	//  * Set query assoc type
	//  * @param $assocType int ASSOC_TYPE_...
	//  */
	// function setStatus($status) {
	// 	$this->setData('contextId', $contextId);
	// }
}


