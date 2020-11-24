<?php

use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\InteractsWithTime;

use PKP\Services\PKPJobManagerService;

import('lib.pkp.classes.queues.jobManager.JobManager');
import('lib.pkp.classes.queues.jobManager.JobManagerDAO');
import('lib.pkp.classes.context.Context');

class PKPQueueJobWrapper {
	use InteractsWithTime;

	/**
	 * @var $context Context
	 */
	var $context;
	var $queuedJob;
	var $request;

	public function __construct($context, $queuedJob, $request) {
		$this->context = $context;
		$this->queuedJob = $queuedJob;
		$this->request = $request;
	}

	public function setContext($context) {
		$this->context = $context;
	}

	/**
	 * @param string $queue
	 * @param PKPQueuedJob $queuedJob
	 * @param string $connectionName
	 *
	 */
	public function queueJob($queue, $connectionName) {
		try	{
			$jobDao = new JobManagerDAO();

			$job = new JobManager();
			$job->setData('status', 0);
			$job->setData('dateCreated', $this->currentTime());
			$job->setData('dateProcessed', null);
			$job->setData('jobClass', get_class($this->queuedJob));
			$job->setData('contextId', $this->context->getId());

			$jobManagerService = Services::get('jobManager'); /** @var PKPJobManagerService $jobManagerService */

			$jobId = $jobManagerService->add($job, $this->request);

			$this->queuedJob->setPKPJobId($jobId);

			$queueResult = null;

			$contextPath = $this->context->getPath();
			$queueName = $contextPath.'_'.$queue;

			$queueResult = Queue::connection($connectionName)->pushOn($queueName, $this->queuedJob);
		} catch (Exception $e) {

		}

	}

	/**
     * Create an array to insert for the given job.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  int  $availableAt
     * @param  int  $attempts
     * @return array
     */
    protected function buildDatabaseRecord()
    {
        return [
			'status' => 0,
			'date_created' => $this->currentTime(),
			'date_processed' => null,
			'job_class' => $this->currentTime(),
        ];
	}

	/**
     * Push a raw payload to the database with a given delay.
     *
     * @param  string|null  $queue
     * @param  string  $payload
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  int  $attempts
     * @return mixed
     */
    protected function pushToDatabase($queue, $payload, $delay = 0, $attempts = 0)
    {
        return $this->database->table($this->table)->insertGetId($this->buildDatabaseRecord(
            $this->getQueue($queue), $payload, $this->availableAt($delay), $attempts
        ));
    }
}
