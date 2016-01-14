<?php

	if((extension_loaded('apc') || extension_loaded('apcu')) && ini_get('apc.enabled')) {
		$engine = 'Apc';
	} else {
		$engine = 'File';
	}

	Cache::config('short', array(
		'engine' => $engine,
		'duration' => '+1 hours', //This is set to 1 hour as the Salesforce Session time out is 2 hours
		'probability' => 100,
		'prefix' => 'salesforce_short_'
	));