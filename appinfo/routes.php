<?php

return [
	'resources' => [
		'cidgravity_gateway' => ['url' => '/cidgravity_gateway']
	],
	'ocs' => [
		['name' => 'externalStorage#getExternalStorageConfigurationForSpecificFile', 'url' => '/get-external-storage-config', 'verb' => 'GET'],
		['name' => 'externalStorage#getMetadataForSpecificFile', 'url' => '/get-file-metadata', 'verb' => 'GET']
	],
];
