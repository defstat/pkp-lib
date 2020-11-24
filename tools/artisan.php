<?php

use Illuminate\Console\Application;

use Illuminate\Queue\Console\WorkCommand;

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/tools/bootstrap.inc.php');

import('lib.pkp.classes.laravelintegration.core.PKPLaravelContainer');
import('lib.pkp.classes.laravelintegration.core.PKPLaravelWrapper');

class ArtisanCli extends CommandLineTool {

	/**
	 * Set the version numbers
	 */
	function execute() {
		$laravelApp = PKPLaravelWrapper::initialiseLaravel();

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
