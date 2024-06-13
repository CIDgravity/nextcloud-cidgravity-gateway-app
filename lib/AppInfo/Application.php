<?php

namespace OCA\CidgravityGateway\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCA\CidgravityGateway\Listener\LoadAdditionalScriptsListener;

class Application extends App implements IBootstrap {
	public const APP_ID = 'cidgravitygateway';

	public function __construct(array $urlParams = array()) {
		parent::__construct(self::APP_ID, $urlParams);
	}

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
    }

    public function boot(IBootContext $context): void {

    }
}
