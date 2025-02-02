<?php

namespace FourPaws\Components;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use CBitrixComponent;
use FourPaws\Catalog\Collection\ProductCollection;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\Helpers\TaggedCacheHelper;

/** @noinspection AutoloadingIssuesInspection
 *
 * Class FourPawsProductsByProp
 */
class ProductsByProp extends CBitrixComponent
{
    /**
     * @var Context
     */
    private $context;
    /** @var Application */
    private $instance;

    /**
     * AutoloadingIssuesInspection constructor.
     *
     * @param null|\CBitrixComponent $component
     *
     * @throws SystemException
     * @throws LoaderException
     */
    public function __construct(CBitrixComponent $component = null)
    {
        parent::__construct($component);
        $this->instance = Application::getInstance();
        $this->context = $this->instance->getContext();
        Loader::includeModule('iblock');
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params): array
    {
        $params['IBLOCK_ID'] = (int)$params['IBLOCK_ID'];
        $params['ITEM_ID'] = (int)$params['ITEM_ID'];
        $params['SLIDER'] = $params['SLIDER'] ?? 'N';
        $params['IS_SHARE'] = $params['IS_SHARE'] ?? false;
        if (!\is_bool($params['IS_SHARE'])) {
            if (!empty($params['IS_SHARE']) && $params['IS_SHARE'] === 'Y') {
                $params['IS_SHARE'] = true;
            } else {
                $params['IS_SHARE'] = false;
            }
        }
        $params['COUNT_ON_PAGE'] = (int)$params['COUNT_ON_PAGE'];
        if ($params['COUNT_ON_PAGE'] === 0) {
            $params['COUNT_ON_PAGE'] = 20;
        }
        $params['PROPERTY_CODE'] = $params['PROPERTY_CODE'] ?? '';
        $params['FILTER_FIELD'] = $params['FILTER_FIELD'] ?? 'ID';
        $params['TITLE'] = $params['TITLE'] ?? 'Товары';
        $params['SHOW_PAGE_NAVIGATION'] = $params['SHOW_PAGE_NAVIGATION'] ?? true;

        $params['CACHE_TIME'] = $params['CACHE_TIME'] ?? 360000;
        $params['CACHE_TYPE'] = $params['CACHE_TIME'] === 0 ? 'N' : $params['CACHE_TYPE'];
        if (empty($params['CACHE_TYPE'])) {
            $params['CACHE_TYPE'] = 'A';
        }

        $params['SORT'] = $params['SORT'] ? $params['SORT'] : 'SORT';
        $params['ORDER'] = $params['ORDER'] ? $params['ORDER'] : 'ASC';

        return parent::onPrepareComponentParams($params);
    }

    /**
     * @return null|bool
     */
    public function executeComponent(): ?bool
    {
        if ($this->arParams['IBLOCK_ID'] === 0 || $this->arParams['ITEM_ID'] === 0 || empty($this->arParams['PROPERTY_CODE'])) {
            return false;
        }

        $this->arParams['CURRENT_PAGE'] = (int)$this->context->getRequest()->get('page');
        if ($this->arParams['CURRENT_PAGE'] === 0) {
            $this->arParams['CURRENT_PAGE'] = 1;
        }

        $this->arResult['OFFERS'] = new ProductCollection(new \CIblockResult());
        if ($this->startResultCache()) {
            TaggedCacheHelper::addManagedCacheTags([
                'iblock:item:' . $this->arParams['ITEM_ID'],
            ]);

            parent::executeComponent();

            $res = \CIBlockElement::GetProperty($this->arParams['IBLOCK_ID'], $this->arParams['ITEM_ID'], '', '',
                ['CODE' => $this->arParams['PROPERTY_CODE']]);
            $products = [];
            while ($item = $res->Fetch()) {
                if (!empty($item['VALUE']) && !\in_array($item['VALUE'], $products, true)) {
                    $products[] = $item['VALUE'];
                }
            }
            $this->arResult['OFFERS_IDS'] = $products;
            if (!empty($products)) {
                $query = new OfferQuery();
                if ($this->arParams['COUNT_ON_PAGE'] > 0) {
                    if ($this->arParams['SHOW_PAGE_NAVIGATION'] && $this->arParams['SLIDER'] !== 'Y') {
                        $query->withNav([
                            'nPageSize' => $this->arParams['COUNT_ON_PAGE'],
                            'iNumPage'  => $this->arParams['CURRENT_PAGE'],
                        ]);
                    } else {
                        $query->withNav([
                            'nTopCount' => $this->arParams['COUNT_ON_PAGE'],
                        ]);
                    }
                }
                $this->arResult['OFFERS'] = $query->withFilter([
                    '=' . $this->arParams['FILTER_FIELD'] => $products,
                    'ACTIVE'                              => 'Y',
                    '>CATALOG_PRICE_2'                    => 0,
                ])->withOrder([
                    $this->arParams['SORT'] => $this->arParams['ORDER']
                ])->exec();
            }

            $this->includeComponentTemplate();
        }
        return true;
    }
}
