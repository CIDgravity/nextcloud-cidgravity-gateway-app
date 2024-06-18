<?php

/**
 * @copyright Copyright (c) 2024, CIDgravity (https://cidgravity.com)
 *
 * @author Florian RUEN <florian.ruen@cidgravity.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
*/

namespace OCA\CidgravityGateway\Service;

use Exception;
use OCP\IUser;
use OCP\Files\IRootFolder;
use OCA\Files_External\Service\GlobalStoragesService;
use Psr\Log\LoggerInterface;
use OCP\Files\File;
use OCA\Files_External\Lib\StorageConfig;

class ExternalStorageService {

	public function __construct(private LoggerInterface $logger, private IRootFolder $rootFolder, private GlobalStoragesService $globalStoragesService) {}

    /**
	 * Get the external storage configuration to which a specific fileId belongs to
	 * @param IUser $nextcloudUser Nextcloud user associated with the session
	 * @param int $fileId File ID to search for
	 * @return array
	 * @throws Exception
	 */
    public function getExternalStorageConfigurationForSpecificFile(IUser $nextcloudUser, int $fileId): array {
        try {
            $userFolder = $this->rootFolder->getUserFolder($nextcloudUser->getUID());
            $files = $userFolder->getById($fileId);
            if (count($files) <= 0 || !$files[0] instanceof File) {
                return ['message' => 'file ' . $fileId . ' not found', 'error' => 'file_not_found'];
            }

            // get the file according to the provided $fileId
            $file = $files[0];

            // fetch all configured external storages
            $externalStorages = $this->globalStoragesService->getStorages();

            // loop through each external storage to find the one related to your file
            foreach ($externalStorages as $externalStorage) {
                if ($this->isFileInExternalStorage($file, $externalStorage)) {
                    return $this->buildExternalStorageConfiguration($externalStorage);
                }
            }

        } catch (Exception $e) {
            return ['message' => 'error getting external storage config', 'error' => $e->getMessage()];
        }

        return ['message' => 'external storage for file ' . $fileId . ' not found', 'error' => 'external_storage_not_found'];
	}

    /**
	 * Construct specific configuration object from external storage configuration, to avoid expose sensitive data (such as password ...)
	 * @param StorageConfig $externalStorage External storage to build configuration for
	 * @return array
	*/
    private function buildExternalStorageConfiguration(StorageConfig $externalStorage): array {
        $configuration = [];
        $configuration['is_cidgravity'] = $externalStorage->getBackend()->getIdentifier() == "cidgravity";
        $configuration['id'] = $externalStorage->getId();
        $configuration['host'] = $externalStorage->getBackendOption('host');
        $configuration['mountpoint'] = $externalStorage->getMountPoint();

        return $configuration;
    }

    /**
	 * Check if specific file belongs to a specific external storage configuration
	 * @param File $file File to search for
	 * @param StorageConfig $externalStorage External storage configuration
	 * @return bool
	*/
    private function isFileInExternalStorage(File $file, StorageConfig $externalStorage): bool {
        $fileStorage = $file->getStorage();

        if ($fileStorage->instanceOfStorage('\OC\Files\Storage\DAV')) {
            if ($externalStorage->getBackend()->getIdentifier() == "cidgravity") {
                // according to the code, this ID format will be webdav::[EXTERNAL_STORAGE_USER]@[EXTERNAL_STORAGE_HOST]/[EXTERNAL_STORAGE_ROOT]/
                $fileStorageID = $fileStorage->getId();

                // to check if this file belongs to this external storage
                // create same ID format using external storage configuration
                $protocol = 'http://';

                if ($externalStorage->getBackendOption('secure')) {
                    $protocol = 'https://';
                }

                $externalStorageHost = str_replace($protocol, "", $externalStorage->getBackendOption('host'));
                $externalStorageID = 'webdav::' . $externalStorage->getBackendOption('user') . '@' . $externalStorageHost . '/' . $externalStorage->getBackendOption('root');
                
                // check if the fileStorageID falls under externalStorageID
                if (strpos($fileStorageID, $externalStorageID) === 0) {
                    return true;
                }
            }
        }

        return false;
    }
}