<?php
use Illuminate\Queue\Jobs\Job;


/**
 * @file classes/queue/queueHandlers/EmailTemplateQueueHandler.inc.php
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
import('lib.pkp.classes.mail.MailTemplate');

use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;

/**
 * @param mixed $name
 */
class EmailTemplateQueueJob implements ShouldQueue {

	use InteractsWithQueue, Queueable;

	public $emailTemplate;
	private $pkpRequest;

	private $pkpJobId;

	public function getPKPJobId() {
		return $this->pkpJobId;
	}

	public function setPKPJobId($pkpJobId) {
		$this->pkpJobId = $pkpJobId;
	}

	function __construct($_emailTemplate, $_request) {
		$this->emailTemplate = $_emailTemplate;
		$this->pkpRequest = $_request;
	}

	public function handle()
	{
		try	{
			if (!$this->emailTemplate->send($this->pkpRequest)) {
				import('classes.notification.NotificationManager');
				$notificationMgr = new NotificationManager();
				$notificationMgr->createTrivialNotification($this->pkpRequest->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
			}

		} catch (Exception $e) {
			$this->job->fail();
		}
	}
}
