<?php

/**
 * @file classes/task/LaravelSchedulerRun.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class LaravelSchedulerRun
 * @ingroup tasks
 *
 * @brief Class to published submissions scheduled for publication.
 */
use Illuminate\Support\Facades\Artisan;

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class LaravelSchedulerRun extends ScheduledTask {

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	public function getName() {
		return __('admin.scheduledTask.laravelSchedulerRun');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	public function executeActions() {
		PKPLaravelWrapper::initialiseLaravel();

		// Artisan::call('schedule:run', array(), null);
		Artisan::call('queue:work', array('connection' => 'database', '--queue' => 'emailQueue', '--once' => true), null);

		return true;
	}
}


