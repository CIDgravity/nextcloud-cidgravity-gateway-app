<?php

return [
	'resources' => [
		'cidgravitygateway' => ['url' => '/cidgravitygateway']
	],
	'ocs' => [
		['name' => 'externalStorage#getExternalStorageConfigurationForSpecificFile', 'url' => '/get-external-storage-config', 'verb' => 'GET'],
		['name' => 'externalStorage#getMetadataForSpecificFile', 'url' => '/get-file-metadata', 'verb' => 'GET']
	],
];
