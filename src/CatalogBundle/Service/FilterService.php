<?php

namespace FourPaws\CatalogBundle\Service;

use Elastica\QueryBuilder;
use FourPaws\Catalog\Collection\FilterCollection;
use FourPaws\Catalog\Model\Category;
use FourPaws\Catalog\Model\Filter\Abstraction\FilterBase;
use FourPaws\Catalog\Model\Filter\InternalFilter;
use FourPaws\Catalog\Model\Variant;
use FourPaws\EcommerceBundle\Service\DataLayerService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class FilterService
 *
 * @package FourPaws\CatalogBundle\Service
 */
class FilterService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var FilterHelper
     */
    private $filterHelper;
    /**
     * @var DataLayerService
     */
    private $dataLayerService;

    /**
     * FilterService constructor.
     *
     * @param FilterHelper $filterHelper
     */
    public function __construct(FilterHelper $filterHelper, DataLayerService $dataLayerService)
    {
        $this->filterHelper = $filterHelper;
        $this->dataLayerService = $dataLayerService;
    }

    /**
     * @param Category $category
     *
     * @return FilterCollection
     */
    public function getCategoryFilters(Category $category): FilterCollection
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $filterCollection = new FilterCollection();

        /**
         * При фильтрации по категории всегда фильтруем с ней
         */
        if ($category->getId()) {
            $filterCollection->add($category);
        }

        /**
         * Фильтры активности
         */
        foreach ($this->getInternalFilters() as $filter) {
            $filterCollection->add($filter);
        }

        /**
         * Загружаем фильтры по категории
         */
        $categoryFilters = $this->filterHelper->getCategoryFilters($category->getId(), $category->getIblockId());
        foreach ($categoryFilters as $categoryFilter) {
            $filterCollection->add($categoryFilter);
        }

        /**
         * @var FilterBase $filter
         * @var Variant $variant
         */
        foreach ($filterCollection as $filter) {
            foreach ($filter->getAvailableVariants() as $variant) {
                $name = $filter instanceof Category ? 'Категория' : $filter->getName();
                $variant->setOnclick($this->dataLayerService->renderCatalogFilter($name, $variant->getName()));
            }
        }

        return $filterCollection;
    }

    /**
     * Возвращает внутренние неотключаемые фильтры, которые должны добавляться к любому запросу товаров из
     * Elasticsearch, чтобы обеспечить корректность выборки: активные бренды, товары, офферы, цена правильного региона.
     *
     * @return FilterCollection
     */
    public function getInternalFilters(): FilterCollection
    {
        // В будущем можно добавить учёт дат активности элементов инфоблоков.
        // See: https://www.elastic.co/guide/en/elasticsearch/reference/5.5/query-dsl-range-query.html

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $internalFilterCollection = new FilterCollection();


        try {
            foreach ($this->getActiveFilters() as $filter) {
                $internalFilterCollection->add($filter);
            }

            /**
             * @todo Фильтр по региональной цене
             */
            //            $internalFilterCollection->add($this->getRegionInternalFilter());
        } catch (\InvalidArgumentException $exception) {
            /**
             * @todo log exception
             */
        }

        return $internalFilterCollection;
    }

    /**
     * @return FilterHelper
     */
    public function getFilterHelper(): FilterHelper
    {
        return $this->filterHelper;
    }

    /**
     * Фильтры отвечающие за активность продукта, бренда, офферов
     *
     * @return FilterCollection
     */
    protected function getActiveFilters(): FilterCollection
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $activeFilterCollection = new FilterCollection();

        $queryBuilder = new QueryBuilder();
        try {
            $activeFilterCollection->add(
                InternalFilter::create(
                    'ProductActive',
                    $queryBuilder->query()->term(['active' => true])
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'ProductSectionDefined',
                    $queryBuilder->query()->bool()->addMust(
                        $queryBuilder->query()->exists('sectionIdList')
                    )
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'BrandActive',
                    $queryBuilder->query()->term(['brand.active' => true])
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'OffersActive',
                    $queryBuilder->query()->nested()
                                 ->setPath('offers')
                                 ->setQuery($queryBuilder->query()->term(['offers.active' => true]))
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'OffersPrice',
                    $queryBuilder
                        ->query()
                        ->nested()
                        ->setPath('offers')
                        ->setQuery(
                            $queryBuilder
                                ->query()
                                ->range('offers.price', ['gt' => 0])
                        )
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'HasImages',
                    $queryBuilder->query()->term(['hasImages' => true])
                )
            );

            $activeFilterCollection->add(
                InternalFilter::create(
                    'HasStocks',
                    $queryBuilder->query()->term(['hasStocks' => true])
                )
            );
        } catch (\InvalidArgumentException $exception) {
        }

        return $activeFilterCollection;
    }

    /**
     * @todo
     *
     * @return InternalFilter
     */
    protected function getRegionInternalFilter(): InternalFilter
    {
        //$currentRegionCode = $this->locationService->getCurrentRegionCode();
        //        $currentRegionCode = LocationService::DEFAULT_REGION_CODE;
        //
        //        return InternalFilter::create(
        //            'CurrentRegion',
        //            (new Nested())->setPath('offers.prices')
        //                ->setQuery(new Term(['offers.prices.REGION_ID' => $currentRegionCode]))
        //        );
    }
}
