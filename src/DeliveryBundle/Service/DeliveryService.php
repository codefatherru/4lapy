<?php

namespace FourPaws\DeliveryBundle\Service;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Delivery\DeliveryLocationTable;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;
use FourPaws\Catalog\Model\Offer;
use FourPaws\DeliveryBundle\Collection\StockResultCollection;
use FourPaws\Location\LocationService;
use FourPaws\StoreBundle\Collection\StoreCollection;
use FourPaws\StoreBundle\Service\StoreService;
use FourPaws\UserBundle\Service\UserService;
use WebArch\BitrixCache\BitrixCache;

class DeliveryService
{
    const INNER_DELIVERY_CODE = '4lapy_delivery';

    const INNER_PICKUP_CODE = '4lapy_pickup';

    const DPD_DELIVERY_GROUP_CODE = 'ipolh_dpd';

    const DPD_DELIVERY_CODE = 'ipolh_dpd:COURIER';

    const DPD_PICKUP_CODE = 'ipolh_dpd:PICKUP';

    const ORDER_LOCATION_PROP_CODE = 'CITY_CODE';

    const LOCATION_RESTRICTION_TYPE_LOCATION = 'L';

    const LOCATION_RESTRICTION_TYPE_GROUP = 'G';

    const ZONE_1 = 'ZONE_1';

    const ZONE_2 = 'ZONE_2';

    const ZONE_3 = 'ZONE_3';

    const ZONE_4 = 'ZONE_4';

    const PICKUP_CODES = [
        DeliveryService::INNER_PICKUP_CODE,
        DeliveryService::DPD_PICKUP_CODE,
    ];

    const DELIVERY_CODES = [
        DeliveryService::INNER_DELIVERY_CODE,
        DeliveryService::DPD_DELIVERY_CODE,
    ];

    /**
     * @var LocationService $locationService
     */
    protected $locationService;

    /**
     * @var UserService $userService
     */
    protected $userService;

    /**
     * @var StoreService
     */
    protected $storeService;

    public function __construct(LocationService $locationService, UserService $userService, StoreService $storeService)
    {
        $this->locationService = $locationService;
        $this->userService = $userService;
        $this->storeService = $storeService;
    }

    /**
     * Получение доставок для товара
     *
     * @param Offer $offer
     * @param string $locationCode
     * @param array $codes коды доставок для расчета
     *
     * @return array
     */
    public function getByProduct(Offer $offer, string $locationCode = '', array $codes = []): array
    {
        $basket = Basket::createFromRequest([]);
        $basketItem = BasketItem::create($basket, 'sale', $offer->getId());
        $basketItem->setFieldNoDemand('CAN_BUY', 'Y');
        $basketItem->setFieldNoDemand('PRICE', $offer->getPrice());
        $basketItem->setFieldNoDemand('QUANTITY', 1);
        $basket->addItem($basketItem);

        return $this->getByBasket($basket, $locationCode, $codes);

    }

    /**
     * Получение доставок для корзины
     *
     * @param Basket $basket
     * @param string $locationCode
     * @param array $codes коды доставок для расчета
     *
     * @return array
     */
    public function getByBasket(BasketBase $basket, string $locationCode = '', array $codes = []): array
    {
        if (!$locationCode) {
            $locationCode = $this->getCurrentLocation();
        }

        $shipment = $this->generateShipment($locationCode, $basket);

        return $this->calculateDeliveries($shipment, $codes);
    }

    /**
     * Получение доставок для местоположения
     *
     * @param string $locationCode
     * @param array $codes коды доставок для расчета
     *
     * @return array
     */
    public function getByLocation(string $locationCode = null, array $codes = []): array
    {
        if (!$locationCode) {
            $locationCode = $this->getCurrentLocation();
        }

        $shipment = $this->generateShipment($locationCode);

        return $this->calculateDeliveries($shipment, $codes);
    }

    /**
     * Выполняет расчет всех возможных (или указанных) доставок
     *
     * @param Shipment $shipment
     * @param array $codes коды доставок
     *
     * @return CalculationResult[]
     */
    public function calculateDeliveries(Shipment $shipment, array $codes = [])
    {
        $availableServices = Manager::getRestrictedObjectsList($shipment);

        $result = [];

        foreach ($availableServices as $service) {
            if ($codes && !\in_array($service->getCode(), $codes)) {
                continue;
            }

            if ($service->isProfile()) {
                $name = $service->getNameWithParent();
            } else {
                $name = $service->getName();
            }
            $service->getCode();
            $shipment->setFields(
                [
                    'DELIVERY_ID'   => $service->getId(),
                    'DELIVERY_NAME' => $name,
                ]
            );

            $calculationResult = $shipment->calculateDelivery();
            if ($calculationResult->isSuccess()) {
                if (\in_array(
                    $service->getCode(),
                    [
                        self::DPD_DELIVERY_CODE,
                        self::DPD_PICKUP_CODE,
                    ],
                    true
                )) {
                    $calculationResult->setPeriodFrom($_SESSION['DPD_DATA'][$service->getCode()]['DAYS_FROM']);
                    $calculationResult->setPeriodTo($_SESSION['DPD_DATA'][$service->getCode()]['DAYS_TO']);
                    $calculationResult->setData(
                        array_merge(
                            $calculationResult->getData(),
                            [
                                'INTERVALS' => $_SESSION['DPD_DATA'][$service->getCode()]['INTERVALS'],
                            ]
                        )
                    );
                }

                $calculationResult->setData(
                    array_merge(
                        [
                            'DELIVERY_ID'   => $service->getId(),
                            'DELIVERY_NAME' => $name,
                            'DELIVERY_CODE' => $service->getCode(),
                        ],
                        $calculationResult->getData()
                    )
                );

                $result[] = $calculationResult;
            }
        }

        return $result;
    }

    public function getAllZones($withLocations = true): array
    {
        return $this->locationService->getLocationGroups($withLocations);
    }

    /**
     * Получение кода местоположения для доставки
     *
     * @param Shipment $shipment
     *
     * @return null|string
     */
    public function getDeliveryLocation(Shipment $shipment)
    {
        $order = $shipment->getParentOrder();
        $propertyCollection = $order->getPropertyCollection();
        $locationProp = $propertyCollection->getDeliveryLocation();

        if ($locationProp && $locationProp->getValue()) {
            return $locationProp->getValue();
        }

        return null;
    }

    /**
     * Получение кода зоны доставки. Содержит либо код группы доставки,
     * либо код местоположения (в случае, если в ограничениях указано
     * отдельное местоположение)
     *
     * @param Shipment $shipment
     * @param bool $skipLocations возвращать только коды групп
     *
     * @return bool|string
     */
    public function getDeliveryZoneCode(Shipment $shipment, $skipLocations = true)
    {
        if (!$deliveryLocation = $this->getDeliveryLocation($shipment)) {
            return false;
        }
        $deliveryId = $shipment->getDeliveryId();

        return $this->getDeliveryZoneCodeByLocation($deliveryLocation, $deliveryId, $skipLocations);
    }

    /**
     * @param $deliveryLocation
     * @param $deliveryId
     * @param bool $skipLocations
     *
     * @return bool|int|string
     */
    public function getDeliveryZoneCodeByLocation($deliveryLocation, $deliveryId, $skipLocations = true)
    {
        $deliveryLocationPath = [$deliveryLocation];
        if (($location = $this->locationService->findLocationByCode($deliveryLocation)) && $location['PATH']) {
            $deliveryLocationPath = array_merge(
                $deliveryLocationPath,
                array_column($location['PATH'], 'CODE')
            );
        }

        $availableZones = $this->getAvailableZones($deliveryId);

        foreach ($availableZones as $code => $zone) {
            if ($skipLocations && $zone['TYPE'] === static::LOCATION_RESTRICTION_TYPE_LOCATION) {
                continue;
            }
            if (!empty(array_intersect($deliveryLocationPath, $zone['LOCATIONS']))) {
                return $code;
            }
        }

        return false;
    }

    /**
     * Получение доступных зон доставки в соответствии с ограничениями по местоположению
     *
     * @param int $deliveryId
     *
     * @return array
     */
    public function getAvailableZones(int $deliveryId): array
    {
        $allZones = $this->getAllZones();

        $getZones = function () use ($allZones, $deliveryId) {
            $result = [];

            $restrictions = DeliveryLocationTable::getList(
                [
                    'filter' => ['DELIVERY_ID' => $deliveryId],
                ]
            );

            $locationCodes = [];
            while ($restriction = $restrictions->fetch()) {
                switch ($restriction['LOCATION_TYPE']) {
                    case static::LOCATION_RESTRICTION_TYPE_LOCATION:
                        $locationCodes[] = $restriction['LOCATION_CODE'];
                        break;
                    case static::LOCATION_RESTRICTION_TYPE_GROUP:
                        if (isset($allZones[$restriction['LOCATION_CODE']])) {
                            $item = $allZones[$restriction['LOCATION_CODE']];
                            $item['TYPE'] = static::LOCATION_RESTRICTION_TYPE_GROUP;
                            $result[$restriction['LOCATION_CODE']] = $item;
                        }
                        break;
                }
            }

            if (!empty($locationCodes)) {
                $locations = LocationTable::getList(
                    [
                        'filter' => ['CODE' => $locationCodes],
                        'select' => ['ID', 'CODE', 'NAME.NAME'],
                    ]
                );

                while ($location = $locations->fetch()) {
                    // сделано, чтобы отдельные местоположения были впереди групп,
                    // т.к. группы могут их включать
                    $result = [
                            $location['CODE'] => [
                                'CODE'      => $location['CODE'],
                                'NAME'      => $location['SALE_LOCATION_LOCATION_NAME_NAME'],
                                'ID'        => $location['ID'],
                                'LOCATIONS' => [$location['CODE']],
                                'TYPE'      => static::LOCATION_RESTRICTION_TYPE_LOCATION,
                            ],
                        ] + $result;
                }
            }

            return $result;
        };

        $result = (new BitrixCache())
            ->withId(__METHOD__ . $deliveryId)
            ->resultOf($getZones);

        return $result;
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isPickup(CalculationResult $calculationResult): bool
    {
        return \in_array($calculationResult->getData()['DELIVERY_CODE'], static::PICKUP_CODES, true);
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isDelivery(CalculationResult $calculationResult): bool
    {
        return \in_array($calculationResult->getData()['DELIVERY_CODE'], static::DELIVERY_CODES, true);
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isInnerPickup(CalculationResult $calculationResult): bool
    {
        return $calculationResult->getData()['DELIVERY_CODE'] === static::INNER_PICKUP_CODE;
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isDpdPickup(CalculationResult $calculationResult): bool
    {
        return $calculationResult->getData()['DELIVERY_CODE'] === static::DPD_PICKUP_CODE;
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isInnerDelivery(CalculationResult $calculationResult): bool
    {
        return $calculationResult->getData()['DELIVERY_CODE'] === static::INNER_DELIVERY_CODE;
    }

    /**
     * @param CalculationResult $calculationResult
     *
     * @return bool
     */
    public function isDpdDelivery(CalculationResult $calculationResult): bool
    {
        return $calculationResult->getData()['DELIVERY_CODE'] === static::DPD_DELIVERY_CODE;
    }

    /**
     * @param CalculationResult $delivery
     *
     * @return StoreCollection
     */
    public function getStoresByDelivery(CalculationResult $delivery): StoreCollection
    {
        /** @var StockResultCollection $stockResult */
        $stockResult = $delivery->getData()['STOCK_RESULT'];
        return $stockResult->getStores();
    }
    
    /**
     * @return string
     */
    protected function getCurrentLocation()
    {
        return $this->locationService->getCurrentLocation();
    }

    protected function generateShipment(string $locationCode, BasketBase $basket = null): Shipment
    {
        $order = Order::create(
            SITE_ID,
            $this->userService->getAnonymousUserId(),
            CurrencyManager::getBaseCurrency()
        );

        if (!$basket) {
            $basket = Basket::createFromRequest([]);
        }

        $order->setBasket($basket);

        $propertyCollection = $order->getPropertyCollection();
        $locationProp = $propertyCollection->getDeliveryLocation();
        $locationProp->setValue($locationCode);

        $shipmentCollection = $order->getShipmentCollection();
        $shipment = $shipmentCollection->createItem();
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        $shipment->setField('CURRENCY', $order->getCurrency());

        /** @var BasketItem $item */
        foreach ($order->getBasket() as $item) {
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }

        return $shipment;
    }
}
