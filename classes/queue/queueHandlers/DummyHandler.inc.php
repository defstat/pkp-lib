<?php
/**
 * @file classes/queue/queueHandlers/DummyHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DummyHandler
 * @ingroup queueHandlers
 *
 * @brief Example handler for queue infrastructure job
 */

use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Capsule\Manager as Queue;

/**
 * @param mixed $name
 */
class DummyHandler {

	/**
	 * @param DatabaseJob $job
	 * @param mixed $data
	 */
    public function fire($job, $data) {
		try	{
			if ($job->attempts() > 3) {
				$job->delete();
			}
			echo $job->getJobId() . $data['string'] . " - Attempt: " . $job->attempts() . "\n";
			throw new \Exception("Error Processing Request", 1);
			
		} catch (Exception $e) {
			$job->fail($e);
		} 
        Queue:: push('DummyHandler', array('string'=>'test ' . date('r')));
				Queue::push('DummyHandler', array('string'=>'test2 ' . date('r')));
				Queue::push('DummyHandler', array('string'=>'test3 ' . date('r')));
    }
}
