<?php
/*
 * @copyright Copyright (c) ADV/web-engineering co.
 */

namespace FourPaws\DeliveryBundle\Entity\CalculationResult;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\Catalog\Model\Offer;
use FourPaws\DeliveryBundle\Helpers\DeliveryTimeHelper;
use FourPaws\StoreBundle\Collection\StoreCollection;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Exception\NotFoundException;

class DpdPickupResult extends BaseResult implements PickupResultInterface
{
    /**
     * @var Store
     */
    protected $selectedPickupPoint;

    /**
     * @var StoreCollection
     */
    protected $terminals;

    /**
     * Данные по длительности доставки, пришедшие от DPD
     * @var int
     */
    protected $initialPeriod = 0;

    /**
     * @return \DateTime
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function getDeliveryDate(): \DateTime
    {
        $modifier = $this->getInitialPeriod();

        /**
         * к любой доставке DPD прибавляем 2 дня
         */
        $modifier += 2;

        return (clone parent::getDeliveryDate())->modify(sprintf('+%s days', $modifier));
    }

    /**
     * @return Store
     */
    public function getSelectedShop(): Store
    {
        if (null === $this->selectedPickupPoint) {
            $this->selectedPickupPoint = $this->getTerminals()->first();
        }

        return $this->selectedPickupPoint;
    }

    /**
     * @param Store $selectedStore
     *
     * @return PickupResultInterface
     */
    public function setSelectedShop(Store $selectedStore): PickupResultInterface
    {
        $this->selectedPickupPoint = $selectedStore;

        return $this;
    }

    /**
     * @return StoreCollection
     */
    public function getTerminals(): StoreCollection
    {
        return $this->terminals;
    }

    /**
     * @param StoreCollection $terminals
     * @return DpdPickupResult
     */
    public function setTerminals(StoreCollection $terminals): DpdPickupResult
    {
        $this->terminals = $terminals;
        return $this;
    }

    /**
     * @return StoreCollection
     */
    public function getBestShops(): StoreCollection
    {
        return $this->getTerminals();
    }/** @noinspection SenselessProxyMethodInspection */

    /**
     * @param bool $internalCall
     * @return bool
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function isSuccess($internalCall = false)
    {
        return parent::isSuccess($internalCall);
    }

    /**
     * @return int
     */
    public function getInitialPeriod(): int
    {
        return $this->initialPeriod;
    }

    /**
     * @param int $initialPeriod
     * @return DpdPickupResult
     */
    public function setInitialPeriod(int $initialPeriod): DpdPickupResult
    {
        $this->initialPeriod = $initialPeriod;
        return $this;
    }

    /**
     * @return int
     * @throws ArgumentException
     * @throws ApplicationCreateException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function getPeriodTo(): int
    {
        return $this->getPeriodFrom();
    }

    /**
     * @param Offer $offer
     *
     * @return bool
     * @throws ApplicationCreateException
     */
    protected function checkIsDeliverable(Offer $offer): bool
    {
        return parent::checkIsDeliverable($offer) && $offer->getProduct()->isDeliveryAvailable();
    }

    /**
     * Возвращает отформатированный текст о доставке для карточки товара на сайте и в мобильном приложении
     * @param bool $isByRequest
     * @param bool $withCurrency
     * @return string
     */
    public function getTextForOffer($isByRequest = false, $withCurrency = false): string
    {
        $text = DeliveryTimeHelper::showByDate($this->deliveryDate, 0, ['DATE_FORMAT' => 'XX']);
        if ($isByRequest) {
            $text .= ' ближайшая';
        } else if ($this->freeFrom) {
            $text .= ' бесплатно от ' . $this->freeFrom;
            if ($withCurrency) {
                $text .= $this->currency;
            }
        }
        return $text;
    }
}
