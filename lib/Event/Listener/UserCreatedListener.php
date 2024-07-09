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
            $this->logger->debug("CIDgravity: invalid event to create user folder on provided external storage");
            return;
        }

        // get "Public Filecoin" external storage from config ID
        // do this only if public gateway enabled
        if ($this->config->getSystemValue('cidgravity_gateway')['is_public_gateway_enabled']) {
            $externalStorageID = $this->config->getSystemValue('cidgravity_gateway')['public_external_storage_id'];
            $externalStorage = $this->globalStoragesService->getStorage($externalStorageID);

            if ($externalStorage->getBackend()->getIdentifier() != "cidgravity") {
                $this->logger->error("CIDgravity: unable to create user folder on provided external storage: not an cidgravity external storage");
                return;
            }

            // get associated user and create the folder
            $userUID = $event->getUser()->getUID();
            $externalStorageMountpoint = $externalStorage->getMountPoint();
            $userFolder = $this->rootFolder->getUserFolder($userUID);
            $webDavPath = "/{$externalStorageMountpoint}/{$userUID}";

            try {
                if (!$userFolder->nodeExists($webDavPath)) {
                    $userFolder->newFolder($webDavPath);
                    $this->logger->debug("CIDgravity: User folder successfully created on provided external storage");
                } else {
                    $this->logger->debug("CIDgravity: unable to create user folder on provided external storage: already exists");
                }
            } catch (\Exception $e) {
                $this->logger->error("CIDgravity: unable to create user folder on provided external storage: " . $e->getMessage());
            }
            
        } else {
            $this->logger->debug("CIDgravity: public gateway not enabled, will not try to create user folder on external storage");
        }
    }
}