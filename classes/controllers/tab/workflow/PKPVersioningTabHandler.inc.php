<?php

/**
 * @file controllers/tab/workflow/PKPVersioningTabHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewRoundTabHandler
 * @ingroup controllers_tab_workflow
 *
 * @brief Handle AJAX operations for version tabs on production stages workflow pages.
 */

// Import the base Handler.
import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');

class PKPVersioningTabHandler extends Handler {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}


	//
	// Extended methods from Handler
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		// We need a submission version id in request.
		import('lib.pkp.classes.security.authorization.internal.VersioningRequiredPolicy');
		$this->addPolicy(new VersioningRequiredPolicy($request, $args));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * create new submission version
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function newVersion($args, $request){

		$submissionId = (int)$request->getUserVar('submissionId');
		$submissionDao = Application::getSubmissionDAO();
		$oldVersion = $submissionDao->getLatestVersionId($submissionId);

		// get data of the old version
		$submission = $submissionDao->getById($submissionId, null, false, $oldVersion);
		$authorDao = DAORegistry::getDAO('AuthorDAO');
		$authors = $authorDao->getBySubmissionId($submissionId, true, false, $oldVersion);

		// save new submission version without publication date
		$newVersion = $oldVersion+1;
		$submission->setData('submissionVersion', $newVersion);
		$submission->setDatePublished(null);
		$submissionDao->updateObject($submission);

		// copy the authors from old version to new version
		foreach($authors as $author) {
			$author->setVersion($newVersion);
			$authorDao->insertObject($author, true);
		}

		// reload page to display new version
		$dispatcher = $this->getDispatcher();
		$redirectUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'index', array($submission->getId(), $submission->getStageId()));
		return $request->redirectUrlJson($redirectUrl);

	}

	/**
	* Make a copy of the file to the specified file stage
	* @param $context Context
	* @param $submissionFile SubmissionFile
	* @param $fileStage int SUBMISSION_FILE_...
	* @return newFileId int
	*/
	function copyFile($context, $submissionFile, $fileStage){
		import('lib.pkp.classes.file.SubmissionFileManager');
		$submissionFileManager = new SubmissionFileManager($context->getId(), $submissionFile->getSubmissionId());
		$fileId = $submissionFile->getFileId();
		$revision = $submissionFile->getRevision();
		list($newFileId, $newRevision) = $submissionFileManager->copyFileToFileStage($fileId, $revision, $fileStage, null, true);
		return $newFileId;
	}

	/**
	 * @see PKPHandler::setupTemplate
	 */
	function setupTemplate($request) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR);
		parent::setupTemplate($request);
	}

	/**
	 * Handle version info (tab content).
	 * @param $request PKPRequest
	 * @param $args array
	 * @return JSONMessage JSON object
	 */
	function versioning($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);

		// Retrieve the authorized submission, stage id and submission version.
		$submission = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION);
		$stageId = $this->getAuthorizedContextObject(ASSOC_TYPE_WORKFLOW_STAGE);
		$submissionVersionId = $this->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_VERSION);

		// Get submission version
		$submissionDao = Application::getSubmissionDAO();
		$submissionVersion = $submissionDao->getById($submission->getId(), null, false, $submissionVersionId);

		// Add variables to the template.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('submission', $submissionVersion);
		$templateMgr->assign('stageId', $stageId);
		$templateMgr->assign('submissionVersion', $submissionVersionId);
		$templateMgr->assign('isPublished', $submissionVersion->getDatePublished() ? true : false);

		return $templateMgr->fetchJson('workflow/version.tpl');
	}
}

?>
