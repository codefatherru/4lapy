<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;
use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Exception\EmptyEntityClass;
use FourPaws\PersonalBundle\Entity\Referral;
use FourPaws\PersonalBundle\Service\ReferralService;
use FourPaws\UserBundle\Exception\BitrixRuntimeException;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Exception\ValidationException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use FourPaws\UserBundle\Service\UserAuthorizationInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @noinspection AutoloadingIssuesInspection */
class FourPawsPersonalCabinetReferralComponent extends CBitrixComponent
{
    protected static $accessUserGroup = 30;

    /**
     * @var ReferralService
     */
    private $referralService;

    /** @var UserAuthorizationInterface */
    private $authUserProvider;

    /**
     * @var CurrentUserProviderInterface
     */
    private $currentUserProvider;

    /**
     * FourPawsPersonalCabinetReferralComponent constructor.
     *
     * @param null|\CBitrixComponent $component
     *
     * @throws ServiceNotFoundException
     * @throws SystemException
     * @throws \RuntimeException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
        try {
            $container = App::getInstance()->getContainer();
        } catch (ApplicationCreateException $e) {
            $logger = LoggerFactory::create('component');
            $logger->error(sprintf('Component execute error: %s', $e->getMessage()));
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new SystemException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        }
        $this->referralService = $container->get('referral.service');
        $this->currentUserProvider = $container->get(CurrentUserProviderInterface::class);
        $this->authUserProvider = $container->get(UserAuthorizationInterface::class);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params): array
    {
        $params['PAGE_COUNT'] = 10;
        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        /** кешируем на сутки, можно будет увеличить если обновления будут не очень частые - чтобы лишний кеш не хранился */
        $params['CACHE_TIME'] = 24 * 60 * 60;
        $params['MANZANA_CACHE_TIME'] = 1 * 60 * 60;
        return $params;
    }

    /**
     * {@inheritdoc}
     * @throws ObjectException
     * @throws EmptyEntityClass
     * @throws SystemException
     * @throws ValidationException
     * @throws BitrixRuntimeException
     * @throws \Exception
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws ApplicationCreateException
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     * @throws LoaderException
     */
    public function executeComponent()
    {
        if (!$this->authUserProvider->isAuthorized()) {
            define('NEED_AUTH', true);

            return null;
        }

        try {
            $curUser = $this->currentUserProvider->getCurrentUser();
            if (!\in_array((int)static::$accessUserGroup, $this->currentUserProvider->getUserGroups(), true)) {
                LocalRedirect('/personal');
            }
        } catch (NotAuthorizedException $e) {
            define('NEED_AUTH', true);

            return null;
        }

        $this->setFrameMode(true);

        $this->arResult['ITEMS'] = $items = new ArrayCollection();

        $nav = null;

        /** @todo Добавить кеширование запроса в манзану с тегом - напрмиер на 30минут - 1час, + сбрасывать кеш по тегу при добавлении рефералла с сайта */
        $cache = Cache::createInstance();
        if ($cache->initCache($this->arParams['MANZANA_CACHE_TIME'],
            serialize(['userId' => $curUser->getId()]))) {
            $result = $cache->getVars();
            $this->arResult['NAV'] = $result['NAV'];
            $this->arResult['BONUS'] = $result['BONUS'];
            $cacheItems = $result['cacheItems'];
        } elseif ($cache->startDataCache()) {
            try {
                $nav = new PageNavigation('nav-referral');
                $nav->allowAllRecords(false)->setPageSize($this->arParams['PAGE_COUNT'])->initFromUri();

                $this->arResult['ITEMS'] = $items = $this->referralService->getCurUserReferrals(true, $nav);
            } catch (NotAuthorizedException $e) {
                define('NEED_AUTH', true);

                return null;
            }

            $cacheItems = [];
            if (!$items->isEmpty()) {
                /** @var Referral $item */
                /** @noinspection ForeachSourceInspection */
                foreach ($items as $item) {
                    if ($item instanceof Referral) {
                        $this->arResult['BONUS'] += $item->getBonus();
                        $cardId = $item->getCard();
                        $cacheItems[$cardId] = [
                            'bonus'         => $item->getBonus(),
                            'card'          => $cardId,
                            'moderated'     => $item->isModerate(),
                            'dateEndActive' => $item->getDateEndActive(),
                        ];
                    }
                }
                if ($this->arResult['BONUS'] > 0) {
                    $this->arResult['BONUS'] = floor($this->arResult['BONUS']);
                }
                $this->arResult['NAV'] = $nav;
            }

            $cache->endDataCache([
                'NAV'            => $this->arResult['NAV'],
                'BONUS' => $this->arResult['BONUS'],
                'cacheItems' => $cacheItems,
            ]);
        }

        if ($this->startResultCache(
            $this->arParams['CACHE_TIME'],
            [
                'cacheItems' => $cacheItems,
                'count'=>$nav->getRecordCount(),
                'page'=>$nav->getCurrentPage(),
                'bonus' => $this->arResult['BONUS']
            ]
        )) {
            $this->arResult['COUNT'] = $this->referralService->getAllCountByUser();
            $this->arResult['COUNT_ACTIVE'] = $this->referralService->getActiveCountByUser();
            $this->arResult['COUNT_MODERATE'] = $this->referralService->getModeratedCountByUser();
            $this->arResult['referral_type'] = $this->referralService->getReferralType();
            $this->arResult['FORMATED_BONUS'] = \number_format($this->arResult['BONUS'], 0, '.', ' ');

            $this->includeComponentTemplate();
        }

        return true;
    }
}
