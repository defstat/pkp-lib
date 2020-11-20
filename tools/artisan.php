<?php

use Illuminate\Console\Application;

use Illuminate\Queue\Console\WorkCommand;

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/tools/bootstrap.inc.php');

import('lib.pkp.classes.laravelintegration.core.PKPLaravelContainer');

class ArtisanCli extends CommandLineTool {

	/**
	 * Set the version numbers
	 */
	function execute() {
        $laravelApp = Registry::get('laravelContainer'); /** @var $laravelApp PKPLaravelContainer */

		
		$kernel = $laravelApp->make(Illuminate\Contracts\Console\Kernel::class);

		$status = $kernel->handle(
			$input = new Symfony\Component\Console\Input\ArgvInput,
			new Symfony\Component\Console\Output\ConsoleOutput
		);

		$kernel->terminate($input, $status);

		exit($status);
	}
}

$tool = new ArtisanCli(isset($argv) ? $argv : []);
$tool->execute();