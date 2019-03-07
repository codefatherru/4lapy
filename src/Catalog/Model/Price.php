<?php
/**
 * Created by PhpStorm.
 * User: mmasterkov
 * Date: 07.03.2019
 * Time: 18:09
 */

namespace FourPaws\Catalog\Model;


use FourPaws\BitrixOrm\Model\BitrixArrayItemBase;
use JMS\Serializer\Annotation as Serializer;

class Price extends BitrixArrayItemBase
{
    /**
     * @var integer
     * @Serializer\SkipWhenEmpty()
     * @Serializer\Type("int")
     * @Serializer\SerializedName("ID")
     */
    protected $ID;

    /**
     * @var integer
     * @Serializer\SkipWhenEmpty()
     * @Serializer\Type("int")
     * @Serializer\SerializedName("PRODUCT_ID")
     */
    protected $PRODUCT_ID;

    /**
     * @var integer
     * @Serializer\SkipWhenEmpty()
     * @Serializer\Type("int")
     * @Serializer\SerializedName("CATALOG_GROUP_ID")
     */
    protected $CATALOG_GROUP_ID;

    /**
     * @var float
     * @Serializer\SkipWhenEmpty()
     * @Serializer\Type("float")
     * @Serializer\SerializedName("PRICE")
     */
    protected $PRICE;

    /**
     * @var string
     * @Serializer\SkipWhenEmpty()
     * @Serializer\Type("string")
     * @Serializer\SerializedName("CURRENCY")
     */
    protected $CURRENCY;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->ID;
    }

    /**
     * @param int $ID
     * @return Price
     */
    public function setId(int $ID): Price
    {
        $this->ID = $ID;
        return $this;
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return $this->PRODUCT_ID;
    }

    /**
     * @param int $PRODUCT_ID
     * @return Price
     */
    public function setProductId(int $PRODUCT_ID): Price
    {
        $this->PRODUCT_ID = $PRODUCT_ID;
        return $this;
    }

    /**
     * @return int
     */
    public function getCatalogGroupId(): int
    {
        return $this->CATALOG_GROUP_ID;
    }

    /**
     * @param int $CATALOG_GROUP_ID
     * @return Price
     */
    public function setCatalogGroupId(int $CATALOG_GROUP_ID): Price
    {
        $this->CATALOG_GROUP_ID = $CATALOG_GROUP_ID;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->PRICE;
    }

    /**
     * @param float $PRICE
     * @return Price
     */
    public function setPrice(float $PRICE): Price
    {
        $this->PRICE = $PRICE;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrency(): string
    {
        return $this->CURRENCY;
    }

    /**
     * @param int $CURRENCY
     * @return Price
     */
    public function setCurrency(string $CURRENCY): Price
    {
        $this->CURRENCY = $CURRENCY;
        return $this;
    }



}