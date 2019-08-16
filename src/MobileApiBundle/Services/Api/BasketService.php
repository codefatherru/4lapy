<?php

/**
 * @copyright Copyright (c) NotAgency
 */

namespace FourPaws\MobileApiBundle\Services\Api;


use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Bitrix\Iblock\ElementTable;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Internals\EntityCollection;
use FourPaws\App\Application;
use FourPaws\AppBundle\Entity\BaseEntity;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\Components\BasketComponent;
use FourPaws\DeliveryBundle\Entity\CalculationResult\CalculationResultInterface;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Enum\IblockElementXmlId;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;
use FourPaws\Helpers\BxCollection;
use FourPaws\MobileApiBundle\Collection\BasketProductCollection;
use FourPaws\MobileApiBundle\Dto\Object\Basket\Product;
use FourPaws\MobileApiBundle\Dto\Object\Price;
use FourPaws\MobileApiBundle\Dto\Object\PriceWithQuantity;
use FourPaws\PersonalBundle\Service\OrderSubscribeService;
use FourPaws\SaleBundle\Service\BasketService as AppBasketService;
use FourPaws\MobileApiBundle\Services\Api\ProductService as ApiProductService;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use FourPaws\UserBundle\Service\UserService as AppUserService;

class BasketService
{
    /**
     * @var AppBasketService
     */
    private $appBasketService;

    /**
     * @var ApiProductService;
     */
    private $apiProductService;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /** @var DeliveryService */
    private $deliveryService;

    /** @var AppUserService */
    private $appUserService;

    /** @var OrderSubscribeService */
    private $orderSubscribeService;

    public function __construct(
        AppBasketService $appBasketService,
        ApiProductService $apiProductService,
        TokenStorageInterface $tokenStorage,
        DeliveryService $deliveryService,
        AppUserService $appUserService,
        OrderSubscribeService $orderSubscribeService
    )
    {
        $this->appBasketService = $appBasketService;
        $this->apiProductService = $apiProductService;
        $this->tokenStorage = $tokenStorage;
        $this->deliveryService = $deliveryService;
        $this->appUserService = $appUserService;
        $this->orderSubscribeService = $orderSubscribeService;
    }


    /**
     * @param bool $onlyOrderable флаг запрашивать ли товары доступные для покупки или все товары (в том числе и недоступные для покупки)
     * @return BasketProductCollection
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\SaleBundle\Exception\BitrixProxyException
     */
    public function getBasketProducts(bool $onlyOrderable = false): BasketProductCollection
    {
        $deliveries = $this->deliveryService->getByLocation();
        $delivery = null;
        foreach ($deliveries as $calculationResult) {
            if ($this->deliveryService->isDelivery($calculationResult)) {
                $delivery = $calculationResult;
                break;
            }
        }

        $fUserId = $this->appUserService->getCurrentFUserId() ?: 0;
        $basket = $this->appBasketService->getBasket(true, $fUserId);

        /**
         * Непонятный код для того чтобы корреткно работали подарки (бесплатные товары) в рамках акций "берешь n товаров, 1 бесплатно"
         * @see BasketComponent::executeComponent()
         */
        if (null === $order = $basket->getOrder()) {
            try {
                $userId = $this->appUserService->getCurrentUserId();
            } /** @noinspection BadExceptionsProcessingInspection */
            catch (NotAuthorizedException $e) {
                $userId = null;
            }

            if ($userId && count($basket->getOrderableItems()) > 0) {
                $user = $this->appUserService->getCurrentUser();
                $needAddDobrolapMagnet = $user->getGiftDobrolap();
                /** Если пользователю должны магнит */
                if ($needAddDobrolapMagnet == BaseEntity::BITRIX_TRUE || $needAddDobrolapMagnet == true || $needAddDobrolapMagnet == 1) {
                    $magnetID = $this->appBasketService->getDobrolapMagnet()['ID'];
                    /** если магнит найден как оффер */
                    if ($magnetID) {
                        $basketItem = $this->appBasketService->addOfferToBasket(
                            (int)$magnetID,
                            1,
                            [],
                            true,
                            $basket
                        );
                        $result = $basket->save();
                        /** если магнит успешно добавлен в корзину */
                        if ($basketItem->getId() && $result->isSuccess()) {
                            $userDB = new \CUser;
                            $fields = [
                                'UF_GIFT_DOBROLAP' => false
                            ];
                            $userDB->Update($userId, $fields);
                        }
                    }
                }
            }

            $order =  \Bitrix\Sale\Order::create(SITE_ID, $userId);
            $order->setBasket($basket);
            // но иногда он так просто не запускается
            if (!\FourPaws\SaleBundle\Discount\Utils\Manager::isExtendCalculated()) {
                $order->doFinalAction(true);
            }
        }

        $products = new BasketProductCollection();
        $basketItems = $onlyOrderable ? $basket->getOrderableItems()->getBasketItems() : $basket->getBasketItems();
        // В этом массиве будут храниться детализация цены для каждого товара в случае акций "берешь n товаров, 1 бесплатно", "50% скидка на второй товар" и т.д.

        foreach ($basketItems as $basketItem) {
            $offer = OfferQuery::getById($basketItem->getProductId());
            if ($this->isSubProduct($basketItem) && !in_array($offer->getXmlId(), [AppBasketService::GIFT_DOBROLAP_XML_ID, AppBasketService::GIFT_DOBROLAP_XML_ID_ALT])) {
                continue;
            }

            /** @var $basketItem BasketItem */
            $useStamps = false;
            if (isset($basketItem->getPropertyCollection()->getPropertyValues()['USE_STAMPS'])) {
                $useStamps = (bool)$basketItem->getPropertyCollection()->getPropertyValues()['USE_STAMPS']['VALUE'];
            }

            $canUseStamps = false;
            if (isset($basketItem->getPropertyCollection()->getPropertyValues()['MAX_STAMPS_LEVEL'])) {
                $canUseStamps = (bool)$basketItem->getPropertyCollection()->getPropertyValues()['MAX_STAMPS_LEVEL']['VALUE']; //FIXME если нужно отображать размер скидки в рублях, процент скидки, то можно посчитать их здесь
            }

            $product = $this->getBasketProduct($basketItem->getId(), $offer, $basketItem->getQuantity(), $useStamps, $canUseStamps);
            $shortProduct = $product->getShortProduct();
            $shortProduct->setPickupOnly(
                $this->isPickupOnly($basketItem, $delivery, $offer)
            );
            if (isset($basketItem->getPropertyCollection()->getPropertyValues()['IS_GIFT'])) {
                $shortProduct->setGiftDiscountId($basketItem->getPropertyCollection()->getPropertyValues()['IS_GIFT']['VALUE']);
                $shortProduct->setPrice((new Price())->setActual(0)->setOld(0));
            }

            $product->setShortProduct($shortProduct);
            $products->add($product);

        }

        $products = $this->fillBasketProductsPrices($basketItems, $products);

        return $products;
    }


    /**
     * Фильтруем товары в рамках акций n+1, 50% за второй товар и т.д.
     * Если basketCode = n1, n2 ... nX - значит это акционный товар например в рамках акции "берешь n товаров, 1 бесплатно" (sic!)
     * по сути является подпродуктом базового продукта
     * @see BasketComponent::calcTemplateFields()
     *
     * @param array|EntityCollection $basketItems
     * @param BasketProductCollection $products
     * @return BasketProductCollection
     */
    private function fillBasketProductsPrices($basketItems, $products)
    {
        /** @var PriceWithQuantity[][] $pricesWithQuantityAll */
        $pricesWithQuantityAll = [];
        foreach ($products as $product) {
            /** @var Product $product */
            if ($isGift = $product->getShortProduct()->getGiftDiscountId() > 0) {
                continue;
            }
            /** @var BasketItem $basketItem */
            foreach ($basketItems as $basketItem) {
                if (
                    (int)$product->getShortProduct()->getId() === (int)$basketItem->getProductId()
                    &&
                    !isset($basketItem->getPropertyCollection()->getPropertyValues()['IS_GIFT'])
                ) {
                    $pricesWithQuantityAll[$product->getBasketItemId()][] = (new PriceWithQuantity())
                        ->setPrice(
                            (new Price)
                                ->setActual($basketItem->getPrice())
                                ->setOld($basketItem->getBasePrice())
                                ->setSubscribe($this->orderSubscribeService->getSubscribePriceByBasketItem($basketItem))
                        )
                        ->setQuantity($basketItem->getQuantity())
                    ;
                }
            }
        }

        return $products->map(function ($product) use ($pricesWithQuantityAll) {
            /** @var Product $product */
            if (array_key_exists($product->getBasketItemId(), $pricesWithQuantityAll)) {
                $pricesWithQuantity = $pricesWithQuantityAll[$product->getBasketItemId()];
                $totalQuantity = 0;
                foreach ($pricesWithQuantity as $priceWithQuantity) {
                    $totalQuantity += $priceWithQuantity->getQuantity();
                }
                $product->setQuantity($totalQuantity);
                $product->setPrices($pricesWithQuantity);
            }
            return $product;
        });
    }

    /**
     * @param int $basketItemId
     * @param Offer $offer
     * @param int $quantity
     * @param bool|null $useStamps
     * @param bool|null $canUseStamps
     * @return Product
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    public function getBasketProduct(int $basketItemId, Offer $offer, int $quantity, ?bool $useStamps, ?bool $canUseStamps = false)
    {
        $product = $offer->getProduct();
        $shortProduct = $this->apiProductService->convertToShortProduct($product, $offer, $quantity);

        return (new Product())
            ->setBasketItemId($basketItemId)
            ->setShortProduct($shortProduct)
            ->setQuantity($quantity)
            ->setUseStamps($useStamps)
            ->setCanUseStamps($canUseStamps)
        ;
    }

    /**
     * @param BasketItem $basketItem
     * @param CalculationResultInterface $delivery
     * @param Offer $offer
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    protected function isPickupOnly(BasketItem $basketItem, CalculationResultInterface $delivery, Offer $offer)
    {
        try {
            if (!$basketItem->isDelay()) {
                if ($basketItem->getPrice() && (
                        (null === $delivery) ||
                        !(clone $delivery)->setStockResult(
                            $this->deliveryService->getStockResultForOffer(
                                $offer,
                                $delivery,
                                (int)$basketItem->getQuantity(),
                                $basketItem->getPrice()
                            )
                        )->isSuccess()
                    )
                ) {
                    return true;
                }
            }
        } catch (\FourPaws\DeliveryBundle\Exception\NotFoundException $e) {
            // do nothing
        } catch (\FourPaws\StoreBundle\Exception\NotFoundException $e) {
            // do nothing
        }
        return false;
    }

    /**
     * @param BasketItem $basketItem
     * @return bool
     */
    private function isSubProduct(BasketItem $basketItem): bool
    {
        return strpos($basketItem->getBasketCode(), 'n') === 0;
    }
}
