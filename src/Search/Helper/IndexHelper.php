<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\Search\Helper;

use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Adv\Bitrixtools\Tools\Log\LazyLoggerAwareTrait;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Result;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\InvalidException;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Search;
use Exception;
use FourPaws\App\Application;
use FourPaws\App\Env;
use FourPaws\BitrixOrm\Collection\CollectionBase;
use FourPaws\Catalog\Model\Brand;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Model\Product;
use FourPaws\Catalog\Query\BrandQuery;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\Catalog\Query\ProductQuery;
use FourPaws\DeliveryBundle\Handler\DeliveryHandlerBase;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;
use FourPaws\LocationBundle\LocationService;
use FourPaws\Search\Enum\DocumentType;
use FourPaws\Search\Exception\Index\IndexExceptionInterface;
use FourPaws\Search\Exception\Index\NotActiveException;
use FourPaws\Search\Exception\Index\WrongEntityPassedException;
use FourPaws\Search\Factory;
use FourPaws\Search\Model\CatalogSyncMsg;
use FourPaws\StoreBundle\Service\StoreService;
use JMS\Serializer\Serializer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

class IndexHelper implements LoggerAwareInterface
{
    use LazyLoggerAwareTrait;

    const ENV_NUMBER_OF_SHARDS = 'ELS_NUMBER_OF_SHARDS';
    const ENV_NUMBER_OF_REPLICAS = 'ELS_NUMBER_OF_REPLICAS';

    /**
     * @var Index
     */
    protected $catalogIndex;
    /**
     * @var Client
     */
    private $client;
    /**
     * @var Factory
     */
    private $factory;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Producer
     */
    private $catalogSyncProducer;

    /**
     * IndexHelper constructor.
     *
     * @param Client     $client
     * @param Factory    $factory
     *
     * @param Serializer $serializer
     * @param Producer   $catalogSyncProducer
     */
    public function __construct(Client $client, Factory $factory, Serializer $serializer, Producer $catalogSyncProducer)
    {
        $this->client = $client;
        $this->factory = $factory;
        $this->serializer = $serializer;
        $this->catalogSyncProducer = $catalogSyncProducer;
    }

    /**
     * @param CatalogSyncMsg $catalogSyncMsg
     */
    public function publishSyncMessage(CatalogSyncMsg $catalogSyncMsg)
    {
        $this->catalogSyncProducer->publish(
            $this->serializer->serialize($catalogSyncMsg, 'json')
        );
    }

    /**
     * @return Index
     */
    public function getCatalogIndex(): Index
    {
        if (null === $this->catalogIndex) {
            $this->catalogIndex = $this->client->getIndex($this->getIndexName('catalog'));
        }

        return $this->catalogIndex;
    }

    /**
     * @param bool $force
     *
     * @throws InvalidException
     * @throws RuntimeException
     * @return bool
     */
    public function createCatalogIndex(bool $force = false): bool
    {
        $catalogIndex = $this->getCatalogIndex();

        $indexExists = $catalogIndex->exists();
        if ($indexExists && !$force) {
            return false;
        }

        try {
            if ($indexExists && $force) {
                $catalogIndex->delete();
            }

            $catalogIndex->create($this->getCatalogIndexSettings());
        } catch (ResponseException $exception) {
            $this->log()->error(
                sprintf(
                    'Ошибка создания индекса %s: [%s] %s',
                    $catalogIndex->getName(),
                    $exception->getCode(),
                    $exception->getMessage()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getCatalogIndexSettings(): array
    {
        $shards = (getenv(self::ENV_NUMBER_OF_SHARDS)) ?: 1;
        $replicas = (getenv(self::ENV_NUMBER_OF_REPLICAS)) ?: 1;
        $qwertyRu = mb_split('\s', 'й ц у к е н г ш щ з х ъ ф ы в а п р о л д ж э я ч с м и т ь б ю');
        $qwertyEn = mb_split('\s', 'q w e r t y u i o p [ ] a s d f g h j k l ; \' z x c v b n m , .');
        $characterMap = function ($value1, $value2) {
            return $value1 . ' => ' . $value2;
        };
        $enToRuMapping = array_map($characterMap, $qwertyEn, $qwertyRu);
        $ruToEnMapping = array_map($characterMap, $qwertyRu, $qwertyEn);

        return [
            'settings' => [
                'number_of_shards' => $shards,
                'number_of_replicas' => $replicas,
                'analysis'         =>
                    [
                        'analyzer'    => [
                            'default'          => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop'
                                ],
                            ],
                            'autocomplete'     => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop',
                                    'russian_stemmer',
                                    'autocomplete_filter',
                                ],
                            ],
                            'full-text-search' => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop',
                                    'russian_stemmer',
                                ],
                            ],
                            'full-text-brand-hard-search' => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop',
                                    'russian_stemmer',
                                ],
                            ],
                            'word-suggest-hard-search' => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop',
                                    'russian_stemmer',
                                ],
                            ],
                            'detail-text-analyzator' => [
                                'type'      => 'custom',
                                'tokenizer' => 'standard',
                                'filter'    => [
                                    'lowercase',
                                    'russian_stop',
                                    'russian_stemmer',
                                ],
                            ],
                        ],
                        'filter'      => [
                            'autocomplete_filter' => [
                                'type'     => 'edge_ngram',
                                'min_gram' => 1,
                                'max_gram' => 20,
                            ],
                            'russian_stop'        => [
                                'type'      => 'stop',
                                'stopwords' => '_russian_',
                            ],
                            'russian_stemmer'     => [
                                'type'     => 'stemmer',
                                'language' => 'russian',
                            ],
                            'transform-to-latin'  => [
                                'type' => 'icu_transform',
                                'id'   => 'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC',
                            ],
                            'phonetic_cyrillic' => [
                                'type' => 'phonetic',
                                'encoder' => 'beider_morse',
                                'rule_type' => 'exact',
                                'name_type' => 'generic',
                                'languageset' => ['cyrillic']
                            ],
                            'phonetic_english' => [
                                'type' => 'phonetic',
                                'encoder' => 'beider_morse',
                                'rule_type' => 'exact',
                                'name_type' => 'generic',
                                'languageset' => ['english']
                            ]
                        ],
                        'char_filter' => [
                            'ru_en' => [
                                'type'     => 'mapping',
                                'mappings' => $ruToEnMapping,
                            ],
                            'en_ru' => [
                                'type'     => 'mapping',
                                'mappings' => $enToRuMapping,
                            ],
                        ],
                    ],
            ],
            'mappings' => [
                'product' => [
                    '_all'       => ['enabled' => false],
                    'properties' => [
                        'suggest'             => [
                            'type'            => 'completion',
                            'analyzer'        => 'autocomplete',
                            'search_analyzer' => 'standard',
                        ],
                        'brand' => [
                            'properties' => [
                                'active'                          => ['type' => 'boolean'],
                                'dateActiveFrom'                  => ['type' => 'date', 'format' => 'date_optional_time'],
                                'dateActiveTo'                    => ['type' => 'date', 'format' => 'date_optional_time'],
                                'ID'                              => ['type' => 'integer'],
                                'CODE'                            => ['type' => 'keyword'],
                                'XML_ID'                          => ['type' => 'keyword'],
                                'SORT'                            => ['type' => 'integer'],
                                'PREVIEW_TEXT'                    => ['type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                                'PREVIEW_TEXT_TYPE'               => ['type' => 'keyword', 'index' => false],
                                'DETAIL_TEXT'                     => ['type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                                'DETAIL_TEXT_TYPE'                => ['type' => 'keyword', 'index' => false],
                                'DETAIL_PAGE_URL'                 => ['type' => 'text', 'index' => false],
                                'CANONICAL_PAGE_URL'              => ['type' => 'text', 'index' => false],
                                'NAME'                            => ['type' => 'text'],
                                'PROPERTY_POPULAR'                => ['type' => 'boolean'],
                                'PROPERTY_CATALOG_INNER_BANNER'   => ['type' => 'text'],
                                'PROPERTY_TRANSLITS'              => ['type' => 'text'],
                            ],
                        ],
                        'offers' => [
                            'type' => 'nested',
                            'properties' => [
                                'active'                    => ['type' => 'boolean'],
                                'dateActiveFrom'            => ['type' => 'date', 'format' => 'date_optional_time'],
                                'dateActiveTo'              => ['type' => 'date', 'format' => 'date_optional_time'],
                                'ID'                        => ['type' => 'integer'],
                                'CODE'                      => ['type' => 'keyword'],
                                'XML_ID'                    => ['type' => 'keyword'],
                                'SORT'                      => ['type' => 'integer'],
                                'NAME'                      => ['type' => 'text'],
                                'PROPERTY_VOLUME'           => ['type' => 'float'],
                                'PROPERTY_VOLUME_REFERENCE' => ['type' => 'keyword'],
                                'PROPERTY_COLOUR'           => ['type' => 'keyword'],
                                'PROPERTY_CLOTHING_SIZE'    => ['type' => 'keyword'],
                                'PROPERTY_BARCODE'          => ['type' => 'keyword'],
                                'PROPERTY_KIND_OF_PACKING'  => ['type' => 'keyword'],
                                'PROPERTY_REWARD_TYPE'      => ['type' => 'keyword'],
                                'PROPERTY_IS_HIT'           => ['type' => 'boolean'],
                                'PROPERTY_IS_NEW'           => ['type' => 'boolean'],
                                'PROPERTY_IS_SALE'          => ['type' => 'boolean'],
                                'PROPERTY_BONUS_EXCLUDE'    => ['type' => 'boolean'],
                                'PROPERTY_IS_POPULAR'       => ['type' => 'boolean'],
                                'price'                     => ['type' => 'scaled_float', 'scaling_factor' => 100],
                                'currency'                  => ['type' => 'keyword'],
                                'availableStores'           => ['type' => 'keyword'],
                                'prices'                    => [
                                    'type'       => 'nested',
                                    'properties' => [
                                        'ID'               => ['type' => 'keyword'],
                                        'PRODUCT_ID'       => ['type' => 'keyword'],
                                        'CATALOG_GROUP_ID' => ['type' => 'keyword'],
                                        'PRICE'            => ['type' => 'keyword'],
                                        'CURRENCY'         => ['type' => 'keyword'],
                                    ],
                                ],
                                'PROPERTY_REGION_DISCOUNTS' => [
                                    'type'       => 'nested',
                                    'properties' => [
                                        'id'               => ['type' => 'integer'],
                                        'cond_for_action'  => ['type' => 'keyword'],
                                        'price_action'     => ['type' => 'scaled_float', 'scaling_factor' => 100],
                                        'cond_value'       => ['type' => 'scaled_float', 'scaling_factor' => 100],
                                    ],
                                ]
                            ],
                        ],
                        'active'                           => ['type' => 'boolean'],
                        'sectionIdList'                    => ['type' => 'integer'],
                        'sectionName'                      => ['type' => 'text'],
                        'ID'                               => ['type' => 'integer'],
                        'CODE'                             => ['type' => 'keyword'],
                        'XML_ID'                           => ['type' => 'keyword'],
                        'SORT'                             => ['type' => 'integer'],
                        'PREVIEW_TEXT'                     => ['type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                        'PREVIEW_TEXT_TYPE'                => ['type' => 'keyword', 'index' => false],
                        'DETAIL_TEXT'                      => ['type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                        'DETAIL_TEXT_TYPE'                 => ['type' => 'keyword', 'index' => false],
                        'DETAIL_PAGE_URL'                  => ['type' => 'text', 'index' => false],
                        'CANONICAL_PAGE_URL'               => ['type' => 'text', 'index' => false],
                        'dateActiveFrom'                   => ['type' => 'date', 'format' => 'date_optional_time'],
                        'dateActiveTo'                     => ['type' => 'date', 'format' => 'date_optional_time'],
                        'NAME'                             => [
                            'type' => 'text',
                            'fields' => [
                                'synonym' => [
                                    'type' => 'text',
                                    'analyzer' => 'detail-text-analyzator'
                                ]
                            ]
                        ],
                        'PROPERTY_BRAND'                   => ['type' => 'integer'],
                        'PROPERTY_FOR_WHO'                 => ['type' => 'keyword'],
                        'PROPERTY_PET_SIZE'                => ['type' => 'keyword'],
                        'PROPERTY_PET_AGE'                 => ['type' => 'keyword'],
                        'PROPERTY_PET_AGE_ADDITIONAL'      => ['type' => 'keyword'],
                        'PROPERTY_PET_GENDER'              => ['type' => 'keyword'],
                        'PROPERTY_PET_TYPE'                => ['type' => 'keyword'],
                        'PROPERTY_CATEGORY'                => ['type' => 'keyword'],
                        'PROPERTY_PURPOSE'                 => ['type' => 'keyword'],
                        'PROPERTY_LABEL'                   => ['type' => 'keyword'],
                        'PROPERTY_STM'                     => ['type' => 'boolean'],
                        'PROPERTY_TRADE_NAME'              => ['type' => 'keyword'],
                        'PROPERTY_MAKER'                   => ['type' => 'keyword'],
                        'PROPERTY_MANAGER_OF_CATEGORY'     => ['type' => 'keyword'],
                        'PROPERTY_MANUFACTURE_MATERIAL'    => ['type' => 'keyword'],
                        'PROPERTY_SEASON_CLOTHES'          => ['type' => 'keyword'],
                        'PROPERTY_WEIGHT_CAPACITY_PACKING' => ['type' => 'text', 'index' => false],
                        'PROPERTY_LICENSE'                 => ['type' => 'boolean'],
                        'PROPERTY_LOW_TEMPERATURE'         => ['type' => 'boolean'],
                        'PROPERTY_FOOD'                    => ['type' => 'boolean'],
                        'PROPERTY_FLAVOUR'                 => ['type' => 'keyword'],
                        'PROPERTY_FEATURES_OF_INGREDIENTS' => ['type' => 'keyword'],
                        'PROPERTY_PRODUCT_FORM'            => ['type' => 'keyword'],
                        'PROPERTY_TYPE_OF_PARASITE'        => ['type' => 'keyword'],
                        'PROPERTY_GROUP'                   => ['type' => 'text', 'index' => false],
                        'PROPERTY_GROUP_NAME'              => ['type' => 'text', 'index' => false],
                        'PROPERTY_PRODUCED_BY_HOLDER'      => ['type' => 'boolean'],
                        'PROPERTY_SPECIFICATIONS'          => [
                            'properties' => [
                                'TEXT' => ['type' => 'text'],
                                'TYPE' => ['type' => 'keyword', 'index' => false],
                            ],
                        ],
                        'PROPERTY_COUNTRY'                 => ['type' => 'keyword'],
                        'PROPERTY_CONSISTENCE'             => ['type' => 'keyword'],
                        'PROPERTY_FEED_SPECIFICATION'      => ['type' => 'keyword'],
                        'PROPERTY_PHARMA_GROUP'            => ['type' => 'keyword'],
                        'hasActions'                       => ['type' => 'boolean'],
                        'hasImages'                        => ['type' => 'boolean'],
                        'hasStocks'                        => ['type' => 'boolean'],
                        'availableStores'                  => ['type' => 'keyword'],
                        'searchBooster'                    => ['type' => 'text', 'analyzer' => 'detail-text-analyzator']
                    ],
                ],
                'brand' => [
                    '_all'       => ['enabled' => false],
                    'properties' => [
                        'active'                          => ['type' => 'boolean'],
                        'dateActiveFrom'                  => ['type' => 'date', 'format' => 'date_optional_time'],
                        'dateActiveTo'                    => ['type' => 'date', 'format' => 'date_optional_time'],
                        'ID'                              => ['type' => 'integer'],
                        'CODE'                            => ['type' => 'keyword'],
                        'XML_ID'                          => ['type' => 'keyword'],
                        'SORT'                            => ['type' => 'integer'],
                        'PREVIEW_TEXT'                    => ['type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                        'PREVIEW_TEXT_TYPE'               => ['type' => 'keyword', 'index' => false],
                        'DETAIL_TEXT'                     => [ 'type' => 'text', 'analyzer' => 'detail-text-analyzator'],
                        'DETAIL_TEXT_TYPE'                => ['type' => 'keyword', 'index' => false],
                        'DETAIL_PAGE_URL'                 => ['type' => 'text', 'index' => false],
                        'CANONICAL_PAGE_URL'              => ['type' => 'text', 'index' => false],
                        'NAME'                            => ['type' => 'text'],
                        'PROPERTY_POPULAR'                => ['type' => 'boolean'],
                        'PROPERTY_CATALOG_INNER_BANNER'   => ['type' => 'text'],
                        'PROPERTY_TRANSLITS'              => ['type' => 'text'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @param Product $product
     *
     * @throws RuntimeException
     * @return bool
     */
    public function indexProduct(Product $product): bool
    {
        return $this->indexProducts([$product]);
    }

    /**
     * @param array $products
     *
     * @return bool
     */
    public function indexProducts(array $products): bool
    {
        $products = array_filter($products, function ($data) {
            try {
                $result = $this->canIndexProduct($data);
            } catch (IndexExceptionInterface $e) {
                $this->log()->debug(
                    \sprintf(
                        'Skipping product #%s: %s',
                        $data instanceof Product ? $data->getId() : 'N',
                        $e->getMessage()
                    )
                );
                $result = false;
            }

            return $result;
        });

        $documents = array_map(function (Product $product) {
            return $this->factory->makeProductDocument($product);
        }, $products);

        if (!$products) {
            return true;
        }
       
        $responseSet = $this->getCatalogIndex()->addDocuments($documents);

        if (!$responseSet->isOk()) {
            $this->log()->error(
                $responseSet->getError(),
                [
                    'products' => array_map(function (Product $product) {
                        return $product->getId();
                    }, $products),
                ]
            );

            return false;
        }

        return true;
    }

    public function indexBrand(Brand $brand)
    {
        return $this->indexBrands([$brand]);
    }

    /**
     * @param Brand[] $brands
     * @return bool
     */
    public function indexBrands(array $brands)
    {
        $brands = array_filter($brands, function ($data) {
            try {
                $result = $this->canIndexBrand($data);
            } catch (IndexExceptionInterface $e) {
                $this->log()->debug(
                    \sprintf(
                        'Skipping brand #%s: %s',
                        $data instanceof Brand ? $data->getId() : 'N',
                        $e->getMessage()
                    )
                );
                $result = false;
            }

            return $result;
        });

        if (!$brands) {
            return true;
        }

        $documents = array_map(function (Brand $brand) {
            return $this->factory->makeBrandDocument($brand);
        }, $brands);

        $responseSet = $this->getCatalogIndex()->addDocuments($documents);

        if (!$responseSet->isOk()) {
            $this->log()->error(
                $responseSet->getError(),
                [
                    'brands' => array_map(function (Brand $brand) {
                        return $brand->getId();
                    }, $brands),
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * **Синхронно** индексирует товары в Elasticsearch
     *
     * @param bool $flushBaseFilter
     *
     * @param int  $batchSize
     */
    public function indexAll(bool $flushBaseFilter = false, int $batchSize = 500)
    {
        $brandQuery = (new BrandQuery())
            ->withOrder(['ID' => 'DESC']);
        if ($flushBaseFilter) {
            $brandQuery->withFilter([]);
        }
        $allBrands = $brandQuery->exec();
        $this->__indexAll(Brand::class, $allBrands, $batchSize);

        $query = (new ProductQuery())
            ->withOrder(['ID' => 'DESC']);

        if ($flushBaseFilter) {
            $query->withFilter([]);
        }
    
        // $query->withFilter(['ID' => [86097]]);
        $allProducts = $query->exec();
        $this->__indexAll(Product::class, $allProducts, $batchSize);
//        $indexOk = 0;
//        $indexError = 0;
//        $indexTotal = $allProducts->count();
//
//        $this->log()->info(
//            sprintf(
//                'Всего товаров: %d. Идёт индексация товаров... Ждите.',
//                $indexTotal
//            )
//        );
//
//        $allProductsChunked = array_chunk($allProducts->toArray(), $batchSize);
//        unset($allProducts);
//        unset($query);
//
//        $this->log()->debug(sprintf('memory: %s, memory_pick_usage: %s', memory_get_usage(true), memory_get_peak_usage(true)));
//        foreach ($allProductsChunked as $i => $allProductsChunk) {
//            if ($this->indexProducts($allProductsChunk)) {
//                $indexOk += \count($allProductsChunk);
//            } else {
//                $indexError +=\count($allProductsChunk);
//            }
//            unset($allProductsChunked[$i]);
//
//            $this->log()->info(sprintf('Индексировано товаров %d...', $indexOk));
//            $this->log()->debug(sprintf('memory: %s, memory_pick_usage: %s', memory_get_usage(true), memory_get_peak_usage(true)));
//        }
//
//        $this->log()->info(
//            sprintf(
//                "Товаров: %d;\tиндексировано: %d;\tошибок: %d;",
//                $indexTotal,
//                $indexOk,
//                $indexError
//            )
//        );
    }

    /**
     * @param string $type
     * @param CollectionBase $result
     * @param int $batchSize
     */
    private function __indexAll(string $type, CollectionBase $result, int $batchSize = 500)
    {
        $indexOk = 0;
        $indexError = 0;
        $indexTotal = $result->count();
        $entityName = $method = '';

        switch ($type) {
            case Product::class:
                $entityName = 'товаров';
                $method = 'indexProducts';
                break;
            case Brand::class:
                $entityName = 'брендов';
                $method = 'indexBrands';
                break;
        }


        $this->log()->info(
            sprintf(
                'Всего %s: %d. Идёт индексация товаров... Ждите.',
                $entityName,
                $indexTotal
            )
        );

        $allItemsChunked = array_chunk($result->toArray(), $batchSize);
        unset($result);

        $this->log()->debug(sprintf('memory: %s, memory_pick_usage: %s', memory_get_usage(true), memory_get_peak_usage(true)));
        foreach ($allItemsChunked as $i => $allItemChunk) {

            /**
             * @see indexProducts for Product::class
             * @see indexBrands for Brand::class
             */
            
            if (call_user_func([$this, $method], $allItemChunk)) {
                $indexOk += \count($allItemChunk);
            } else {
                $indexError += \count($allItemChunk);
            }

            unset($allItemsChunked[$i]);

            $this->log()->info(sprintf('Индексировано %s %d...', $entityName, $indexOk));
            $this->log()->debug(sprintf('memory: %s, memory_pick_usage: %s', memory_get_usage(true), memory_get_peak_usage(true)));
        }

        $this->log()->info(
            sprintf(
                "%s: %d;\tиндексировано: %d;\tошибок: %d;",
                $entityName,
                $indexTotal,
                $indexOk,
                $indexError
            )
        );
    }

    /**
     * @param int $productId
     *
     * @throws RuntimeException
     * @return bool
     */
    public function deleteProduct(int $productId): bool
    {
        $document = (new Document($productId))->setType(DocumentType::PRODUCT);
        $responseSet = $this->getCatalogIndex()->deleteDocuments([$document]);

        if (!$responseSet->isOk()) {
            $this->log()->error(
                $responseSet->getError(),
                [
                    'productId' => $productId,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param int $brandId
     *
     * @throws RuntimeException
     * @return bool
     */
    public function deleteBrand(int $brandId): bool
    {
        $overallResult = true;

        $productSearch = $this->createProductSearch();

        $productSearch->getQuery()
            ->setFrom(0)
            ->setSize(500)
            ->setSource(false)
            ->setSort(['_doc'])
            ->setParam('query', ['term' => ['brand.ID' => $brandId]]);

        $scroll = $productSearch->scroll();

        foreach ($scroll as $resultSet) {
            $documentsToDelete = [];

            foreach ($resultSet as $result) {
                $documentsToDelete[] = (new Document($result->getId()))->setType(DocumentType::PRODUCT);
            }

            $responseSet = $this->getCatalogIndex()->deleteDocuments($documentsToDelete);

            if (!$responseSet->isOk()) {
                $this->log()->error(
                    $responseSet->getError(),
                    [
                        'brandId' => $brandId,
                    ]
                );

                $overallResult = false;
            }
        }

        return $overallResult;
    }

    /**
     * @throws InvalidException
     * @return Search
     */
    public function createProductSearch(): Search
    {
        /*
         * Обязательно надо создавать явно новый объект Query,
         * иначе даже при создании новых объектов Search они
         * будут разделять общий объект Query и выставление
         * size = 0 для дозапросов аггрегаций будет ломать
         * постраничную навигацию каталога.
         */
        return (new Search($this->client))
            ->setQuery(new Query())
            ->addIndex($this->getCatalogIndex())
            ->addType(DocumentType::PRODUCT);
    }

    public function createBrandSearch(): Search
    {
        /*
         * Обязательно надо создавать явно новый объект Query,
         * иначе даже при создании новых объектов Search они
         * будут разделять общий объект Query и выставление
         * size = 0 для дозапросов аггрегаций будет ломать
         * постраничную навигацию каталога.
         */
        return (new Search($this->client))
            ->setQuery(new Query())
            ->addIndex($this->getCatalogIndex())
            ->addType(DocumentType::BRAND);
    }

    public function createAllTypesSearch(): \Elastica\Multi\Search
    {
        return (new \Elastica\Multi\Search($this->client));
//            ->addSearch($this->createProductSearch())
//            ->addSearch($this->createBrandSearch());
    }

    public function createSuggestSearch(): Search
    {
        return (new Search($this->client));
//            ->addSearch($this->createProductSearch())
//            ->addSearch($this->createBrandSearch());
    }

    /**
     * Удаляет из Elasticsearch отсутствующие в БД товары
     *
     * @param bool $flushBaseFilter
     *
     * @throws \RuntimeException
     * @return bool
     */
    public function cleanup(bool $flushBaseFilter = false): bool
    {
        try {
            $totalDocumentsCount = 0;
            $deletedDocumentsCount = 0;

            $productQuery = (new ProductQuery())
                ->withSelect(['ID']);
            if ($flushBaseFilter) {
                $productQuery->withFilter([]);
            }

            $productSearch = $this->createProductSearch();

            $productSearch->getQuery()
                ->setFrom(0)
                ->setSize(500)
                ->setSource(false)
                ->setSort(['_doc']);

            $scroll = $productSearch->scroll();

            //По всем пачкам из Elastic
            foreach ($scroll as $resultSet) {
                if ($totalDocumentsCount === 0) {
                    $totalDocumentsCount = $resultSet->getTotalHits();
                }

                $productFromElasticIdList = [];
                //По всем документам из пачки
                foreach ($resultSet as $result) {
                    $productFromElasticIdList[] = $result->getId();
                }

                if (\count($productFromElasticIdList) <= 0) {
                    continue;
                }

                $productFromDbIdList = [];
                $dbProductList = $productQuery->withFilterParameter('=ID', $productFromElasticIdList)
                    ->doExec();

                while ($fields = $dbProductList->Fetch()) {
                    $productFromDbIdList[] = (int)$fields['ID'];
                }

                $deleteIdList = array_diff($productFromElasticIdList, $productFromDbIdList);

                if (\count($deleteIdList) <= 0) {
                    continue;
                }

                $deleteIdIndex = array_flip($deleteIdList);

                $deleteDocumentList = [];

                foreach ($resultSet as $result) {
                    if (!isset($deleteIdIndex[$result->getId()])) {
                        continue;
                    }

                    $deleteDocumentList[] = $result->getDocument();
                }

                $deleteDocumentsResponseSet = $this->getCatalogIndex()->deleteDocuments($deleteDocumentList);

                if ($deleteDocumentsResponseSet->isOk()) {
                    $deletedDocumentsCount += $deleteDocumentsResponseSet->count();
                }
            }

            $this->log()->info('Cleanup done.');
            $this->log()->info('Check documents: ' . $totalDocumentsCount);
            $this->log()->info('Removed documents: ' . $deletedDocumentsCount);
        } catch (Exception $exception) {
            $this->log()->error(
                sprintf(
                    '[%s] %s (%s)',
                    \get_class($exception),
                    $exception->getMessage(),
                    $exception->getCode()
                )
            );

            return false;
        }

        return true;
    }

    /**
     * @param string $indexName
     *
     * @return string
     */
    private function getIndexName(string $indexName): string
    {
        $prefix = '';
        if (!Env::isProd()) {
            $prefix = Env::getServerType() . '-';
        }

        return $prefix . $indexName;
    }

    /**
     * @param $product
     *
     * @return bool
     * @throws NotActiveException
     * @throws WrongEntityPassedException
     */
    private function canIndexProduct($product): bool
    {
        if (!$product instanceof Product) {
            throw new WrongEntityPassedException('Invalid entity type');
        }

        if (!$product->isActive()) {
            throw new NotActiveException('Product is not active');
        }

        return true;
    }

    /**
     * @param $brand
     *
     * @return bool
     * @throws NotActiveException
     * @throws WrongEntityPassedException
     */
    private function canIndexBrand($brand): bool
    {
        if (!$brand instanceof Brand) {
            throw new WrongEntityPassedException('Invalid entity type');
        }

        if (!$brand->isActive()) {
            throw new NotActiveException('Brand is not active');
        }

        return true;
    }

    static function getAlias($searchString)
    {
        $arSelect = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'PROPERTY_TRANSLITS'
        ];

        $arFilter = [
            'IBLOCK_ID' => IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::BRANDS),
            'ACTIVE' => 'Y',
            '!PROPERTY_TRANSLITS' => false
        ];

        $brandFound = false;
        $dbItems = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        while ($arItem = $dbItems->Fetch()) {
            if (!empty($arItem['PROPERTY_TRANSLITS_VALUE'])) {
                $arTranslits = explode(',', $arItem['PROPERTY_TRANSLITS_VALUE']);
                foreach ($arTranslits as $translit) {
                    $translit = mb_strtolower(trim($translit));
                    $pos = mb_strpos($searchString, $translit);

                    if ($pos !== false) {
                        /** не начало строки без пробела */
                        if (($pos > 0) && (mb_substr($searchString, $pos-1,1) != ' ')) {
                            continue;
                        }

                        /** не конец строки без пробела */
                        if (($pos + mb_strlen($translit) != mb_strlen($searchString)) && mb_substr($searchString, $pos + mb_strlen($translit), 1) != ' ') {
                            continue;
                        }

                        $searchString = str_replace($translit,
                            mb_strtolower($arItem['NAME']), $searchString);
                        $brandFound = true;
                        break;
                    }
                }
            }
            if ($brandFound) {
                break;
            }
        }
        return $searchString;
    }
}
