<?php

use Illuminate\Console\Application;

use Illuminate\Queue\Console\WorkCommand;

use Illuminate\Support\Facades\Artisan;

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/tools/bootstrap.inc.php');

import('lib.pkp.classes.queue.PKPLaravelContainer');

class QueueWorkerCli extends CommandLineTool {

	/**
	 * Set the version numbers
	 */
	function execute() {
		// $laravelApp = Registry::get('laravelContainer'); /** @var $laravelApp PKPLaravelContainer */
		
		// $kernel = $laravelApp->make(Illuminate\Contracts\Console\Kernel::class);

		// $status = $kernel->handle(
		// 	$input = new Symfony\Component\Console\Input\ArgvInput,
		// 	new Symfony\Component\Console\Output\ConsoleOutput
		// );

		// $kernel->terminate($input, $status);

		// exit($status);
		// Artisan::call('queue:work', array('connection' => 'databaseQueueConnection', '--queue' => 'emailQueue'), null);
	}
}

$tool = new QueueWorkerCli(isset($argv) ? $argv : []);
$tool->execute();


