<?php

namespace FourPaws\DeliveryBundle\Service;

use Bitrix\Main\Error;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Shipment;

class InnerDeliveryService extends DeliveryServiceHandlerBase
{
    protected $code = '4lapy_delivery';

    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    public static function getClassTitle()
    {
        return 'Доставка "Четыре лапы"';
    }

    public static function getClassDescription()
    {
        return 'Обработчик собственной доставки "Четыре лапы"';
    }

    public function isCompatible(Shipment $shipment)
    {
        if (!parent::isCompatible($shipment)) {
            return false;
        }

        /** @todo проверка остатков товаров */

        return true;
    }

    public function getIntervals(Shipment $shipment): array
    {
        switch ($this->deliveryService->getDeliveryZoneCode($shipment)) {
            case DeliveryService::ZONE_1:
                return [
                    [
                        'FROM' => 9,
                        'TO'   => 18,
                    ],
                    [
                        'FROM' => 18,
                        'TO'   => 23,
                    ],
                ];
            case DeliveryService::ZONE_2:
                return [
                    [
                        'FROM' => 8,
                        'TO'   => 12,
                    ],
                    [
                        'FROM' => 12,
                        'TO'   => 16,
                    ],
                    [
                        'FROM' => 16,
                        'TO'   => 20,
                    ],
                    [
                        'FROM' => 20,
                        'TO'   => 0,
                    ],
                ];
        }

        return [];
    }

    protected function calculateConcrete(Shipment $shipment)
    {
        $result = parent::calculateConcrete($shipment);
        if (!$result->isSuccess()) {
            return $result;
        }

        $data = [];
        $deliveryZone = $this->deliveryService->getDeliveryZoneCode($shipment, false);
        if ($this->config['PRICES'][$deliveryZone]) {
            $result->setDeliveryPrice($this->config['PRICES'][$deliveryZone]);

            if (!empty($this->config['FREE_FROM'][$deliveryZone])) {
                $data['FREE_FROM'] = $this->config['FREE_FROM'][$deliveryZone];
                $order = $shipment->getParentOrder();
                if ($order->getBasket()->getPrice() >= $this->config['FREE_FROM'][$deliveryZone]) {
                    $result->setDeliveryPrice(0);
                }
            }
        } else {
            $result->addError(new Error('Не задана стоимость доставки'));
        }
        $data['INTERVALS'] = $this->getIntervals($shipment);
        $result->setData($data);

        /* @todo учитывать наличие товара */
        $result->setPeriodType(CalculationResult::PERIOD_TYPE_DAY);
        if (date('H') < 14) {
            $result->setPeriodFrom(0);
        } else {
            $result->setPeriodFrom(1);
        }

        return $result;
    }

    protected function getConfigStructure()
    {
        $result = parent::getConfigStructure();

        $zones = $this->deliveryService->getAvailableZones($this->getId());

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
        }

        return $result;
    }
}
