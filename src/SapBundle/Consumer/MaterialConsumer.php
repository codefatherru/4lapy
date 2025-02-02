<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SapBundle\Consumer;

use Bitrix\Main\Application;
use CIBlock;
use FourPaws\BitrixOrm\Model\CatalogProduct;
use FourPaws\Catalog\Model\Brand;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Model\Product;
use FourPaws\CatalogBundle\EventController\Event;
use FourPaws\CatalogBundle\Service\BrandService;
use FourPaws\Migrator\Entity\AddResult;
use FourPaws\SapBundle\Dto\In\Offers\Material;
use FourPaws\SapBundle\Exception\CantCreateReferenceItem;
use FourPaws\SapBundle\Exception\LoggedException;
use FourPaws\SapBundle\Exception\LogicException;
use FourPaws\SapBundle\Exception\NotFoundBasicUomException;
use FourPaws\SapBundle\Exception\NotFoundDataManagerException;
use FourPaws\SapBundle\Exception\NotFoundReferenceRepositoryException;
use FourPaws\SapBundle\Exception\RuntimeException;
use FourPaws\SapBundle\Repository\BrandRepository;
use FourPaws\SapBundle\Service\Materials\CatalogProductService;
use FourPaws\SapBundle\Service\Materials\OfferService;
use FourPaws\SapBundle\Service\Materials\ProductService;
use FourPaws\SapBundle\Service\ReferenceService;
use Psr\Log\LogLevel;
use RuntimeException as BaseRuntimeException;

/**
 * Class MaterialConsumer
 *
 * @package FourPaws\SapBundle\Consumer
 */
class MaterialConsumer extends SapConsumerBase
{
    /**
     * @var ReferenceService
     */
    private $referenceService;
    /**
     * @var OfferService
     */
    private $offerService;
    /**
     * @var ProductService
     */
    private $productService;
    /**
     * @var CatalogProductService
     */
    private $catalogProductService;
    /**
     * @var \Bitrix\Main\DB\Connection
     */
    private $connection;
    /**
     * @var BrandRepository
     */
    private $brandRepository;

    /**
     * MaterialConsumer constructor..
     * @param ReferenceService      $referenceService
     * @param OfferService          $offerService
     * @param ProductService        $productService
     * @param CatalogProductService $catalogProductService
     * @param BrandRepository       $brandRepository
     */
    public function __construct(
        ReferenceService $referenceService,
        OfferService $offerService,
        ProductService $productService,
        CatalogProductService $catalogProductService,
        BrandRepository $brandRepository
    )
    {
        $this->connection = Application::getConnection();
        $this->referenceService = $referenceService;
        $this->offerService = $offerService;
        $this->productService = $productService;
        $this->catalogProductService = $catalogProductService;
        $this->brandRepository = $brandRepository;
    }

    /**
     * @param Material $material
     *
     * @throws \Bitrix\Main\Db\SqlQueryException
     * @return bool
     */
    public function consume($material): bool
    {
        if (!$this->support($material)) {
            return false;
        }

        $this->connection->startTransaction();

        try {
            if ($material->isNotUploadToIm()) {
                $result = $this->offerService->deactivate($material->getOfferXmlId());
                $this->connection->commitTransaction();
                return $result;
            }

            /**
             * Костыль! Уровень изоляции кривой.
             */
            Event::lockEvents();

            $this->connection->commitTransaction();

            $this->connection->startTransaction();
            $brand = $this->getBrand($material);
            $product = $this->getProduct($material, $brand);
            $offer = $this->getOffer($material, $product);
            $this->getCatalogProduct($material, $offer);
            $this->connection->commitTransaction();

            //апдейтим НДС после апдейта/создания товара/оффера, иначе он перетирается на null
            $this->connection->query('UPDATE b_catalog_product SET `VAT_ID` = ' . $offer->getCatalogVatId() . ' WHERE `ID` = ' . $offer->getId());

            /**
             * Костыль! Уровень изоляции кривой.
             */
            Event::unlockEvents();
            $this->productService->addProductsToClearCache([$offer->getId(), $product->getId()]);

            return true;
        } catch (LoggedException $exception) {
        } catch (\Exception $exception) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->log()->log(
                LogLevel::CRITICAL,
                sprintf('Undefined exception: %s [%s]', $exception->getMessage(), $exception->getCode()),
                $exception->getTrace()
            );
        } finally {
            /**
             * Костыль! Уровень изоляции кривой.
             */
            Event::unlockEvents();
        }
        $this->connection->rollbackTransaction();

        return false;
    }

    /**
     * @param $data
     *
     * @return bool
     */
    public function support($data): bool
    {
        return \is_object($data) && $data instanceof Material;
    }

    /**
     * @param Material $material
     *
     * @throws BaseRuntimeException
     * @throws LoggedException
     * @return Brand
     */
    protected function getBrand(Material $material): Brand
    {
        /**
         * @todo internal storage for import
         */
        if (!$material->getBrandCode()) {
            $material->setBrandCode(BrandService::DEFAULT_BRAND_CODE);
        }

        $brand = $this->brandRepository->findByXmlId($material->getBrandCode());
        if (!$brand) {
            $brand = (new Brand())
                ->withXmlId($material->getBrandCode())
                ->withName($material->getBrandName());
            $result = $this->brandRepository->create($brand);

            if (!$result->isSuccess()) {
                $this->log()->log(
                    LogLevel::ERROR,
                    implode(', ', $result->getErrorMessages()),
                    ['NAME' => $material->getBrandName(), 'XML_ID' => $material->getBrandCode()]
                );
                throw new LoggedException('Ошибка создания бренда');
            }

            $this->log()->log(
                LogLevel::DEBUG,
                sprintf('Бренд %s [%s] создан', $brand->getName(), $brand->getId())
            );
        }
        return $brand;
    }

    /**
     * @param Material $material
     *
     * @param Brand    $brand
     *
     * @throws NotFoundReferenceRepositoryException
     * @throws NotFoundDataManagerException
     * @throws LogicException
     * @throws CantCreateReferenceItem
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws BaseRuntimeException
     * @throws LoggedException
     * @return Product
     */
    protected function getProduct(Material $material, Brand $brand): Product
    {
        $product = $this->productService->processMaterial($material);
        $product->withBrandId($brand->getId());

        CIBlock::disableClearTagCache();
        if ($product->getId()) {
            $result = $this->productService->update($product);
        } else {
            $result = $this->productService->create($product);
        }
        CIBlock::enableClearTagCache();

        if ($result->isSuccess()) {
            $this->log()->log(LogLevel::DEBUG, sprintf(
                'Элемент инфоблока [Продукт] %s [%s] %s',
                $product->getName(),
                $product->getId(),
                $result instanceof AddResult ? 'Создан' : 'Обновлен'
            ));
            return $product;
        }
        $message = sprintf(
            'Ошибка %s элемент инфоблока [Продукт] %s [%s]',
            $result instanceof AddResult ? 'Создания' : 'Обновления',
            $product->getName(),
            $product->getCode()
        );

        $this->log()->log(LogLevel::CRITICAL, $message, $result->getErrorMessages());

        throw new LoggedException($message);
    }

    /**
     * @param Material $material
     * @param Product  $product
     *
     * @throws BaseRuntimeException
     * @throws NotFoundDataManagerException
     * @throws NotFoundBasicUomException
     * @throws CantCreateReferenceItem
     * @throws NotFoundReferenceRepositoryException
     * @throws LogicException
     * @throws RuntimeException
     * @throws LoggedException
     *
     * @return Offer
     */
    protected function getOffer(Material $material, Product $product): Offer
    {
        $offer = $this->offerService->processMaterial($material);

        /**
         * если оффер перемещается к другому товару, то мы должны переиндексировать его предыдущий товар
         */
        $previousProductId = null;
        if ($offer->getCml2Link() && $offer->getCml2Link() !== $product->getId()) {
            $previousProductId = $offer->getCml2Link();
        }
        $offer->withCml2Link($product->getId());

        CIBlock::disableClearTagCache();
        if ($offer->getId()) {
            $result = $this->offerService->update($offer);
        } else {
            $result = $this->offerService->create($offer);
        }

        if ((null !== $previousProductId) &&
            $product = $this->productService->findById($previousProductId)
        ) {
            $this->productService->update($product);
        }
        CIBlock::enableClearTagCache();

        if ($result->isSuccess()) {
            $this->log()->log(LogLevel::DEBUG, sprintf(
                'Торговое предложение %s [%s] %s',
                $offer->getName(),
                $offer->getId(),
                $result instanceof AddResult ? 'Создано' : 'Обновлено'
            ));
            return $offer;
        }
        $message = sprintf(
            'Ошибка %s торгового предложения %s [%s]',
            $result instanceof AddResult ? 'Создания' : 'Обновления',
            $offer->getName(),
            $offer->getCode()
        );

        $this->log()->log(LogLevel::CRITICAL, $message, $result->getErrorMessages());
        throw new LoggedException($message);
    }

    /**
     * @param Material $material
     * @param Offer    $offer
     *
     * @throws NotFoundBasicUomException
     * @throws BaseRuntimeException
     * @throws LoggedException
     * @return CatalogProduct
     */
    protected function getCatalogProduct(Material $material, Offer $offer): CatalogProduct
    {
        $catalogProduct = $this->catalogProductService->processMaterial($material);
        $catalogProduct->setId($offer->getId());
        $result = $this->catalogProductService->updateOrCreate($catalogProduct);

        if ($result) {
            $this->log()->log(LogLevel::DEBUG, sprintf(
                'Продуктовое предложение %s создано или обновлено',
                $catalogProduct->getId()
            ));
            return $catalogProduct;
        }

        $message = sprintf(
            'Ошибка создания или обновления продуктового предложения %s',
            $catalogProduct->getId()
        );

        $this->log()->log(LogLevel::CRITICAL, $message);

        throw new LoggedException($message);
    }
}
