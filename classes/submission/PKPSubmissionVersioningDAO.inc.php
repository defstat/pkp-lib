<?php

/**
 * @file classes/submission/PKPSubmissionVersioningDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PKPSubmissionVersioningDAO
 * @ingroup submission
 * @see PKPSubmissionVersioningDAO
 *
 * @brief Operations for retrieving and modifying PKPAuthor objects.
 */

import('lib.pkp.classes.submission.PKPAuthor');

abstract class PKPSubmissionVersioningDAO extends DAO {

	function getByParams($submissionId, $submissionVersion, $assocType, $assocId) {
		$params = array(
			(int) $submissionId,
			(int) $submissionVersion,
			(int) $assocType,
			(int) $assocId,
		);

		$result = $this->retrieve(
			'SELECT a.*
			FROM	submission_versioning a
			WHERE	a.submission_id = ? AND a.submission_version = ? AND a.assoc_type = ? and a.assoc_id = ?',
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	function _fromRow($row) {
		/** @var $submissionVersioning PKPSubmissionVersioning */
		$submissionVersioning = $this->newDataObject();
		$submissionVersioning->setAssocId($row['assoc_id']);
		$submissionVersioning->setAssocType($row['assoc_type']);
		$submissionVersioning->setSubmissionId($row['submission_id']);
		$submissionVersioning->setSubmissionVersion($row['submission_version']);

		return $submissionVersioning;
	}

	/**
	 * Get a new data object
	 * @return DataObject
	 */
	abstract function newDataObject();

	/**
	 * Insert a new PKPSubmissionVersioning object.
	 * @param $submissionVersioning PKPSubmissionVersioning
	 */
	function insertObject($submissionVersioning) {

		$returner = $this->update(
			'INSERT INTO submission_versioning (
				submission_id, submission_version, assoc_id, assoc_type
			) VALUES (
				?, ?, ?, ?
			)',
				array(
					(int) $submissionVersioning->getSubmissionId(),
					(int) $submissionVersioning->getSubmissionVersion(),
					(int) $submissionVersioning->getAssocId(),
					(int) $submissionVersioning->getAssocType(),
				)
		);

		return $returner;
	}

	/**
	 * Update an existing PKPSubmissionVersioning.
	 * @param $submissionVersioning PKPSubmissionVersioning
	 */
	function updateObject($submissionVersioning) {
		$returner = $this->update(
			'UPDATE	submission_versioning
			SET	submission_id = ?,
				submission_version = ?,
				assoc_id = ?,
				assoc_type = ?',
			array(
				(int) $submissionVersioning->getSubmissionId(),
				(int) $submissionVersioning->getSubmissionVersion(),
				(int) $submissionVersioning->getAssocId(),
				(int) $submissionVersioning->getAssocType(),
			)
		);

		return $returner;
	}

	/**
	 * Delete a submissionVersioning object
	 * @param $submissionVersioning PKPSubmissionVersioning
	 */
	function deleteObject($submissionVersioning) {
		$params = array(
			(int) $submissionVersioning->getSubmissionId(),
			(int) $submissionVersioning->getSubmissionVersion,
			(int) $submissionVersioning->getAssocType(),
			(int) $submissionVersioning->getAssocId(),
		);

		$returner = $this->update(
			'DELETE FROM submission_versioning
			WHERE submission_id = ? and submission_version = ? and assoc_type = ? and assoc_id = ?',
			$params
		);

		return $returner;
	}

}
