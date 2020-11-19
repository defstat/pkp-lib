<?php
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

use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Capsule\Manager as Queue;
use Symfony\Component\Serializer\Serializer;

/**
 * @param mixed $name
 */
class EmailTemplateQueueHandler {

	/**
	 * @param DatabaseJob $job
	 * @param mixed $data
	 */
    // public function fire($job, $data) {
	// 	try	{
	// 		if ($job->attempts() > 3) {
	// 			$job->delete();
	// 		}

	// 		$mail = (MailTemplate)$data['emailTemplate'];
	// 		// $request = $data->request;
	// 		$request = $data->request;

	// 		$result = false;
	// 		if ($request) {
	// 			$result = $mail->send($request);
	// 		} else {
	// 			$result = $mail->send();
	// 		}

	// 		if (!$result) {
	// 			import('classes.notification.NotificationManager');
	// 			$notificationMgr = new NotificationManager();
	// 			$notificationMgr->createTrivialNotification($request->getUser()->getId(), NOTIFICATION_TYPE_ERROR, array('contents' => __('email.compose.error')));
	// 		}
			
	// 	} catch (Exception $e) {
	// 		$job->fail($e);
	// 	} 
    //     Queue:: push('DummyHandler', array('string'=>'test ' . date('r')));
	// 			Queue::push('DummyHandler', array('string'=>'test2 ' . date('r')));
	// 			Queue::push('DummyHandler', array('string'=>'test3 ' . date('r')));
    // }
}
