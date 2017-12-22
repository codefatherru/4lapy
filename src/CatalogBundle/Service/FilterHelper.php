<?php

namespace FourPaws\CatalogBundle\Service;

use Adv\Bitrixtools\Tools\BitrixUtils;
use Bitrix\Highloadblock\DataManager;
use CIBlockProperty;
use CIBlockSectionPropertyLink;
use Exception;
use FourPaws\Catalog\Model\Category;
use FourPaws\Catalog\Model\Filter\FilterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use WebArch\BitrixCache\BitrixCache;

class FilterHelper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DataManager
     */
    private $filterTable;

    public function __construct(DataManager $dataManager)
    {
        $this->filterTable = $dataManager;
    }

    /**
     * Инициализация состояния фильтров по запросу
     *
     * @param Category $category
     * @param Request  $request
     *
     * @throws Exception
     */
    public function initCategoryFilters(Category $category, Request $request)
    {
        /** @var FilterInterface $filter */
        foreach ($category->getFilters() as $filter) {
            $filter->initState($request);
        }
    }

    /**
     * Получить настройки свойств элементов в зависимости от раздела(?). В том числе позволяет понять, что включено в
     * умном фильтре.
     *
     * @param int $iblockId
     * @param int $sectionId
     *
     * @return array
     */
    public function getSectionPropertyLinks($iblockId, $sectionId = 0): array
    {
        $propertyLinks = CIBlockSectionPropertyLink::GetArray($iblockId, $sectionId);
        $propIdList = array_filter(
            array_map(
                function ($propertyLinks) {
                    if (isset($propertyLinks['PROPERTY_ID']) && $propertyLinks['PROPERTY_ID'] > 0) {
                        return (int)$propertyLinks['PROPERTY_ID'];
                    }
                    return 0;
                },
                $propertyLinks
            ),
            function ($id) {
                return $id > 0;
            }
        );
        if (!\is_array($propIdList) || !(\count($propIdList) > 0)) {
            return $propertyLinks;
        }

        //Узнаём символьные коды свойств
        $dbPropList = CIBlockProperty::GetList([], ['=ID' => $propIdList]);
        $propCodeByIdIndex = [];
        while ($arProp = $dbPropList->Fetch()) {
            $propCodeByIdIndex[$arProp['ID']] = $arProp['CODE'];
        }

        foreach ($propertyLinks as $key => $propertyLink) {
            if (!isset($propCodeByIdIndex[$propertyLink['PROPERTY_ID']])) {
                continue;
            }

            $propertyLinks[$key]['PROPERTY_CODE'] = $propCodeByIdIndex[$propertyLink['PROPERTY_ID']];
        }

        return $propertyLinks;
    }

    /**
     * @param int $categoryId
     * @param int $iblockId
     * @return FilterInterface[]
     */
    public function getCategoryFilters(int $categoryId, int $iblockId): array
    {
        $availablePropIndexByCode = [];
        try {
            $availablePropIndexByCode = $this->getAvailablePropIndexByCode($categoryId, $iblockId);
        } catch (Exception $e) {
        }

        return array_filter($this->getFilters(), function (FilterInterface $filter) use ($availablePropIndexByCode) {
            return
                !$filter->getPropCode() ||
                array_key_exists($filter->getPropCode(), $availablePropIndexByCode);
        });
    }

    /**
     * @return FilterInterface[]
     */
    protected function getFilters(): array
    {
        $doGetFilterFieldsList = function () {
            $filterFieldsList = [];

            $dbAllFilterList = $this->filterTable::query()
                ->setSelect(['*'])
                ->setFilter(['UF_ACTIVE' => 1])
                ->setOrder(['UF_SORT' => 'ASC'])
                ->exec();
            while ($filterFields = $dbAllFilterList->fetch()) {
                $filterFieldsList[] = $filterFields;
            }

            return $filterFieldsList;
        };

        try {
            $filtersData = (new BitrixCache())->withId(__METHOD__)
                ->withTag('catalog:filters')
                ->resultOf($doGetFilterFieldsList);
        } catch (Exception $e) {
            return [];
        }


        $filtersData = array_filter($filtersData, function (array $filterFields) {
            return
                isset($filterFields['UF_CLASS_NAME']) &&
                class_exists($filterFields['UF_CLASS_NAME']) &&
                is_a($filterFields['UF_CLASS_NAME'], FilterInterface::class);
        });

        return array_map(function (array $filterFields) {
            $className = $filterFields['UF_CLASS_NAME'];
            return new $className($filterFields);
        }, $filtersData);
    }

    /**
     * Возвращает индекс доступных свойств категории по коду свойства.
     *
     * @param int $categoryId
     *
     * @param int $categoryIblockId
     * @throws Exception
     * @return array
     */
    protected function getAvailablePropIndexByCode(int $categoryId, int $categoryIblockId): array
    {
        $doGetAvailablePropIndexByCode = function () use ($categoryIblockId, $categoryId) {

            /**
             * Запросить информацию о привязках свойств к категориям
             */
            $propertyLinks = $this->getSectionPropertyLinks($categoryIblockId, $categoryId);

            /**
             * Составить индекс по коду свойства и только для свойств, выбранных для "умного фильтра"
             */
            $availablePropIndexByCode = [];
            foreach ($propertyLinks as $propertyLink) {
                if (
                    !isset($propertyLink['SMART_FILTER'], $propertyLink['PROPERTY_CODE'])
                    || $propertyLink['SMART_FILTER'] !== BitrixUtils::BX_BOOL_TRUE
                ) {
                    continue;
                }
                $availablePropIndexByCode[$propertyLink['PROPERTY_CODE']] = true;
            }

            return $availablePropIndexByCode;
        };

        return (new BitrixCache())->withId(__METHOD__)
            ->withTag('catalog:filters')
            ->resultOf($doGetAvailablePropIndexByCode);
    }
}
