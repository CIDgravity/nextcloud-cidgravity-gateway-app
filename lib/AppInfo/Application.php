<?php

namespace OCA\Cidgravity_Gateway\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

use OCA\Cidgravity_Gateway\Event\Listener\LoadAdditionalScriptsListener;
use OCA\Cidgravity_Gateway\Event\Listener\ExternalStoragesRegistrationListener;
use OCA\Cidgravity_Gateway\Event\Listener\UserCreatedListener;

use OCP\User\Events\UserCreatedEvent;

class Application extends App implements IBootstrap {
	public const APP_ID = 'cidgravity_gateway';

	public function __construct(array $urlParams = array()) {
		parent::__construct(self::APP_ID, $urlParams);
	}

    public function register(IRegistrationContext $context): void {
		$context->registerEventListener('OCA\\Files_External::loadAdditionalBackends', ExternalStoragesRegistrationListener::class);
        $context->registerEventListener(LoadAdditionalScriptsEvent::class, LoadAdditionalScriptsListener::class);
        $context->registerEventListener(UserCreatedEvent::class, UserCreatedListener::class);
    }

    public function boot(IBootContext $context): void {

    }
}
