<?php

interface PKPQueuedJob {
	var $context;

	public function uiDisplay();

	public function setContext($context) {
		$this->context = $context;
	}
}
