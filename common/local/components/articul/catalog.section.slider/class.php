<?php

use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use FourPaws\Catalog\Model\Product;
use FourPaws\Catalog\Query\ProductQuery;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;

/**
 * Created by PhpStorm.
 * User: mmasterkov
 * Date: 24.07.2019
 * Time: 12:31
 */

class CCatalogSectionSlider extends \CBitrixComponent
{
    private $iblockId;
    private $productXmlIds;
    private $products;
    private $imageIds;
    private $titleImageIds;
    private $sectionIds;
    private $sections;
    private $sectionLinks;


    public function onPrepareComponentParams($params): array
    {
        if (!isset($params['CACHE_TIME'])) {
            $params['CACHE_TIME'] = 86400;
        }
        $params['TYPE'] = $params['TYPE'] ?: 'default';
        $this->iblockId = IblockUtils::getIblockId(IblockType::GRANDIN, IblockCode::CATALOG_SLIDER_PRODUCTS);
        return parent::onPrepareComponentParams($params);
    }

    public function executeComponent()
    {
        if($this->startResultCache()){
            $filter = [
                'IBLOCK_ID'    => $this->iblockId,
                'ACTIVE'       => 'Y',
                'SECTION_CODE' => $this->arParams['SECTION_CODE'] ?: false,
            ];

            $dbres = \CIBlockElement::GetList(['SORT' => 'ASC'], $filter);
            while($row = $dbres->GetNextElement()){
                $element = $row->GetFields();
                $element['PROPERTIES'] = $row->GetProperties();

                if(empty($element['PROPERTIES']['PRODUCTS']['VALUE'])){
                    $filterInner = [
                        'IBLOCK_ID' => IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::PRODUCTS),
                        'SECTION_ID' => $element['PROPERTIES']['SECTION']['VALUE'],
                        'ACTIVE' => 'Y',
                        'INCLUDE_SUBSECTION' => 'Y'
                    ];

//                    $dbresInner = ElementTable::getList([
//                        'select' => ['ID', 'XML_ID', 'NAME', 'IBLOCK_SECTION_ID'],
//                        'filter' => [
//                            'IBLOCK_SECTION_ID' => $element['PROPERTIES']['SECTION']['VALUE'],
//                            'ACTIVE' => 'Y'
//                        ],
//                        'limit'  => 10
//                    ]);

                    $dbresInner = \CIBlockElement::GetList([], $filterInner, false, ['nTopCount' => 10], ['ID', 'NAME', 'XML_ID', 'IBLOCK_SECTION_ID']);

                    while($rowInner = $dbresInner->Fetch()){
                        $element['PROPERTIES']['PRODUCTS']['VALUE'][] = $rowInner['XML_ID'];
                    }
                }

                foreach ($element['PROPERTIES']['PRODUCTS']['VALUE'] as $xmlId){
                    $this->productXmlIds[] = $xmlId;
                }

                $this->sectionIds[] = $element['PROPERTIES']['SECTION']['VALUE'];
                $this->titleImageIds[] = $element['PROPERTIES']['TITLE_IMAGE']['VALUE'];
                $this->imageIds[] = $element['PROPERTIES']['IMAGE']['VALUE'];

                $this->arResult['ELEMENTS'][] = $element;
            }

            $this->fillProducts();
            $this->fillImages();
            $this->fillSections();
            $this->fillLinks();

            $this->includeComponentTemplate();
        }
    }

    private function fillProducts()
    {
        if(empty($this->productXmlIds)){
            return;
        }

        $productCollection = (new ProductQuery())->withFilter(['XML_ID' => $this->productXmlIds])->exec();
        $this->products = $productCollection;
    }

    private function fillImages()
    {
        if(empty($this->imageIds)){
            return;
        }
        $dbres = CFile::GetList([], ['@ID' => implode(',', $this->imageIds)]);
        while($row = $dbres->Fetch()){
            $this->arResult['IMAGES'][$row['ID']] = COption::GetOptionString("main", "upload_dir", "upload") . "/" . $row["SUBDIR"] . "/" . $row["FILE_NAME"];
        }
        if(empty($this->titleImageIds)){
            return;
        }
        $dbres = CFile::GetList([], ['@ID' => implode(',', $this->titleImageIds)]);
        while($row = $dbres->Fetch()){
            $this->arResult['TITLE_IMAGES'][$row['ID']] = COption::GetOptionString("main", "upload_dir", "upload") . "/" . $row["SUBDIR"] . "/" . $row["FILE_NAME"];
        }
    }

    private function fillSections()
    {
        if(empty($this->sectionIds)){
            return;
        }

        $dbres = CIBlockSection::GetList([], ['ID' => $this->sectionIds], false, ['ID', 'NAME', 'SECTION_PAGE_URL']);
        while($row = $dbres->GetNext())
        {
            $this->sections[$row['ID']] = $row;
        }
    }

    private function fillLinks()
    {
        foreach ($this->arResult['ELEMENTS'] as $element) {
            if ($element['PROPERTIES']['SECTION_LINK']['VALUE']) {
                $this->sectionLinks[$element['ID']] = $element['PROPERTIES']['SECTION_LINK']['VALUE'];
            } else {
                $this->sectionLinks[$element['ID']] = $this->getSectionUrl($element['PROPERTIES']['SECTION']['VALUE']);
            }
        }
    }

    public function getProduct($xmlId)
    {
        return $this->products->filter(function ($product) use ($xmlId) {
            /** @var Product $product */
            return $product->getXmlId() == $xmlId;
        })->first();
    }

    public function getSectionUrl($id)
    {
        return $this->sections[$id]['SECTION_PAGE_URL'];
    }

    /**
     * sectionLink - строковое свойство у инфоблока
     * никак не связанное с привязанным разделом
     * @param $elementId
     * @return mixed
     */
    public function getSectionLink($elementId)
    {
        return $this->sectionLinks[$elementId];
    }
}