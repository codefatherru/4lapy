<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\MobileApiBundle\Controller\v0;

use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Bitrix\Main\Application;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Model\Product;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\Catalog\Query\ProductQuery;
use FourPaws\Catalog\Table\CommentsTable;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;
use FourPaws\Helpers\TaggedCacheHelper;
use FourPaws\MobileApiBundle\Controller\BaseController;
use FourPaws\MobileApiBundle\Dto\Object\Catalog\FullProduct;
use FourPaws\MobileApiBundle\Dto\Request\GoodsBySpecialOfferRequest;
use FourPaws\MobileApiBundle\Dto\Request\GoodsListByRequestRequest;
use FourPaws\MobileApiBundle\Dto\Request\GoodsListRequest;
use FourPaws\MobileApiBundle\Dto\Request\GoodsSearchBarcodeRequest;
use FourPaws\MobileApiBundle\Dto\Request\GoodsSearchRequest;
use FourPaws\MobileApiBundle\Dto\Request\SpecialOffersRequest;
use FourPaws\MobileApiBundle\Dto\Response\SpecialOffersResponse;
use FourPaws\MobileApiBundle\Dto\Response\GoodsItemByRequestResponse;
use FourPaws\MobileApiBundle\Dto\Response as ApiResponse;
use FourPaws\MobileApiBundle\Dto\Request\GoodsItemRequest;
use FourPaws\MobileApiBundle\Dto\Response;
use FourPaws\MobileApiBundle\Exception\NotFoundProductException;
use FourPaws\SaleBundle\Service\BasketService as AppBasketService;
use FourPaws\UserBundle\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use FourPaws\MobileApiBundle\Services\Api\ProductService as ApiProductService;
use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\MobileApiBundle\Dto\Error;

class ProductController extends BaseController
{
    private $cacheTime = 3600;
    private $cachePath = '/api/banners';

    /**
     * @var ApiProductService
     */
    private $apiProductService;

    /**
     * @var AppBasketService
     */
    private $appBasketService;

    /**
     * @var UserService
     */
    private $userService;

    public function __construct(
        ApiProductService $apiProductService,
        AppBasketService $appBasketService,
        UserService $userService
    )
    {
        $this->apiProductService = $apiProductService;
        $this->appBasketService = $appBasketService;
        $this->userService = $userService;
    }

    /**
     * @Rest\Get(path="/special_offers/")
     * @Rest\View(serializerGroups={"Default", "specialOffers"})
     *
     * @param SpecialOffersRequest $specialOffersRequest
     * @return ApiResponse
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    public function getSpecialOffersAction(SpecialOffersRequest $specialOffersRequest): ApiResponse
    {
        $cache = Application::getInstance()->getCache();
        $cacheId = md5(serialize([
            $specialOffersRequest->getCount(),
            $specialOffersRequest->getPage(),
            $this->userService->getDiscount()
        ]));
        if ($cache->startDataCache($this->cacheTime, $cacheId, $this->cachePath)) {
            $tagCache = $cache->isStarted() ? new TaggedCacheHelper($this->cachePath) : null;

            $goods = [];

            $collection = (new ProductQuery())
                ->withFilter([
                    'ID' => \CIBlockElement::SubQuery('PROPERTY_CML2_LINK',
                        [
                            '=PROPERTY_IS_POPULAR' => '1',
                            '>CATALOG_PRICE_2' => 0,
                            'IBLOCK_ID' => IblockUtils::getIblockId(
                                IblockType::CATALOG,
                                IblockCode::OFFERS
                            ),
                        ]
                    )
                ])
                ->withNav([
                    'iNumPage' => $specialOffersRequest->getPage(),
                    'nPageSize' => $specialOffersRequest->getCount()
                ])
                ->withOrder(['SORT' => 'ASC'])
                ->withSelect(['ID'])
                ->exec();

            /** @var Product $product */
            foreach ($collection as $product) {
                $product->getOffers(true);
                /** @var Offer $offer */
                $offer = $product->getOffersSorted()->first();
                $shortProduct = $this->apiProductService->convertToShortProduct($product, $offer);
                // товары всегда доступны в каталоге (недоступные просто не должны быть в выдаче)
                $shortProduct->setIsAvailable(true);
                $goods[] = $shortProduct;
            }

            $cdbResult = $collection->getCdbResult();

            $response = new SpecialOffersResponse();
            $response
                ->setGoods($goods)
                ->setTotalItem(intval($cdbResult->NavRecordCount))
                ->setTotalPages(intval($cdbResult->NavPageCount));

            $apiResponse = (new ApiResponse())->setData($response);

            if ($tagCache) {
                TaggedCacheHelper::addManagedCacheTags([$this->cachePath]);
                $tagCache->end();
            }

            $cache->endDataCache($apiResponse);
        } else {
            $apiResponse = $cache->getVars();
        }

        return $apiResponse;
    }

    /**
     * @Rest\Get("/goods_list/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @param Request $request
     * @param GoodsListRequest $goodsListRequest
     * @return Response\ProductListResponse
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    public function getGoodsListAction(Request $request, GoodsListRequest $goodsListRequest)
    {
        $categoryId = $goodsListRequest->getCategoryId();
        $stockId = $goodsListRequest->getStockId();
        $sort = $goodsListRequest->getSort();
        $page = $goodsListRequest->getPage();
        $count = $goodsListRequest->getCount();

        $productsList = $this->apiProductService->getList($request, $categoryId, $sort, $count, $page, '', $stockId);
        /** @var \CIBlockResult $cdbResult */
        $cdbResult = $productsList->get('cdbResult');
        return (new Response\ProductListResponse())
            ->setProductList($productsList->get('products'))
            ->setTotalPages($cdbResult->NavPageCount ?: 0)
            ->setTotalItems($cdbResult->NavRecordCount ?: 0);
    }

    /**
     * @Rest\Get("/goods_item/")
     * @Rest\View(serializerGroups={"Default", "product"})
     * @param GoodsItemRequest $goodsItemRequest
     * @return Response
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     * @throws \FourPaws\StoreBundle\Exception\NotFoundException
     */
    public function getGoodsItemAction(GoodsItemRequest $goodsItemRequest)
    {
        $offer = $this->apiProductService->getOne($goodsItemRequest->getId());
        return (new Response())->setData([
            'goods' => $offer
        ]);
    }

    /**
     * @Rest\Get("/all_comments/")
     * @Rest\View(serializerGroups={"Default", "product"})
     * @param Request $request
     * @return Response
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     * @throws \FourPaws\StoreBundle\Exception\NotFoundException
     */
    public function allCommentsAction(Request $request)
    {
        $id = \CCatalogSku::GetProductInfo($request->get('id'))['ID'];
        $count = $request->get('count');

        $comments = $this->apiProductService->getAllProductCommentsWithNav($id, $count, $request->get('page'));

        $total = CommentsTable::query()
            ->setSelect(['ID'])
            ->setFilter(['=UF_OBJECT_ID' => $id, '=UF_ACTIVE' => 1])
            ->setCacheTtl('36000')
            ->exec()
            ->fetchAll();

        if (count($total)) {
            $totalItems = count($total);
            $totalPages = 1;
            if ($count) {
                $totalPages = ceil($totalItems/$count);
            }
        }

        return (new Response())->setData([
            'comments' => $comments,
            'total_pages' => $totalPages,
            'total_items' => $totalItems
        ]);
    }

    /**
     * @Rest\Post("/add_comment/")
     * @Rest\View(serializerGroups={"Default", "product"})
     * @param Request $request
     * @return Response
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     * @throws \FourPaws\StoreBundle\Exception\NotFoundException
     */
    public function addCommentAction(Request $request)
    {
        if (!$request->get('stars')) {
            $errors = new ArrayCollection([new Error(0, 'Нужно поставить оценку для отзыва')]);

            return (new Response())->setData([
                'success' => 0
            ])->setErrors($errors);
        } elseif ($request->get('stars') > 5) {
            $errors = new ArrayCollection([new Error(0, 'Нужно поставить корректную оценку для отзыва')]);

            return (new Response())->setData([
                'success' => 0
            ])->setErrors($errors);
        }

        $this->apiProductService->addProductComment($request);

        return (new Response())->setData([
            'success' => 1
        ]);
    }

    /**
     * @Rest\Get("/goods_search/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @param Request $request
     * @param GoodsSearchRequest $goodsSearchRequest
     * @return Response\ProductListResponse
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\Catalog\Exception\CategoryNotFoundException
     */
    public function getGoodsSearchAction(Request $request, GoodsSearchRequest $goodsSearchRequest)
    {
        $categoryId = 0;
        $sort = 'relevance';
        $page = $goodsSearchRequest->getPage();
        $count = $goodsSearchRequest->getCount();
        $query = $goodsSearchRequest->getQuery();

        $productsList = $this->apiProductService->getList($request, $categoryId, $sort, $count, $page, $query);
        /** @var \CIBlockResult $cdbResult */
        $cdbResult = $productsList->get('cdbResult');
        return (new Response\ProductListResponse())
            ->setProductList($productsList->get('products'))
            ->setTotalPages($cdbResult->NavPageCount)
            ->setTotalItems($cdbResult->NavRecordCount);
    }

    /**
     * @Rest\Get("/goods_search_barcode/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     *
     * @param Request $request
     * @param GoodsSearchBarcodeRequest $goodsSearchBarcodeRequest
     * @return Response
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     * @throws \FourPaws\StoreBundle\Exception\NotFoundException
     * @throws NotFoundProductException
     */
    public function getGoodsSearchBarcodeAction(
        Request $request,
        GoodsSearchBarcodeRequest $goodsSearchBarcodeRequest
    )
    {
        $categoryId = 0;
        $sort = 'relevance';
        $page = 1;
        $count = 1;
        $query = $goodsSearchBarcodeRequest->getBarcode();

        $offer = [];
        $productList = $this->apiProductService->getList($request, $categoryId, $sort, $count, $page, $query);
        if ($currentProduct = $productList->current()) {
            /** @var FullProduct $product */
            $product = $currentProduct[0];
            $offer = $this->apiProductService->getOne($product->getId());
        }
        if (empty($offer)) {
            throw new NotFoundProductException("Товар со штрихкодом $query не найден");
        }
        return (new Response())->setData([
            'goods' => $offer
        ]);
    }

    /**
     * @Rest\Get("/personal_goods/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @Security("has_role('REGISTERED_USERS')", message="Вы не авторизованы")
     * @return Response\ProductListResponse
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    public function getGoodsPersonalAction()
    {
        $products = [];
        $offerIds = $this->appBasketService->getPopularOfferIds(10);
        if (!empty($offerIds)) {
            $offers = (new OfferQuery())
                ->withFilter(['=ID' => $offerIds])
                ->exec();

            /** @var Offer $offer */
            foreach ($offers as $offer) {
                $product = $offer->getProduct();
                $products[] = $this->apiProductService->convertToFullProduct($product, $offer);
            }
        }
        return (new Response\ProductListResponse())
            ->setProductList($products);
    }

    /**
     * @Rest\Get("/goods_by_special_offer/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @param GoodsBySpecialOfferRequest $goodsBySpecialOfferRequest
     * @return Response\ProductListResponse
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     */
    public function getGoodsByOfferAction(GoodsBySpecialOfferRequest $goodsBySpecialOfferRequest)
    {
        $products = [];

        $offerIds = [];
        $iblockId = IblockUtils::getIblockId(IblockType::PUBLICATION, IblockCode::SHARES);
        $res = \CIBlockElement::GetProperty($iblockId, $goodsBySpecialOfferRequest->getId(), '', '', ['CODE' =>'PRODUCTS']);
        while ($row = $res->fetch()) {
            if (!empty($row['VALUE'])) {
                $offerIds[$row['VALUE']] = $row['VALUE'];
            }
        }

        if (!empty($offerIds)) {
            $offers = (new OfferQuery())
                ->withFilter([
                    '=XML_ID' => $offerIds,
                    'ACTIVE' => 'Y',
                    '>CATALOG_PRICE_2' => 0,
                ])->exec();
            /** @var Offer $offer */
            foreach ($offers as $offer) {
                $product = $offer->getProduct();
                $products[] = $this->apiProductService->convertToFullProduct($product, $offer);
            }
        }

        return (new Response\ProductListResponse())
            ->setProductList($products);
    }


    /**
     * @Rest\Get("/goods_list_by_request/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @param GoodsListByRequestRequest $goodsListByRequestRequest
     * @return Response
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @deprecated Информация о ТПЗ возвращается в объекте FourPaws\MobileApiBundle\Dto\Object\Catalog\ShortProduct
     */
    public function getGoodsListByRequestAction(GoodsListByRequestRequest $goodsListByRequestRequest)
    {
        $offerIds = $goodsListByRequestRequest->getIds();
        $collection = (new OfferQuery())
            ->withFilterParameter('ID', $offerIds)
            ->withSelect(['ID'])
            ->exec();
        $offers = [];
        /** @var Offer $offer */
        foreach ($collection as $offer) {
            $offers[] = [
                'id' => $offer->getId(),
                'isByRequest' => $offer->isByRequest()
            ];
        }
        return (new Response())->setData([
            'goods' => $offers
        ]);
    }

    /**
     * @Rest\Get("/goods_item_by_request/")
     * @Rest\View(serializerGroups={"Default", "productsList"})
     * @param GoodsItemRequest $goodsItemRequest
     * @return GoodsItemByRequestResponse
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \FourPaws\DeliveryBundle\Exception\NotFoundException
     * @throws \FourPaws\StoreBundle\Exception\NotFoundException
     * @deprecated Информация о ТПЗ возвращается в объекте FourPaws\MobileApiBundle\Dto\Object\Catalog\FullProduct
     */
    public function getGoodsItemByRequestAction(GoodsItemRequest $goodsItemRequest): GoodsItemByRequestResponse
    {
        $offerId = $goodsItemRequest->getId();
        $offer = (new OfferQuery())->getById($offerId);

        return (new GoodsItemByRequestResponse())
            ->setId($offer->getId())
            ->setIsByRequest($offer->isByRequest())
            ->setAvailability($offer->getAvailabilityText())
            ->setDelivery($this->apiProductService->getDeliveryText($offer))
            ->setPickup($this->apiProductService->getPickupText($offer));
    }
}
