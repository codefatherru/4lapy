<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\SystemException;
use FourPaws\App\Application;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use FourPaws\UserBundle\Service\UserAuthorizationInterface;

class FourPawsExpertsenderFormComponent extends \CBitrixComponent
{
    /**
     * @var CurrentUserProviderInterface
     */
    private $currentUserProvider;

    /**
     * @var UserAuthorizationInterface
     */
    private $authorizationProvider;

    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
        try {
            $container = Application::getInstance()->getContainer();
            $this->authorizationProvider = $container->get(UserAuthorizationInterface::class);
            $this->currentUserProvider = $container->get(CurrentUserProviderInterface::class);
        } catch (\FourPaws\App\Exceptions\ApplicationCreateException $e) {
            $logger = LoggerFactory::create('component');
            $logger->error(sprintf('Component execute error: %s', $e->getMessage()));
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new SystemException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        }
    }

    /** {@inheritdoc} */
    public function executeComponent()
    {
        try {
            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            try {
                $logger = LoggerFactory::create('component');
                $logger->error(sprintf('Component execute error: %s', $e->getMessage()));
            } catch (\RuntimeException $e) {
            }
        }
    }

    /**
     * @return CurrentUserProviderInterface
     */
    public function getCurrentUserProvider(): CurrentUserProviderInterface
    {
        return $this->currentUserProvider;
    }

    /**
     * @return UserAuthorizationInterface
     */
    public function getAuthorizationProvider(): UserAuthorizationInterface
    {
        return $this->authorizationProvider;
    }
}
