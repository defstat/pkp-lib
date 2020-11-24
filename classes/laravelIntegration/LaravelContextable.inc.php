<?php

import('lib.pkp.classes.laravelintegration.core.PKPLaravelWrapper');

trait LaravelContextable {
	private function initialiseContext() {
		PKPLaravelWrapper::initialiseLaravel();
	}
}
