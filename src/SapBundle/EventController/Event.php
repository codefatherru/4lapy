<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SapBundle\EventController;

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Event as BitrixEvent;
use Bitrix\Main\EventManager;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use FourPaws\App\Application;
use FourPaws\App\BaseServiceHandler;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\Helpers\BxCollection;
use FourPaws\SaleBundle\Service\OrderService;
use FourPaws\PersonalBundle\Service\OrderService as PersonalOrderService;
use FourPaws\SapBundle\Consumer\ConsumerRegistry;
use FourPaws\SapBundle\Enum\SapOrder;
use FourPaws\SapBundle\Exception\LogicException;
use FourPaws\SapBundle\Exception\UnexpectedValueException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use FourPaws\SaleBundle\Service\OrderPropertyService;

/**
 * Class Event
 *
 * Обработчики событий
 *
 * @package FourPaws\SapBundle\EventController
 */
class Event extends BaseServiceHandler
{
    protected static $isEventsDisable = false;

    public static function disableEvents(): void
    {
        self::$isEventsDisable = true;
    }

    public static function enableEvents(): void
    {
        self::$isEventsDisable = false;
    }


    /**
     * @param EventManager $eventManager
     *
     */
    public static function initHandlers(EventManager $eventManager): void
    {
        parent::initHandlers($eventManager);

        $module = 'sale';
        static::initHandler('OnSaleOrderEntitySaved', [self::class, 'consumeOrderAfterSaveOrder'], $module);
        static::initHandler('OnSalePaymentEntitySaved', [self::class,'consumeOrderAfterSavePayment'], $module);
    }

    /**
     * @param BitrixEvent $event
     * @throws ApplicationCreateException
     * @throws ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     */
    public static function consumeOrderAfterSaveOrder(BitrixEvent $event): void
    {
        if (self::$isEventsDisable) {
            return;
        }

        /**
         * @var Order        $order
         * @var OrderService $orderService
         */
        $order = $event->getParameter('ENTITY');

        $tempLogger = LoggerFactory::create('OrderSapExport', 'dev');
        $tempLogger->info('consumeOrderAfterSaveOrder start', [
            'orderId: ' . $order->getId(),
        ]);

        if ($order->isCanceled()) {
            $tempLogger->info('consumeOrderAfterSaveOrder order is canceled', [
                'orderId: ' . $order->getId(),
            ]);
            return;
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $orderService = Application::getInstance()->getContainer()->get(
            OrderService::class
        );

        $deliveryService = Application::getInstance()->getContainer()->get('delivery.service');
        $isDostavistaDelivery = $deliveryService->isDostavistaDeliveryCode($deliveryService->getDeliveryCodeById($order->getField('DELIVERY_ID')));

        /**
         * Если заказ уже выгружен в SAP, оплата онлайн, пропускаем
         */
        if (
            ($isOrderExported = self::isOrderExported($order))
            || ($isManzanaOrder = self::isManzanaOrder($order))
            || ($isDostavistaOrder = self::isDostavistaOrder($order))
            || (($isOnlinePayment = $orderService->isOnlinePayment($order)) && !$isDostavistaDelivery)
            //|| $orderService->isSubscribe($order)
        ) {
            $tempLogger->info('consumeOrderAfterSaveOrder order wasn\'t consumed', [
                'orderId: ' . $order->getId(),
                '$isOrderExported: ' . $isOrderExported,
                '$isManzanaOrder: ' . ($isManzanaOrder ?? ''),
                '$isDostavistaOrder: ' . ($isDostavistaOrder ?? ''),
                '$isOnlinePayment: ' . ($isOnlinePayment ?? ''),
                '$isDostavistaDelivery: ' . $isDostavistaDelivery,
            ]);
            return;
        }

        self::getConsumerRegistry()->consume($order);
    }

    /**
     * @param BitrixEvent $event
     *
     * @throws ArgumentNullException
     * @throws NotImplementedException
     * @throws ApplicationCreateException
     * @throws LogicException
     * @throws UnexpectedValueException
     */
    public static function consumeOrderAfterSavePayment(BitrixEvent $event): void
    {
        if (self::$isEventsDisable) {
            return;
        }

        /** @var Payment $payment */
        $oldFields = $event->getParameter('VALUES');
        $payment = $event->getParameter('ENTITY');

        $tempLogger = LoggerFactory::create('OrderSapExport', 'dev');
        $tempLogger->info('consumeOrderAfterSavePayment start', [
            'paymentId: ' . $payment->getId(),
        ]);

        $tempLogger->info('consumeOrderAfterSavePayment params', [
            'paymentId: ' . $payment->getId(),
            '$oldFields[\'PAID\']: ' . $oldFields['PAID'],
            'paymentSystemId: ' . (int)$payment->getPaymentSystemId(),
            'orderId: ' . $payment->getOrderId(),
            'isPaid: ' . $payment->isPaid(),
        ]);

        if (
            $oldFields['PAID'] !== 'Y'
            && (int)$payment->getPaymentSystemId() === SapOrder::PAYMENT_SYSTEM_ONLINE_ID
            && $payment->getOrderId() > 0
            && $payment->isPaid()
        ) {
            /**
             * Если оплата онлайн и статус меняется на оплачено, то выгружаем в SAP
             *
             * @var ConsumerRegistry $consumerRegistry
             */
            $order = Order::load($payment->getOrderId());

            /** @noinspection NullPointerExceptionInspection */
            $tempLogger->info('consumeOrderAfterSavePayment params2', [
                'isOrderExported: ' . self::isOrderExported($order),
                'isManzanaOrder: ' . self::isManzanaOrder($order),
                'isDostavistaOrder: ' . self::isDostavistaOrder($order),
            ]);
            if (!self::isOrderExported($order) && !self::isManzanaOrder($order) && !self::isDostavistaOrder($order)) {
                self::getConsumerRegistry()->consume($order);
            }
        }
    }

    /**
     * @throws ApplicationCreateException
     *
     * @return ConsumerRegistry
     */
    public static function getConsumerRegistry(): ConsumerRegistry
    {
        try {
            return Application::getInstance()->getContainer()->get(ConsumerRegistry::class);
        } catch (ServiceNotFoundException | ServiceCircularReferenceException $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return new ConsumerRegistry();
        }
    }

    /**
     * @param Order $order
     *
     * @return bool
     *
     */
    private static function isOrderExported(Order $order): bool
    {
        $isConsumedValue = BxCollection::getOrderPropertyByCode($order->getPropertyCollection(), 'IS_EXPORTED');

        return null !== $isConsumedValue && $isConsumedValue->getValue() === 'Y';
    }

    /**
     * @param Order $order
     *
     * @return bool
     *
     */
    private static function isManzanaOrder(Order $order): bool
    {
        $manzanaNumberValue = BxCollection::getOrderPropertyByCode($order->getPropertyCollection(), 'MANZANA_NUMBER');

        return null !== $manzanaNumberValue && (bool)$manzanaNumberValue->getValue();
    }

    /**
     * @param Order $order
     *
     * @return bool
     * @throws ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     */
    private static function isDostavistaOrder(Order $order): bool
    {
        $statusId = $order->getField('STATUS_ID');

        if ($statusId != PersonalOrderService::STATUS_CANCELING) {
            $deliveryService = Application::getInstance()->getContainer()->get('delivery.service');
            $isDostavistaDelivery = $deliveryService->isDostavistaDeliveryCode($deliveryService->getDeliveryCodeById($order->getField('DELIVERY_ID')));
            $propertyCollection = $order->getPropertyCollection();
            $orderIdDostavista = BxCollection::getOrderPropertyByCode($propertyCollection, 'ORDER_ID_DOSTAVISTA')->getValue();
            $commWay = BxCollection::getOrderPropertyByCode($propertyCollection, 'COM_WAY')->getValue();
            switch (true) {
                case !$isDostavistaDelivery:
                case $isDostavistaDelivery && $orderIdDostavista != '' && $orderIdDostavista != 0:
                case $isDostavistaDelivery &&
                    (
                        $commWay == OrderPropertyService::COMMUNICATION_DOSTAVISTA_ERROR ||
                        $commWay == OrderPropertyService::COMMUNICATION_PAYMENT_ANALYSIS_DOSTAVISTA_ERROR
                    ):
                    return false;
                    break;
                default:
                    return true;
            }
        } else {
            return false;
        }
    }
}
