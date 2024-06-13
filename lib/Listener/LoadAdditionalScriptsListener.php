<?php

declare(strict_types=1);

namespace OCA\CidgravityGateway\Listener;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

use OCA\CidgravityGateway\AppInfo\Application;

class LoadAdditionalScriptsListener implements IEventListener {
	public function __construct() {}

	public function handle(Event $event): void {
		if (!($event instanceof LoadAdditionalScriptsEvent)) {
			return;
		}

		Util::addScript(Application::APP_ID, 'cidgravitygateway-main', 'files');
	}
}