<?php
use Illuminate\Queue\Jobs\DatabaseJobRecord;

/**
 * @file controllers/grid/admin/context/ContextGridHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextGridHandler
 * @ingroup controllers_grid_admin_context
 *
 * @brief Handle context grid requests.
 */

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.controllers.grid.admin.queueWorkers.QueueWorkerGridRow');
import('lib.pkp.classes.laravelIntegration.core.PKPLaravelWrapper');

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Capsule\Manager as Capsule;

class QueueWorkerGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		PKPLaravelWrapper::initialiseLaravel();
		parent::__construct();
		$this->addRoleAssignment(array(
			ROLE_ID_SITE_ADMIN),
			array('fetchGrid', 'fetchRow', 'runWorker', 'runWorkerOnce')
		);
	}


	//
	// Implement template methods from PKPHandler.
	//
	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.PolicySet');
		$rolePolicy = new PolicySet(COMBINING_PERMIT_OVERRIDES);

		import('lib.pkp.classes.security.authorization.RoleBasedHandlerOperationPolicy');
		foreach($roleAssignments as $role => $operations) {
			$rolePolicy->addPolicy(new RoleBasedHandlerOperationPolicy($request, $role, $operations));
		}
		$this->addPolicy($rolePolicy);

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);

		// Load user-related translations.
		AppLocale::requireComponents(
			LOCALE_COMPONENT_PKP_USER,
			LOCALE_COMPONENT_APP_MANAGER,
			LOCALE_COMPONENT_PKP_MANAGER,
			LOCALE_COMPONENT_PKP_ADMIN,
			LOCALE_COMPONENT_APP_ADMIN
		);

		$this->setTitle('admin.queueWorkers');

		// Grid actions.
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.AjaxModal');
		$this->addAction(
			new LinkAction(
				'runWorker',
				new AjaxModal(
					$router->url($request, null, null, 'runWorker', null, null),
					__('admin.queueWorkers.runWorker'),
					'modal_add_item',
					true,
					'context',
					['editContext']
				),
				__('admin.queueWorkers.runWorker'),
				'add_item'
			)
		);

		// $this->addAction(
		// 	new LinkAction(
		// 		'runWorker',
		// 		new AjaxModal(
		// 			$router->url($request, null, null, 'runWorkerOnce', null, null),
		// 			__('admin.queueWorkers.runWorker'),
		// 			'modal_add_item',
		// 			true,
		// 			'context',
		// 			['editContext']
		// 		),
		// 		__('admin.contexts.create'),
		// 		'add_item'
		// 	)
		// );

		//
		// Grid columns.
		//
		import('lib.pkp.controllers.grid.admin.queueWorkers.QueueWorkerGridCellProvider');
		$contextGridCellProvider = new QueueWorkerGridCellProvider();

		// Context name.
		$this->addColumn(
			new GridColumn(
				'name',
				'common.name',
				null,
				null,
				$contextGridCellProvider
			)
		);

		// Context path.
		$this->addColumn(
			new GridColumn(
				'urlPath',
				'context.path',
				null,
				null,
				$contextGridCellProvider
			)
		);
	}


	//
	// Implement methods from GridHandler.
	//
	/**
	 * @copydoc GridHandler::getRowInstance()
	 * @return UserGridRow
	 */
	protected function getRowInstance() {
		return new QueueWorkerGridRow();
	}

	/**
	 * @copydoc GridHandler::loadData()
	 */
	protected function loadData($request, $filter = null) {
		// Get all contexts.
		$jobs = Capsule::table('jobs')->get();

		$dataArray = array();
		foreach ($jobs as $job) {
			$dbJob = new DatabaseJobRecord((object) $job);
			$payload = json_decode($dbJob->payload);
			$data = unserialize($payload->data->command);

			$dataArray[] = $data;
		}


		return $dataArray;
	}


	// /**
	//  * @copydoc GridHandler::addFeatures()
	//  */
	// function initFeatures($request, $args) {
	// 	import('lib.pkp.classes.controllers.grid.feature.OrderGridItemsFeature');
	// 	return array(new OrderGridItemsFeature());
	// }

	/**
	 * Get the list of "publish data changed" events.
	 * Used to update the site context switcher upon create/delete.
	 * @return array
	 */
	function getPublishChangeEvents() {
		return array('updateHeader');
	}


	//
	// Public grid actions.
	//
	/**
	 * Add a new context.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function runWorker($args, $request) {
		PKPLaravelWrapper::initialiseLaravel();
		Artisan::call('queue:work', array('connection' => 'database', '--queue' => 'emailQueue'), null);
	}

	/**
	 * Add a new context.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function runWorkerOnce($args, $request) {
		PKPLaravelWrapper::initialiseLaravel();
		Artisan::call('queue:work', array('connection' => 'database', '--queue' => 'emailQueue', '--once' => true), null);
	}
}
