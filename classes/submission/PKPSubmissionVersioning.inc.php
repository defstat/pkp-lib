<?php

/**
 * @defgroup submission Submission
 * The abstract concept of a submission is implemented here, and extended
 * in each application with the specifics of that content model, i.e.
 * Articles in OJS, Papers in OCS, and Monographs in OMP.
 */

/**
 * @file classes/submission/Submission.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Submission
 * @ingroup submission
 * @see SubmissionDAO
 *
 * @brief The Submission class implements the abstract data model of a
 * scholarly submission.
 */

abstract class PKPSubmissionVersioning extends DataObject {

	function getAssocType() {
		return $this->getData('assocType');
	}

	function setAssocType($assocType) {
		return $this->setData('assocType', $assocType);
	}

	function getAssocId() {
		return $this->getData('assocId');
	}

	function setAssocId($assocId) {
		return $this->setData('assocId', $assocId);
	}

	function getSubmissionVersion() {
		return $this->getData('submissionVersion');
	}

	function setSubmissionVersion($submissionVersion) {
		return $this->setData('submissionVersion', $submissionVersion);
	}

	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	function setSubmissionId($submissionId) {
		return $this->setData('submissionId', $submissionId);
	}
}
