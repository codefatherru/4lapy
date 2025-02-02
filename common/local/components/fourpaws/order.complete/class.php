<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Highloadblock\DataManager;
use Bitrix\Iblock\Component\Tools;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\Shipment;
use FourPaws\App\Application;
use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Bitrix\FourPawsComponent;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\EcommerceBundle\Preset\Bitrix\SalePreset;
use FourPaws\EcommerceBundle\Service\GoogleEcommerceService;
use FourPaws\EcommerceBundle\Service\RetailRocketService;
use FourPaws\External\ManzanaPosService;
use FourPaws\Helpers\BxCollection;
use FourPaws\KioskBundle\Service\KioskService;
use FourPaws\PersonalBundle\Service\PersonalOffersService;
use FourPaws\SaleBundle\Enum\OrderStatus;
use FourPaws\SaleBundle\Exception\NotFoundException;
use FourPaws\SaleBundle\Exception\ValidationException;
use FourPaws\SaleBundle\Repository\Table\AnimalShelterTable;
use FourPaws\SaleBundle\Service\OrderService;
use FourPaws\SaleBundle\Service\UserAccountService;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Exception\NotFoundException as StoreNotFoundException;
use FourPaws\StoreBundle\Service\StoreService;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Exception\NotFoundException as UserNotFoundException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use FourPaws\UserBundle\Service\UserAuthorizationInterface;
use FourPaws\UserBundle\Service\UserService;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @noinspection AutoloadingIssuesInspection */

/** @noinspection EfferentObjectCouplingInspection
 *
 * Class FourPawsOrderCompleteComponent
 */
class FourPawsOrderCompleteComponent extends FourPawsComponent
{
    /** @var CurrentUserProviderInterface */
    protected $currentUserProvider;
    /** @var DeliveryService */
    protected $deliveryService;
    /** @var OrderService */
    protected $orderService;
    /** @var StoreService */
    protected $storeService;
    /** @var ManzanaPosService */
    protected $manzanaPosService;
    /** @var UserAccountService */
    protected $userAccountService;
    /** @var UserService */
    private $authUserService;
    /**
     * @var GoogleEcommerceService
     */
    private $ecommerceService;
    /**
     * @var SalePreset
     */
    private $salePreset;
    /**
     * @var RetailRocketService
     */
    private $retailRocketService;

    /**
     * FourPawsOrderCompleteComponent constructor.
     *
     * @param null $component
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws ApplicationCreateException
     */
    public function __construct($component = null)
    {
        $serviceContainer = Application::getInstance()->getContainer();
        $this->orderService = $serviceContainer->get(OrderService::class);
        $this->currentUserProvider = $serviceContainer->get(CurrentUserProviderInterface::class);
        $this->authUserService = $serviceContainer->get(UserAuthorizationInterface::class);
        $this->storeService = $serviceContainer->get('store.service');
        $this->deliveryService = $serviceContainer->get('delivery.service');
        $this->manzanaPosService = $serviceContainer->get('manzana.pos.service');
        $this->userAccountService = $serviceContainer->get(UserAccountService::class);
        $this->ecommerceService = $serviceContainer->get(GoogleEcommerceService::class);
        $this->retailRocketService = $serviceContainer->get(RetailRocketService::class);
        $this->salePreset = $serviceContainer->get(SalePreset::class);

        parent::__construct($component);
    }

    /**
     * @global CMain $APPLICATION
     *
     * @throws Exception
     * @throws UserNotFoundException
     * @throws RuntimeException
     * @throws ObjectPropertyException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws NotAuthorizedException
     * @throws NotFoundException
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws ArgumentNullException
     * @throws NotImplementedException
     * @throws StoreNotFoundException
     * @throws SystemException
     * @throws ValidationException
     */
    public function prepareResult(): void
    {
        global $APPLICATION;
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle('Заказ оформлен');
        }

        unset($_SESSION['ORDER_PAYMENT_URL']);
        $this->arResult['IS_AUTH'] = $this->authUserService->isAuthorized();

        $user = null;
        $order = null;
        $relatedOrder = null;
        try {
            $user = $this->currentUserProvider->getCurrentUser();
        } catch (NotAuthorizedException $e) {
        }

        /**
         * При переходе на страницу "спасибо за заказ" мы ищем заказ с переданным id
         */
        try {
            $order = $this->orderService->getOrderById(
                $this->arParams['ORDER_ID'],
                true,
                $user ? $user->getId() : null,
                $this->arParams['HASH']
            );

            $this->arResult['ECOMMERCE_VIEW_SCRIPT'] =
                \sprintf(
                    "<script>%s\n%s</script>",
                    $this->ecommerceService->renderScript(
                        $this->salePreset->createPurchaseFromBitrixOrder($order, 'Покупка через корзину')
                    ),
                    $this->retailRocketService->renderOrderTransaction(
                        $this->salePreset->createRetailRocketTransactionFromBitrixOrder($order)
                    )
                );

            if ($this->orderService->hasRelatedOrder($order)) {
                $relatedOrder = $this->orderService->getRelatedOrder($order);
                if ($relatedOrder->getId() < $order->getId()) {
                    $tmp = $relatedOrder;
                    $relatedOrder = $order;
                    $order = $tmp;
                }

                $this->arResult['ECOMMERCE_VIEW_SCRIPT'] .=
                    \sprintf(
                        "<script>%s\n%s</script>",
                        $this->ecommerceService->renderScript(
                            $this->salePreset->createPurchaseFromBitrixOrder($order, 'Покупка через корзину')
                        ),
                        $this->retailRocketService->renderOrderTransaction(
                            $this->salePreset->createRetailRocketTransactionFromBitrixOrder($order)
                        )
                    );
            }
        } catch (NotFoundException $e) {
            Tools::process404('', true, true, true);
        }

        /**
         * Попытка открыть уже обработанный заказ
         */
        if (!\in_array(
            $order->getField('STATUS_ID'),
            [
                OrderStatus::STATUS_NEW_COURIER,
                OrderStatus::STATUS_NEW_PICKUP,
            ],
            true
        )
        ) {
            Tools::process404('', true, true, true);
        }

        if (!$user) {
            $user = $this->currentUserProvider->getUserRepository()->find($order->getUserId());
        }

        $this->arResult['ORDER'] = $order;
        $this->arResult['ORDER_PROPERTIES'] = $this->getOrderProperties($order);
        /**
         * флаг, что пользователь был зарегистрирован при оформлении заказа
         */
        $this->arResult['ORDER_REGISTERED'] = $this->orderService->getOrderPropertyByCode(
                $order,
                'USER_REGISTERED'
            )->getValue() !== 'Y';

        if (null !== $relatedOrder) {
            $this->arResult['RELATED_ORDER'] = $relatedOrder;
            $this->arResult['RELATED_ORDER_PROPERTIES'] = $this->getOrderProperties($relatedOrder);
            $this->arResult['RELATED_ORDER_DELIVERY'] = $this->getDeliveryData(
                $relatedOrder,
                $this->arResult['RELATED_ORDER_PROPERTIES']
            );
        }
        $this->userAccountService->refreshUserBalance($user);

        /** @var Shipment $shipment */
        /** @noinspection PhpAssignmentInConditionInspection */
        if ($shipment = $order->getShipmentCollection()->current()) {
            $this->arResult['ORDER_DELIVERY'] = $this->getDeliveryData($order, $this->arResult['ORDER_PROPERTIES']);
            $deliveryCode = $shipment->getDelivery()->getCode();
            $this->arResult['ORDER_DELIVERY']['DELIVERY_CODE'] = $deliveryCode;
            $this->arResult['ORDER_DELIVERY']['IS_PICKUP'] = in_array(
                $deliveryCode,
                DeliveryService::PICKUP_CODES,
                true
            );
            $this->arResult['ORDER_DELIVERY']['IS_DPD_PICKUP'] = $deliveryCode === DeliveryService::DPD_PICKUP_CODE;
            $this->arResult['ORDER_DELIVERY']['IS_DPD_DELIVERY'] = $deliveryCode === DeliveryService::DPD_DELIVERY_CODE;
            if ($this->arResult['ORDER_PROPERTIES']['DPD_TERMINAL_CODE']) {
                $this->arResult['ORDER_DELIVERY']['SELECTED_SHOP'] = $this->deliveryService->getDpdTerminalByCode(
                    $this->arResult['ORDER_PROPERTIES']['DPD_TERMINAL_CODE']
                );
            } elseif ($this->arResult['ORDER_PROPERTIES']['DELIVERY_PLACE_CODE']) {
                $this->arResult['ORDER_DELIVERY']['SELECTED_SHOP'] = $this->storeService->getStoreByXmlId(
                    $this->arResult['ORDER_PROPERTIES']['DELIVERY_PLACE_CODE']
                );
            }
        }

        if(KioskService::isKioskMode()){
            $this->arResult['KIOSK_MODE'] = true;
            $this->arResult['KIOSK_LOGOUT_URL'] = KioskService::getLogoutUrl();
        }

        $this->arResult['NEED_SHOW_ROYAL_CANIN_BUNNER'] = $this->orderService->checkRoyalCaninAction($order);

        if ($this->deliveryService->isDobrolapDeliveryCode($this->orderService->getOrderDeliveryCode($order)) && new DateTime() <= new DateTime('2019-12-31 23:59:59')) {
            /* Проверяем не привязан ли купон */
            $this->arResult['EXIST_COUPON'] = false;
            $this->arResult['AVAILABLE_COUPONS'] = false;
            $dobrolapCouponID = BxCollection::getOrderPropertyByCode($order->getPropertyCollection(), 'DOBROLAP_COUPON_ID')->getValue();
            /** @var PersonalOffersService $personalOffersService */
            $personalOffersService = App::getInstance()->getContainer()->get('personal_offers.service');
            if ($dobrolapCouponID) {
                $this->arResult['EXIST_COUPON'] = true;
                /** @var \Symfony\Bundle\FrameworkBundle\Routing\Router */
                $router = App::getInstance()->getContainer()->get('router');
                /** @var Symfony\Component\Routing\RouteCollection $routes */
                $routes = $router->getRouteCollection();
                $route = $routes->get('fourpaws_personal_ajax_personaloffers_bindunreserveddobrolapcoupon');
                $this->arResult['GET_COUPON_URL'] = $route->getPath();
                /** @var DataManager $personalCouponManager */
                $personalCouponManager = App::getInstance()->getContainer()->get('bx.hlblock.personalcoupon');
                $coupon = $personalCouponManager::getById($dobrolapCouponID)->fetch();
                $this->arResult['COUPON'] = $coupon;
                $this->arResult['OFFER'] = $personalOffersService->getOfferByCoupon($coupon);
            } else {
                $cnt = $personalOffersService->getDobrolapCouponCnt();
                if($cnt > 0){
                    $this->arResult['AVAILABLE_COUPONS'] = true;
                }
            }

            /* Получаем питомник */
            $shelterBarcode = BxCollection::getOrderPropertyByCode($order->getPropertyCollection(), 'DOBROLAP_SHELTER')->getValue();
            $shelter = AnimalShelterTable::getByBarcode($shelterBarcode);
            if ($shelter) {
                $this->arResult['SHELTER'] = $shelter['name'] . ', ' . $shelter['city'];
                $this->setTemplateName('dobrolap');
            }
        }
    }

    /**
     * @param Order $order
     *
     * @return array
     *
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     * @throws NotFoundException
     */
    protected function getOrderProperties(Order $order): array
    {
        $result = [];
        /** @var PropertyValue $propertyValue */
        foreach ($order->getPropertyCollection() as $propertyValue) {
            $propertyCode = $propertyValue->getProperty()['CODE'];
            $result[$propertyCode] = $propertyValue->getValue();
        }

        $result['BONUS_COUNT'] = $this->orderService->getOrderBonusSum($order);

        return $result;
    }

    /**
     * @param Order $order
     * @param array $properties
     *
     * @return array
     *
     * @throws Exception
     * @throws NotFoundException
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     */
    protected function getDeliveryData(Order $order, array $properties): array
    {
        $result = [];
        $result['ADDRESS'] = $this->orderService->getOrderDeliveryAddress($order);
        if ($properties['DPD_TERMINAL_CODE']) {
            $terminals = $this->deliveryService->getDpdTerminalsByLocation($properties['CITY_CODE']);
            /** @var Store $terminal */
            /** @noinspection PhpAssignmentInConditionInspection */
            if ($terminal = $terminals[$properties['DPD_TERMINAL_CODE']]) {
                $result['SCHEDULE'] = $terminal->getScheduleString();
            }
        } elseif ($properties['DELIVERY_PLACE_CODE']) {
            try {
                $store = $this->storeService->getStoreByXmlId($properties['DELIVERY_PLACE_CODE']);
                $result['SCHEDULE'] = $store->getScheduleString();
            } catch (StoreNotFoundException $e) {
            }
        }

        if ($properties['DELIVERY_DATE']) {
            $match = [];
            $deliveryString = $properties['DELIVERY_DATE'];
            if (preg_match('~^(\d{2}):\d{2}~', $properties['DELIVERY_INTERVAL'], $match)) {
                $deliveryString .= ' ' . $match[1] . ':00';
            } else {
                $deliveryString .= ' 00:00';
            }

            $result['DELIVERY_DATE'] = \DateTime::createFromFormat('d.m.Y H:i', $deliveryString);
        }

        if ($properties['DELIVERY_INTERVAL']) {
            $result['DELIVERY_INTERVAL'] = $properties['DELIVERY_INTERVAL'];
        }

        return $result;
    }
}
