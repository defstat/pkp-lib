<?php
/**
 * @file classes/services/PKPAnnouncementService.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PKPAnnouncementService
 * @ingroup services
 *
 * @brief Helper class that encapsulates business logic for publications
 */

namespace PKP\Services;

use \Core;
use \DAOResultFactory;
use \DAORegistry;
use \Services;
use \PKP\Services\interfaces\EntityPropertyInterface;
use \PKP\Services\interfaces\EntityReadInterface;
use \PKP\Services\interfaces\EntityWriteInterface;
use PKP\Services\QueryBuilders\PKPJobManagerQueryBuilder;

import('lib.pkp.classes.db.DBResultRange');

class PKPJobManagerService implements EntityPropertyInterface, EntityReadInterface, EntityWriteInterface {

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::get()
	 */
	public function get($jobId) {
		return DAORegistry::getDAO('JobManagerDAO')->getById($jobId);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getCount()
	 */
	public function getCount($args = []) {
		return $this->getQueryBuilder($args)->getCount();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getIds()
	 */
	public function getIds($args = []) {
		return $this->getQueryBuilder($args)->getIds();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMany()
	 */
	public function getMany($args = []) {
		$range = null;
		if (isset($args['count'])) {
			import('lib.pkp.classes.db.DBResultRange');
			$range = new \DBResultRange($args['count'], null, isset($args['offset']) ? $args['offset'] : 0);
		}
		// Pagination is handled by the DAO, so don't pass count and offset
		// arguments to the QueryBuilder.
		if (isset($args['count'])) unset($args['count']);
		if (isset($args['offset'])) unset($args['offset']);
		$jobManagerQO = $this->getQueryBuilder($args)->getQuery();
		$jobManagerDao = DAORegistry::getDAO('JobManagerDAO');
		$result = $jobManagerDao->retrieveRange($jobManagerQO->toSql(), $jobManagerQO->getBindings(), $range);
		$queryResults = new DAOResultFactory($result, $jobManagerDao, '_fromRow');

		return $queryResults->toIterator();

	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMax()
	 */
	public function getMax($args = null) {
		// Don't accept args to limit the results
		if (isset($args['count'])) unset($args['count']);
		if (isset($args['offset'])) unset($args['offset']);
		return (int) $this->getQueryBuilder($args)->getCount();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getQueryBuilder()
	 */
	public function getQueryBuilder($args = []) {

		$defaultArgs = [
			'contextIds' => null,
			'searchPhrase' => '',
			'typeIds' => null,
		];

		$args = array_merge($defaultArgs, $args);

		$jobManagerQB = new PKPJobManagerQueryBuilder();
		if (!empty($args['contextIds'])) {
			$jobManagerQB->filterByContextIds($args['contextIds']);
		}
		// if (!empty($args['searchPhrase'])) {
		// 	$announcementQB->searchPhrase($args['searchPhrase']);
		// }
		// if (!empty($args['typeIds'])) {
		// 	$announcementQB->filterByTypeIds($args['typeIds']);
		// }

		\HookRegistry::call('JobManager::getMany::queryBuilder', [&$jobManagerQB, $args]);

		return $jobManagerQB;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($job, $props, $args = null) {
		$request = $args['request'];
		$jobManagerContext = $args['jobManagerContext'];
		$dispatcher = $request->getDispatcher();

		$values = [];

		foreach ($props as $prop) {
			switch ($prop) {
				case '_href':
					$values[$prop] = $dispatcher->url(
						$request,
						ROUTE_API,
						$jobManagerContext->getData('urlPath'),
						'jobManager/' . $job->getId()
					);
					break;
				default:
					$values[$prop] = $job->getData($prop);
					break;
			}
		}

		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_ANNOUNCEMENT, $values, $jobManagerContext->getSupportedFormLocales());

		\HookRegistry::call('JobManager::getProperties', [&$values, $job, $props, $args]);

		ksort($values);

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($job, $args = null) {
		$props = Services::get('schema')->getSummaryProps(SCHEMA_JOB_MANAGER);

		return $this->getProperties($job, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($job, $args = null) {
		$props = Services::get('schema')->getFullProps(SCHEMA_JOB_MANAGER);

		return $this->getProperties($job, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityWriteInterface::validate()
	 */
	public function validate($action, $props, $allowedLocales, $primaryLocale) {
		\AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_APP_MANAGER
		);
		$schemaService = Services::get('schema');

		import('lib.pkp.classes.validation.ValidatorFactory');
		$validator = \ValidatorFactory::make(
			$props,
			$schemaService->getValidationRules(SCHEMA_JOB_MANAGER, $allowedLocales),
			[
				'dateExpire.date_format' => __('stats.dateRange.invalidDate'),
			]
		);

		// Check required fields if we're adding a context
		\ValidatorFactory::required(
			$validator,
			$action,
			$schemaService->getRequiredProps(SCHEMA_JOB_MANAGER),
			$schemaService->getMultilingualProps(SCHEMA_JOB_MANAGER),
			$allowedLocales,
			$primaryLocale
		);

		// Check for input from disallowed locales
		\ValidatorFactory::allowedLocales($validator, $schemaService->getMultilingualProps(SCHEMA_JOB_MANAGER), $allowedLocales);

		if ($validator->fails()) {
			$errors = $schemaService->formatValidationErrors($validator->errors(), $schemaService->get(SCHEMA_JOB_MANAGER), $allowedLocales);
		}

		\HookRegistry::call('JobManager::validate', array(&$errors, $action, $props, $allowedLocales, $primaryLocale));

		return $errors;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::add()
	 */
	public function add($job, $request) {
		$job->setData('dateCreated', Core::getCurrentDate());
		DAORegistry::getDao('JobManagerDAO')->insertObject($job);
		\HookRegistry::call('JobManager::add', [&$job, $request]);

		return $job;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::edit()
	 */
	public function edit($job, $params, $request) {

		$newJob = DAORegistry::getDAO('JobManagerDAO')->newDataObject();
		$newJob->_data = array_merge($job->_data, $params);

		\HookRegistry::call('JobManager::edit', array(&$newJob, $job, $params, $request));

		DAORegistry::getDAO('JobManagerDAO')->updateObject($newJob);
		$newJob = $this->get($newJob->getId());

		return $newJob;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::delete()
	 */
	public function delete($job) {
		\HookRegistry::call('JobManager::delete::before', array(&$job));
		DAORegistry::getDao('JobManagerDAO')->deleteObject($job);
		\HookRegistry::call('JobManager::delete', array(&$job));
	}
}
