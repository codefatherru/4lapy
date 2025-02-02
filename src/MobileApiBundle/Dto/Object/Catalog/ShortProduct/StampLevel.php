<?php

namespace FourPaws\MobileApiBundle\Dto\Object\Catalog\ShortProduct;

use FourPaws\SaleBundle\Helper\PriceHelper;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class StampLevel
 *
 * @package FourPaws\MobileApiBundle\Dto\Object\Catalog\ShortProduct
 *
 * ОбъектКаталога.ПолныйТовар.УровеньСкидкиМарками
 */
class StampLevel
{
    /**
     * Цена на этом уровне
     * @Serializer\Type("float")
     * @Serializer\SerializedName("price")
     * @var float
     */
    protected $price;

    /**
     * Количество марок, необходимых для обмена
     * @Serializer\Type("int")
     * @Serializer\SerializedName("stamps")
     * @var int
     */
    protected $stamps;

    /**
     * Размер скидки
     * @Serializer\Groups(groups={"read"})
     * @Serializer\Type("int")
     * @Serializer\SerializedName("discount")
     * @var int
     */
    private $discountValue;

    /**
     * Тип скидки:
     * P - в процентах
     * V - в рублях
     * @Serializer\Groups(groups={"read"})
     * @Serializer\Type("string")
     * @Serializer\SerializedName("discountType")
     * @var string
     */
    private $discountType;

    /**
     * Будет ли применен этот уровень
     * @Serializer\Type("bool")
     * @Serializer\SerializedName("isMaxLevel")
     * @var bool
     */
    protected $isMaxLevel = false;

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return StampLevel
     */
    public function setPrice(float $price): StampLevel
    {
        $this->price = PriceHelper::roundPrice($price);
        return $this;
    }

    /**
     * @return int
     */
    public function getStamps(): int
    {
        return $this->stamps;
    }

    /**
     * @param int $stamps
     * @return StampLevel
     */
    public function setStamps(int $stamps): StampLevel
    {
        $this->stamps = $stamps;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMaxLevel(): bool
    {
        return $this->isMaxLevel;
    }

    /**
     * @param bool $isMaxLevel
     * @return StampLevel
     */
    public function setIsMaxLevel(bool $isMaxLevel): StampLevel
    {
        $this->isMaxLevel = $isMaxLevel;
        return $this;
    }

    /**
     * @return int
     */
    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    /**
     * @param int $discountValue
     * @return StampLevel
     */
    public function setDiscountValue(int $discountValue): StampLevel
    {
        $this->discountValue = $discountValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    /**
     * @param string $discountType
     * @return StampLevel
     */
    public function setDiscountType(string $discountType): StampLevel
    {
        $this->discountType = $discountType;
        return $this;
    }
}
