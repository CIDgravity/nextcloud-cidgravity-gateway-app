<?php
declare(strict_types=1);
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

namespace OCA\CidgravityGateway\Event\Listener;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

use OCP\IConfig;
use OCP\User\Events\UserCreatedEvent;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\Files\IRootFolder;

use OCA\Files_External\Lib\StorageConfig;

class UserCreatedListener implements IEventListener
{
    public function __construct(private LoggerInterface $logger, private IConfig $config, private GlobalStoragesService $globalStoragesService, private IRootFolder $rootFolder) {}

    /**
	 * Handle UserCreatedEvent to create user folder on Public Filecoin webdav
	*/
    public function handle(Event $event): void
    {
        $this->logger->debug("CIDgravity: will create the user folder on provided external storage");

        if (!($event instanceof UserCreatedEvent)) {
            $this->logger->warning("CIDgravity: invalid event to create user folder on provided external storage");
            return;
        }

        // iterate over all external storages
        // if config for external storage autoCreateUserFolder is true, create user folder on it
        $externalStorages = $this->globalStoragesService->getStorages();

        foreach ($externalStorages as $externalStorage) {
            if ($externalStorage->getBackend()->getIdentifier() != "cidgravity") {
                continue;
            }

            if ($externalStorage->getBackendOption('auto_create_user_folder')) {

                // here to resolve, we need to replace $user by userID
                // we can't use userConfigHandler, because it works with userSession = current loggedin user
                // here we want the created user, not the user current loggedin (due to event listener)
                $resolvedMountpoint = str_replace('$user', $event->getUser()->getUID(), $externalStorage->getBackendOption('root'));
                    
                // define configuration to connect to webdav using CURL
                $host = $externalStorage->getBackendOption('host');
                $webDavPath = "{$host}{$resolvedMountpoint}";

                // check folder not already exists using PROPFIND in curl
                $folderExists = $this->sendCurlRequestToWebdav($externalStorage, $webDavPath, 'PROPFIND');

                if ($folderExists['errorMessage'] != null) {
                    $this->logger->error("CIDgravity: unable to check if folder already exist on webdav server. Check full log for details", [
                        "errorMessage" => $folderExists['errorMessage'],
                        "httpStatusCode" => $folderExists['httpCode'],
                    ]);
                    
                    return;
                }

                // if not exists (404 status code from previous request), create folder on webdav
                if ($folderExists['httpCode'] == 404) {
                    $createFolder = $this->sendCurlRequestToWebdav($externalStorage, $webDavPath, 'MKCOL');

                    if ($createFolder['errorMessage'] != null) {
                        $this->logger->error("CIDgravity: unable to create folder on webdav external storage. Check full log for details", [
                            "errorMessage" => $createFolder['errorMessage'],
                            "httpStatusCode" => $createFolder['httpCode'],
                        ]);
                        
                        return;
                    }

                    $this->logger->debug("CIDgravity: user folder on external storage successfully created", [
                        "webDavPath" => $webDavPath
                    ]);

                } else {
                    $this->logger->debug("CIDgravity: user folder already exists on webdav external storage, not created", [
                        "webDavPath" => $webDavPath
                    ]);
                }
            }
        }
    }

    public function sendCurlRequestToWebdav(StorageConfig $externalStorage, string $hostWithFolderPath, string $requestType) {
        $ch = curl_init();

        // define curl session opts
        curl_setopt($ch, CURLOPT_URL, $hostWithFolderPath);
        curl_setopt($ch, CURLOPT_USERPWD, "{$externalStorage->getBackendOption('user')}:{$externalStorage->getBackendOption('password')}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $requestType);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/xml',
        ]);

        // execute request
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = '';

        if (curl_errno($ch)) {
            $errorMessage = 'cURL Error: ' . curl_error($ch);
        } else {
            if ($httpCode == 405) {
                $errorMessage = "request type $requestType not supported";
            }
        }

        // close session and return
        curl_close($ch);
        return ['httpCode' => $httpCode, 'errorMessage' => $errorMessage];
    }
}