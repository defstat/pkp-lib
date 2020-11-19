<?php

/**
 * @file classes/task/PublishSubmissions.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishSubmissions
 * @ingroup tasks
 *
 * @brief Class to published submissions scheduled for publication.
 */

import('lib.pkp.classes.scheduledTask.ScheduledTask');

class QueueWorkerSpawn extends ScheduledTask {

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	public function getName() {
		return __('admin.scheduledTask.queueWorkerSpawn');
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	public function executeActions() {
		try {
			$url = 'http://localhost:8088/index.php/publicknowledge/gateway/lockss';

			$ch = curl_init();
	
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CONNECTION_TIMEOUT, 3);
	
			$response = curl_exec($ch);
			curl_close($ch);
		} catch (Exception $e) {
			$i = 0;
		}

		return true;
	}
}


