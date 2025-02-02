<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\DeliveryBundle\Handler;

use Adv\Bitrixtools\Tools\BitrixUtils;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Error;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Shipment;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\DeliveryBundle\Collection\IntervalCollection;
use FourPaws\DeliveryBundle\Collection\IntervalRuleCollection;
use FourPaws\DeliveryBundle\Entity\Interval;
use FourPaws\DeliveryBundle\Factory\IntervalRuleFactory;
use FourPaws\Helpers\BxCollection;
use FourPaws\StoreBundle\Exception\NotFoundException;

class InnerDeliveryHandler extends DeliveryHandlerBase
{
    protected $code = '4lapy_delivery';

    /**
     * InnerDeliveryHandler constructor.
     * @param array $initParams
     * @throws ArgumentNullException
     * @throws ArgumentTypeException
     * @throws SystemException
     * @throws ApplicationCreateException
     */
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    /**
     * @return string
     */
    public static function getClassTitle(): string
    {
        return 'Доставка "Четыре лапы"';
    }

    /**
     * @return string
     */
    public static function getClassDescription(): string
    {
        return 'Обработчик собственной доставки "Четыре лапы"';
    }

    /**
     * @param Shipment $shipment
     * @return bool
     * @throws ObjectNotFoundException
     */
    public function isCompatible(Shipment $shipment): bool
    {
        if (!parent::isCompatible($shipment)) {
            return false;
        }

        return true;
    }

    /**
     * @param Shipment $shipment
     * @return IntervalCollection
     * @throws SystemException
     */
    public function getIntervals(Shipment $shipment): IntervalCollection
    {
        $result = new IntervalCollection();

        $deliveryZone = $this->deliveryService->getDeliveryZoneForShipment($shipment);

        $config = $this->getConfig();
        $intervalConfig = $config['MAIN']['ITEMS']['INTERVALS']['VALUE'];
        /** @var array $intervalGroup */
        foreach ($intervalConfig as $intervalGroup) {
            if ($intervalGroup['ZONE_CODE'] !== $deliveryZone) {
                continue;
            }

            foreach ($intervalGroup['INTERVALS'] as $intervalIndex => $interval) {
                $ruleCollection = new IntervalRuleCollection();
                foreach ($interval['RULES'] as $type => $values) {
                    if (!isset($intervalGroup['RULES'][$type])) {
                        continue;
                    }

                    $ruleData = [];
                    foreach ($values as $i => $value) {
                        if (!isset($intervalGroup['RULES'][$type][$i])) {
                            continue;
                        }

                        $ruleValue = $intervalGroup['RULES'][$type][$i];
                        $ruleValue['VALUE'] = $value;
                        $ruleData[] = $ruleValue;
                    }

                    $ruleCollection = new IntervalRuleCollection(
                        \array_merge(
                            $ruleCollection->toArray(),
                            IntervalRuleFactory::createRules($type, $ruleData)->toArray()
                        )
                    );
                }

                $result->add(
                    (new Interval())->setFrom($interval['FROM'])
                        ->setTo($interval['TO'])
                        ->setRules($ruleCollection)
                );
            }
        }

        return $result;
    }

    /**
     * @param Shipment $shipment
     *
     * @return int[]
     * @throws ObjectNotFoundException
     * @throws SystemException
     */
    public function getWeekDays(Shipment $shipment): array
    {
        $result = [];

        $deliveryZone = $this->deliveryService->getDeliveryZoneForShipment($shipment);
        $config = $this->getConfig();
        /** @var int[] $days */
        $days = $config['DAYS']['ITEMS'][$deliveryZone]['VALUE'];
        foreach ($days as $day) {
            $result[] = $day + 1;
        }

        return $result;
    }

    /**
     * @param Shipment $shipment
     *
     * @throws ApplicationCreateException
     * @throws SystemException
     * @throws ArgumentException
     * @throws ObjectNotFoundException
     * @throws NotFoundException
     * @return CalculationResult
     */
    protected function calculateConcrete(Shipment $shipment): CalculationResult
    {
        $result = new CalculationResult();

        if (!$deliveryLocation = $this->deliveryService->getDeliveryLocation($shipment)) {
            $result->addError(new Error('Не задано местоположение доставки'));

            return $result;
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        /** @var Basket $basket */
        $basket = $shipment->getParentOrder()->getBasket()->getOrderableItems();

        $deliveryZone = $this->deliveryService->getDeliveryZoneForShipment($shipment, false);
        $data = [];
        if ($this->config['PRICES'][$deliveryZone]) {
            $propertyCollection = $shipment->getParentOrder()->getPropertyCollection();
            $deliveryCost = BxCollection::getOrderPropertyByCode($propertyCollection, 'DELIVERY_COST');

            if ($deliveryCost && null !== ($deliveryCostValue = $deliveryCost->getValue()))
            {
                /**
                 * Хак для сохранения кастомной цены доставки, исправляющий баг при добавлении в заказ товаров
                 * через метод \FourPaws\SaleBundle\Service\BasketService::addOfferToBasket
                 * в обработчиках события OnSaleOrderBeforeSaved
                 */

                $result->setDeliveryPrice($deliveryCostValue);
            }
            else
            {
                $result->setDeliveryPrice($this->config['PRICES'][$deliveryZone]);
            }

            if (!empty($this->config['FREE_FROM'][$deliveryZone])) {
                $data['FREE_FROM'] = (int)$this->config['FREE_FROM'][$deliveryZone];
            }
        } else {
            $result->addError(new Error('Не задана стоимость доставки'));
        }
        $deliveryZone = $this->deliveryService->getDeliveryZoneForShipment($shipment, true);
        $data['INTERVALS'] = $this->getIntervals($shipment);
        $data['WEEK_DAYS'] = $this->getWeekDays($shipment);
        if (!$offers = static::getOffers($basket)) {
            $result->setData($data);

            /**
             * Нужно для отображения списка доставок в хедере и на странице доставок
             */
            return $result;
        }

        $availableStores = self::getAvailableStores($this->code, $deliveryZone, $deliveryLocation);
        if ($availableStores->isEmpty()) {
            $result->addError(new Error('Не найдено доступных складов'));

            return $result;
        }

        $stockResult = static::getStocks($basket, $offers, $availableStores);

        $data['STOCK_RESULT'] = $stockResult;

        $result->setData($data);
        if ($stockResult->getOrderable()->isEmpty()) {
            $result->addError(new Error('Отсутствуют товары в наличии'));
        }

        return $result;
    }

    /**
     * @throws ArgumentException
     * @return array
     */
    protected function getConfigStructure(): array
    {
        $result = parent::getConfigStructure();

        $zones = $this->deliveryService->getAvailableZones($this->getId());

        $result['MAIN']['TITLE'] = 'Настройки интервалов';
        $result['MAIN']['DESCRIPTION'] = 'Настройки интервалов';

        $result['MAIN']['ITEMS']['INTERVALS'] = [
            'TYPE'    => 'DELIVERY_INTERVALS',
            'NAME'    => 'Интервалы доставок',
            'DEFAULT' => [],
            'ZONES'   => $zones,
        ];

        $result['PRICES'] = [
            'TITLE'       => 'Стоимости доставок по зонам',
            'DESCRIPTION' => 'Стоимости доставок по зонам',
            'ITEMS'       => [],
        ];

        $result['FREE_FROM'] = [
            'TITLE'       => 'Пороги бесплатной доставки по зонам',
            'DESCRIPTION' => 'Пороги бесплатной доставки по зонам',
            'ITEMS'       => [],
        ];

        $result['DAYS'] = [
            'TITLE'       => 'Дни доставки',
            'DESCRIPTION' => 'Дни недели, в которые курьер доставляет заказы',
            'ITEMS'       => [],
        ];

        $days = [
            'Понедельник',
            'Вторник',
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота',
            'Воскресенье',
        ];
        foreach ($zones as $code => $zone) {
            $result['PRICES']['ITEMS'][$code] = [
                'TYPE'    => 'NUMBER',
                'NAME'    => 'Зона ' . $zone['NAME'],
                'DEFAULT' => 0,
            ];

            $result['FREE_FROM']['ITEMS'][$code] = [
                'TYPE'    => 'NUMBER',
                'NAME'    => 'Зона ' . $zone['NAME'],
                'DEFAULT' => 0,
            ];

            $result['DAYS']['ITEMS'][$code] = [
                'TYPE'         => 'ENUM',
                'NAME'         => 'Дни доставок для зоны ' . $zone['NAME'],
                'MULTIPLE'     => BitrixUtils::BX_BOOL_TRUE,
                'MULTIELEMENT' => BitrixUtils::BX_BOOL_TRUE,
                'OPTIONS'      => $days,
            ];
        }

        return $result;
    }
}
