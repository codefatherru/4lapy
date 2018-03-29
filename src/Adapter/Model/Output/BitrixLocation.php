<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 29.03.18
 * Time: 9:20
 */

namespace FourPaws\Adapter\Model\Output;

use JMS\Serializer\Annotation as Serializer;

class BitrixLocation
{
    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\SerializedName("ID")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $id;
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("CODE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $code;
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("NAME")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $name;
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("REGION")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $region;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

}