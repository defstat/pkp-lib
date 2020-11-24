<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Queue\Connectors\DatabaseConnector;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

use PKP\Services\PKPJobManagerService;

import('lib.pkp.classes.laravelintegration.core.PKPLaravelContainer');
import('lib.pkp.classes.laravelintegration.core.PKPLaravelConsoleKernel');
import('lib.pkp.classes.laravelintegration.core.PKPLaravelExceptionHandler');

class PKPLaravelWrapper {
	public static function initialiseLaravel() {
		$laravelApp = Registry::get('laravelContainer'); /** @var $laravelApp PKPLaravelContainer */

		if ($laravelApp) {
			return $laravelApp;
		}

		self::classLoader();
		self::createLaravelInstance();

		return Registry::get('laravelContainer');
	}

	private static function classLoader() {
		$loader = new Nette\Loaders\RobotLoader;

		// directories to be indexed by RobotLoader (including subdirectories)
		$loader->addDirectory(BASE_SYS_DIR . '/classes');
		$loader->addDirectory(BASE_SYS_DIR . '/lib/pkp/classes');
		$loader->addDirectory(BASE_SYS_DIR . '/controllers');
		$loader->addDirectory(BASE_SYS_DIR . '/lib/pkp/controllers');

		// use 'temp' directory for cache
		$loader->setTempDirectory(BASE_SYS_DIR . '/cache/t_cache');
		$loader->register(); // Run the RobotLoader
	}

	private static function createLaravelInstance() {
		$laravelApp = new PKPLaravelContainer();

		$connection = Capsule::schema()->getConnection();
		$resolver = new \Illuminate\Database\ConnectionResolver(['database' => $connection]);

		$laravelApp['db'] = $resolver;

		$laravelApp->singleton(
			Illuminate\Contracts\Console\Kernel::class,
			PKPLaravelConsoleKernel::class
		);

		$laravelApp->singleton(
			Illuminate\Contracts\Debug\ExceptionHandler::class,
			PKPLaravelExceptionHandler::class
		);

		if (! $laravelApp->hasBeenBootstrapped()) {
			$laravelApp->bootstrapWith($laravelApp->bootstrapers());
		}

		$queue = new Queue($laravelApp);

		$manager = $queue->getQueueManager();

		$connection = Capsule::schema()->getConnection();
		$resolver = new \Illuminate\Database\ConnectionResolver(['database' => $connection]);
		$manager->addConnector('database', function () use ($resolver) {
			return new DatabaseConnector($resolver);
		});

		$manager->before(function (JobProcessing $event) {
			// $payload = json_decode($event->job->getRawBody());
			// $data = unserialize($payload->data->command);

			// $pkpJobManager = $data->getPKPJobId();

			// $jobManagerService = Services::get('jobManager'); /** @var $jobManagerService PKPJobManagerService */
			// $job = $jobManagerService->get($pkpJobManager->getId());

			// $job->setData('status', 2);
			// $job->setData('dateProcessed', Core::getCurrentDate());

			// $jobManagerService->edit($job, array(), Application::get()->getRequest());
		});

		$manager->after(function (JobProcessed $event) {
			// $payload = json_decode($event->job->getRawBody());
			// $data = unserialize($payload->data->command);

			// $pkpJobManager = $data->getPKPJobId();

			// $jobManagerService = Services::get('jobManager'); /** @var $jobManagerService PKPJobManagerService */
			// $job = $jobManagerService->get($pkpJobManager->getId());

			// $job->setData('status', 1);
			// $job->setData('dateProcessed', Core::getCurrentDate());

			// $jobManagerService->edit($job, array(), Application::get()->getRequest());
		});

		$manager->failing(function (JobProcessed $event) {
			// $payload = json_decode($event->job->getRawBody());
			// $data = unserialize($payload->data->command);

			// $pkpJobManager = $data->getPKPJobId();

			// $jobManagerService = Services::get('jobManager'); /** @var $jobManagerService PKPJobManagerService */
			// $job = $jobManagerService->get($pkpJobManager->getId());

			// $job->setData('status', 3);
			// $job->setData('dateProcessed', Core::getCurrentDate());

			// $jobManagerService->edit($job, array(), Application::get()->getRequest());
		});

		$manager->looping(function($input) {
			$i = 0;
		});

		$manager->stopping(function($input) {
			$i = 0;
		});

		$queue->setAsGlobal();

		Registry::set('laravelContainer', $laravelApp);
	}
}
