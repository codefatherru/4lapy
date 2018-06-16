<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\ArgumentException;
use FourPaws\App\Application;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\DeliveryBundle\Entity\CalculationResult\CalculationResultInterface;
use FourPaws\DeliveryBundle\Entity\CalculationResult\PickupResultInterface;
use FourPaws\DeliveryBundle\Exception\NotFoundException as DeliveryNotFoundException;
use FourPaws\LocationBundle\Exception\CityNotFoundException;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Exception\NotFoundException;
use FourPaws\StoreBundle\Service\StockService;
use FourPaws\StoreBundle\Service\StoreService;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\Catalog\Model\Offer;

CBitrixComponent::includeComponentClass('fourpaws:city.delivery.info');

/** @noinspection AutoloadingIssuesInspection */
class FourPawsCatalogProductDeliveryInfoComponent extends FourPawsCityDeliveryInfoComponent
{
    /**
     * @var StoreService
     */
    protected $storeService;

    /**
     * @var StockService
     */
    protected $stockService;

    /**
     * FourPawsCatalogProductDeliveryInfoComponent constructor.
     * @param CBitrixComponent|null $component
     * @throws ApplicationCreateException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);


        /** @noinspection PhpUnhandledExceptionInspection */
        $serviceContainer = Application::getInstance()->getContainer();
        $this->storeService = $serviceContainer->get('store.service');
        $this->stockService = $serviceContainer->get(StockService::class);
    }

    /** {@inheritdoc} */
    public function onPrepareComponentParams($params): array
    {
        if (empty($params['OFFER']) && !empty($params['OFFER_ID'])) {
            $params['OFFER'] = $this->getOffer($params['OFFER_ID']);
        }

        $params['OFFER'] = $params['OFFER'] instanceof Offer ? $params['OFFER'] : null;

        $params['CACHE_TYPE'] = $params['CACHE_TYPE'] ?? 'N';
        $params['CACHE_TIME'] = $params['CACHE_TIME'] ?? 0;

        return parent::onPrepareComponentParams($params);
    }

    /**
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws DeliveryNotFoundException
     * @throws NotFoundException
     * @throws CityNotFoundException
     */
    protected function prepareResult()
    {
        if (!$this->arParams['OFFER']) {
            throw new \InvalidArgumentException('Invalid component parameters');
        }

        parent::prepareResult();

        if (isset($this->arResult['CURRENT']['PICKUP']) &&
            $this->arResult['CURRENT']['PICKUP']['CODE'] === DeliveryService::INNER_PICKUP_CODE
        ) {
            $this->arResult['CURRENT']['PICKUP']['SHOP_COUNT'] = $this->getShopCount(
                $this->arResult['CURRENT']['PICKUP']['RESULT']
            );
        }
    }

    /**
     * @param string $locationCode
     * @param array $possibleDeliveryCodes
     *
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws NotFoundException
     * @throws DeliveryNotFoundException
     * @return CalculationResultInterface[]
     */
    protected function getDeliveries(string $locationCode, array $possibleDeliveryCodes = [])
    {
        $result = [];
        $deliveries = parent::getDeliveries($locationCode, $possibleDeliveryCodes);
        foreach ($deliveries as $delivery) {
            $delivery->setStockResult(
                $this->deliveryService->getStockResultForOffer($this->arParams['OFFER'], $delivery)
            )->setCurrentDate(new \DateTime());
            if ($delivery->isSuccess()) {
                $result[] = $delivery;
            }
        }

        return $result;
    }

    /**
     * @param int $id
     * @return Offer|null
     */
    protected function getOffer(int $id): ?Offer
    {
        return OfferQuery::getById($id);
    }

    /**
     * @param PickupResultInterface $pickup
     * @return int
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws DeliveryNotFoundException
     * @throws NotFoundException
     */
    protected function getShopCount(PickupResultInterface $pickup)
    {
        $shops = $pickup->getBestShops();
        $pickup = clone $pickup;

        $count = 0;
        /** @var Store $shop */
        foreach ($shops as $shop) {
            $pickup->setSelectedStore($shop);
            if ($pickup->isSuccess()) {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    /**
     * @param string $code
     * @return bool
     */
    protected function isDefaultLocation(string $code): bool
    {
        return false;
    }
}
