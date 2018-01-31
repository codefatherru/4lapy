<?php

namespace FourPaws\Components;

use Bitrix\Catalog\CatalogViewedProductTable;
use Bitrix\Catalog\Product\Basket;
use Bitrix\Iblock\Component\Tools;
use Bitrix\Main\Analytics\Catalog;
use Bitrix\Main\Analytics\Counter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Text\JsExpression;
use FourPaws\Catalog\Model\Category;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Model\Product;
use FourPaws\Catalog\Query\CategoryQuery;
use FourPaws\Catalog\Query\ProductQuery;

/** @noinspection AutoloadingIssuesInspection */
class CatalogElementDetailComponent extends \CBitrixComponent
{
    const EXPAND_CLOSURES = 'EXPAND_CLOSURES';
    
    /**
     * @param $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params): array
    {
        if (!isset($params['CACHE_TIME'])) {
            $params['CACHE_TIME'] = 36000000;
        }
    
        $params['CODE']                    = $params['CODE'] ?? '';
        $params['OFFER_ID']                = $params['OFFER_ID'] ?? 0;
        $params['SET_TITLE']               = ($params['SET_TITLE'] === 'Y') ? $params['SET_TITLE'] : 'N';
        $params['SET_VIEWED_IN_COMPONENT'] = $params['SET_VIEWED_IN_COMPONENT'] ?? 'Y';
        
        return parent::onPrepareComponentParams($params);
    }

    public function executeComponent()
    {
        if (!$this->arParams['CODE']) {
            Tools::process404([], true, true, true);
        }

        if ($this->startResultCache()) {
            parent::executeComponent();

            /** @var Product $product */
            $product      = $this->getProduct($this->arParams['CODE']);
            $currentOffer = $this->getCurrentOffer($product);

            if (!$product) {
                $this->abortResultCache();
                Tools::process404([], true, true, true);
            }
    
            $sectionId = (int)reset($product->getSectionsIdList());

            $this->arResult = [
                'PRODUCT'       => $product,
                'CURRENT_OFFER' => $currentOffer,
                'SECTION_CHAIN' => $this->getSectionChain($sectionId),
                /**
                 * @todo впилить seo
                 */
                // возможно, понадобится в будущем
                //'SECTION' => $this->getSection($sectionId),
            ];

            $this->includeComponentTemplate();
        }

        // bigdata
        $this->obtainCounterData();
        $this->sendCounters();
        $this->setMeta();
        $this->saveViewedProduct();

        return $this->arResult['PRODUCT'];
    }

    /**
     * @param string $code
     *
     * @return Product
     */
    protected function getProduct(string $code) : Product
    {
        return (new ProductQuery())
            ->withFilterParameter('CODE', $code)
            ->exec()
            ->first();
    }

    /**
     * @param int $sectionId
     *
     * @return array
     */
    protected function getSectionChain(int $sectionId)
    {
        $sectionChain = [];
        if ($sectionId > 0) {
            $items = \CIBlockSection::GetNavChain(false, $sectionId, ['ID', 'NAME']);
            while ($item = $items->getNext(true, false)) {
                $sectionChain[] = $item;
            }
        }

        return $sectionChain;
    }

    /**
     * @param int $sectionId
     *
     * @return null|Category
     */
    protected function getSection(int $sectionId)
    {
        if ($sectionId <= 0) {
            return null;
        }

        return (new CategoryQuery())
            ->withFilterParameter('ID', $sectionId)
            ->exec()
            ->first();
    }

    /**
     * Добавление в просмотренные товары при генерации результата
     */
    protected function saveViewedProduct()
    {
        if ($this->arParams['SET_VIEWED_IN_COMPONENT'] === 'Y' && !empty($this->arResult['PRODUCT'])) {
            // задано действие добавления в просмотренные при генерации результата
            // (в идеале это нужно делать черех ajax)
            if (Basket::isNotCrawler()) {
                /** @var Product $product */
                $product = $this->arResult['PRODUCT'];
                $currentOffer = $product->getOffers()->first();
                $parentId = $product->getId();
                $productId = $currentOffer ? $currentOffer->getId() : 0;
                $productId = $productId > 0 ? $productId : $parentId;

                CatalogViewedProductTable::refresh(
                    $productId,
                    \CSaleBasket::GetBasketUserID(),
                    $this->getSiteId(),
                    $parentId
                );
            }
        }
    }

    /**
     * Получение данных для BigData
     *
     * @return void
     */
    protected function obtainCounterData()
    {
        if (empty($this->arResult['PRODUCT'])) {
            return;
        }
        /** @var Product $product */
        $product = $this->arResult['PRODUCT'];

        $categoryId = '';
        $categoryPath = [];
        if ($this->arResult['SECTION_CHAIN']) {
            foreach ($this->arResult['SECTION_CHAIN'] as $cat)  {
                $categoryPath[$cat['ID']] = $cat['NAME'];
                $categoryId = $cat['ID'];
            }
        }

        $counterData = array(
            'product_id' => $product->getId(),
            'iblock_id' => $product->getIblockId(),
            'product_title' => $product->getName(),
            'category_id' => $categoryId,
            'category' => $categoryPath
        );
    
        $currentOffer            = $this->getCurrentOffer($product);
        $counterData['price']    = $currentOffer ? $currentOffer->getPrice() : 0;
        $counterData['currency'] = $currentOffer ? $currentOffer->getCurrency() : '';

        // make sure it is in utf8
        $counterData = Encoding::convertEncoding($counterData, SITE_CHARSET, 'UTF-8');

        // pack value and protocol version
        $rcmLogCookieName = Option::get('main', 'cookie_name', 'BITRIX_SM').'_'.\Bitrix\Main\Analytics\Catalog::getCookieLogName();

        $this->arResult['counterDataSource'] = $counterData;
        $this->arResult['counterData'] = [
            'item' => base64_encode(json_encode($counterData)),
            'user_id' => new JsExpression(
                'function(){return BX.message("USER_ID") ? BX.message("USER_ID") : 0;}'
            ),
            'recommendation' => new JsExpression(
                'function() {
                    var rcmId = "";
                    var cookieValue = BX.getCookie("' . $rcmLogCookieName . '");
                    var productId = ' . $product->getId() . ';
                    var cItems = [];
                    var cItem;

                    if (cookieValue)
                    {
                        cItems = cookieValue.split(".");
                    }

                    var i = cItems.length;
                    while (i--)
                    {
                        cItem = cItems[i].split("-");
                        if (cItem[0] == productId)
                        {
                            rcmId = cItem[1];
                            break;
                        }
                    }

                    return rcmId;
                }'
            ),
            'v' => '2'
        ];
    }

    /**
     * Отправка bigdata
     *
     * @return void
     */
    protected function sendCounters()
    {
        if (isset($this->arResult['counterData']) && Catalog::isOn())  {
            Counter::sendData('ct', $this->arResult['counterData']);
        }
    }
    
    /**
     * @todo from inheritedProperties
     */
    protected function setMeta()
    {
        global $APPLICATION;
        
        if ($this->arParams['SET_TITLE'] === 'Y') {
            $APPLICATION->SetTitle($this->arResult['PRODUCT']->getName());
        }
    }
    
    /**
     * @param Product $product
     *
     * @return Offer
     */
    protected function getCurrentOffer(Product $product) : Offer
    {
        $offerId = (int)$this->arParams['OFFER_ID'];
        
        if ($offerId) {
            foreach ($product->getOffers() as $offer) {
                if ($offer->getId() === $offerId) {
                    return $offer;
                }
            }
        }
        
        return $product->getOffers()->first();
    }
}
