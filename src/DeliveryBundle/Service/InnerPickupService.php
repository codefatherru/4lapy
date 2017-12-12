<?php

namespace FourPaws\DeliveryBundle\Service;

use Bitrix\Sale\Shipment;

class InnerPickupService extends DeliveryServiceBase
{
    public function __construct(array $initParams)
    {
        parent::__construct($initParams);
    }

    public static function getClassTitle()
    {
        return 'Самовывоз из магазина "Четыре лапы"';
    }

    public static function getClassDescription()
    {
        return 'Обработчик самовывоза "Четыре лапы"';
    }

    public function isCompatible(Shipment $shipment)
    {
        if (!parent::isCompatible($shipment)) {
            return false;
        }

        /** todo проверка остатков товаров */

        return true;
    }

    protected function calculateConcrete(Shipment $shipment)
    {
        /* @todo calculate delivery time and price */

        $result = new \Bitrix\Sale\Delivery\CalculationResult();
        $result->setDeliveryPrice(0);

        return $result;
    }
}
