<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\DeliveryBundle\Dpd;

use Adv\Bitrixtools\Tools\BitrixUtils;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use FourPaws\App\Application;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\DeliveryBundle\Dpd\Lib\Calculator;
use FourPaws\DeliveryBundle\Entity\DpdLocation;
use FourPaws\DeliveryBundle\Exception\LocationNotFoundException;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\DeliveryBundle\Service\DpdLocationService;
use FourPaws\LocationBundle\Exception\CityNotFoundException;
use FourPaws\LocationBundle\LocationService;
use FourPaws\StoreBundle\Collection\StoreCollection;

if (!Loader::includeModule('ipol.dpd')) {
    class Shipment
    {
    }

    return;
}

class Shipment extends \Ipolh\DPD\Shipment
{
    protected $locationTo;

    protected $locationFrom;

    /**
     * Устанавливает местоположение отправителя
     *
     * @param array|string $locationCode код местоположения
     *
     * @return $this
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws CityNotFoundException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function setSender($locationCode):self
    {
        $this->locationFrom = \is_array($locationCode)
            ? $locationCode
            : $this->getDpdLocation($locationCode);

        return $this;
    }

    /**
     * Устанавливает местоположение получателя
     *
     * @param array|string $locationCode код местоположения
     *
     * @return $this
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws CityNotFoundException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function setReceiver($locationCode):self
    {
        $this->locationTo = \is_array($locationCode)
            ? $locationCode
            : $this->getDpdLocation($locationCode);

        return $this;
    }

    /**
     * Проверяет возможность осуществления в терминал доставки
     *
     * @return  bool
     */
    public function isPossibileSelfDelivery($isPaymentOnDelivery = null)
    {
        if (!$this->isPossibileDelivery()) {
            return false;
        }

        return $this->getDpdTerminals($isPaymentOnDelivery)->count() > 0;
    }

    public function getDpdTerminals($isPaymentOnDelivery = null): StoreCollection
    {
        /** @var DeliveryService $deliveryService */
        $deliveryService = Application::getInstance()->getContainer()->get('delivery.service');
        $isPaymentOnDelivery = null === $isPaymentOnDelivery ? $this->isPaymentOnDelivery() : $isPaymentOnDelivery;

        return $deliveryService->getDpdTerminalsByLocation(
            $this->locationTo['CODE'],
            $isPaymentOnDelivery,
            0
        );
    }

    /**
     * Возвращает объем отправки, м3
     *
     * @return float
     */
    public function getVolume()
    {
        $volume = $this->dimensions['WIDTH'] * $this->dimensions['HEIGHT'] * $this->dimensions['LENGTH'];

        return round($volume / 1000000, 3) ?: 0.001;
    }

    public function isPaymentOnDelivery()
    {
        return false;
    }

    public function calculator()
    {
        return new Calculator($this, $this->api);
    }

    /**
     * @param $locationCode
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws ApplicationCreateException
     * @return array
     * @throws CityNotFoundException
     */
    protected function getDpdLocation($locationCode): array
    {
        if(!empty($locationCode)) {
            /** @var LocationService $locationService */
            $locationService = Application::getInstance()->getContainer()->get('location.service');
            /** @var DpdLocationService $dpdLocationService */
            $dpdLocationService = Application::getInstance()->getContainer()->get(DpdLocationService::class);
                $location = $locationService->findLocationCityByCode($locationCode);
                try {
                    $dpdLocation = $dpdLocationService->getOneByLocationId($location['ID']);
                } catch (LocationNotFoundException $e) {
                    $dpdLocation = new DpdLocation();
                }
        } else {
            throw new CityNotFoundException('город не найден');
        }

        return [
            'ID'           => $dpdLocation->getId(),
            'CODE'         => $locationCode,
            'COUNTRY_CODE' => $dpdLocation->getCountryCode(),
            'REGION_CODE'  => $dpdLocation->getRegionCode(),
            'REGION_NAME'  => $dpdLocation->getRegionName(),
            'CITY_ID'      => $dpdLocation->getDpdId(),
            'CITY_CODE'    => $dpdLocation->getKladr(),
            'CITY_NAME'    => $dpdLocation->getName(),
            'IS_CASH_PAY'  => $dpdLocation->isCashPay() ? BitrixUtils::BX_BOOL_TRUE : BitrixUtils::BX_BOOL_FALSE,
        ];
    }
}
