<?php

namespace FourPaws\PersonalBundle\Entity;


use FourPaws\AppBundle\Entity\BaseEntity;
use JMS\Serializer\Annotation as Serializer;

class OrderProp extends BaseEntity
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("NAME")
     * @Serializer\Groups(groups={"read","update", "create"})
     */
    protected $name = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("CODE")
     * @Serializer\Groups(groups={"read","update", "create"})
     */
    protected $code = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("VALUE")
     * @Serializer\Groups(groups={"read","update", "create"})
     */
    protected $value = '';

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code ?? '';
    }

    /**
     * @param string $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value ?? '';
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }
}