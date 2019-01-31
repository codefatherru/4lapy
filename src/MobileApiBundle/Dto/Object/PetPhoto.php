<?php

namespace FourPaws\MobileApiBundle\Dto\Object;

use FourPaws\Decorators\FullHrefDecorator;
use JMS\Serializer\Annotation as Serializer;

class PetPhoto
{
    /**
     * @Serializer\Type("int")
     * @Serializer\SerializedName("id")
     * @var int
     */
    protected $id;

    /**
     * @Serializer\Type("string")
     * @Serializer\SerializedName("src")
     * @var string
     */
    protected $src;

    /**
     * @Serializer\Type("string")
     * @Serializer\SerializedName("preview")
     * @var string
     */
    protected $preview;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return PetPhoto
     */
    public function setId(int $id): PetPhoto
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSrc(): string
    {
        return $this->src;
    }

    /**
     * @param string $src
     * @return PetPhoto
     * @throws \Bitrix\Main\SystemException
     */
    public function setSrc(string $src): PetPhoto
    {
        $this->src = $src ? (new FullHrefDecorator($src))->getFullPublicPath() : '';
        return $this;
    }

    /**
     * @return int
     */
    public function getPreview(): int
    {
        return $this->preview;
    }

    /**
     * @param string $preview
     * @return PetPhoto
     * @throws \Bitrix\Main\SystemException
     */
    public function setPreview(string $preview): PetPhoto
    {
        $this->preview = $preview ? (new FullHrefDecorator($preview))->getFullPublicPath() : '';
        return $this;
    }
}