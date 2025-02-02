<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\MobileApiBundle\Dto\Object;

use Doctrine\Common\Collections\Collection;
use FourPaws\BitrixOrm\Model\Interfaces\ImageInterface;
use FourPaws\MobileApiBundle\Dto\Object\Catalog\ShortProduct;
use JMS\Serializer\Annotation as Serializer;

class Info
{
    /**
     * Идентификатор
     *
     * @Serializer\Type("string")
     * @Serializer\SerializedName("id")
     * @var string
     */
    protected $id = '';

    /**
     * Тип страницы
     *
     * @Serializer\Type("string")
     * @Serializer\SerializedName("type")
     * @var string
     */
    protected $type = '';

    /**
     * Дата новости
     *
     * @Serializer\Type("DateTime<'d.m.Y'>")
     * @Serializer\SerializedName("date")
     * @var null|\DateTime
     */
    protected $dateFrom;

    /**
     * Дата окончания периода
     *
     * @Serializer\Type("DateTime<'d.m.Y'>")
     * @Serializer\SerializedName("end_date")
     * @var null|\DateTime
     */
    protected $dateTo;

    /**
     * Название
     *
     * @Serializer\Type("string")
     * @Serializer\SerializedName("title")
     * @var string
     */
    protected $name = '';

    /**
     * Анонс - краткое описание
     *
     * @Serializer\Type("api_details_text")
     * @Serializer\SerializedName("details")
     * @var string
     */
    protected $previewText = '';

    /**
     * HTML-текст
     *
     * @Serializer\Type("api_html_text")
     * @Serializer\SerializedName("html")
     * @var string
     */
    protected $detailText = '';

    /**
     * Ссылка на страницу
     *
     * @Serializer\Type("string")
     * @Serializer\SerializedName("web_url")
     * @var string
     */
    protected $url = '';

    /**
     * Ссылка на главную картинку страницы
     *
     * @Serializer\Type("api_image_src")
     * @Serializer\SerializedName("icon")
     *
     * @var null|ImageInterface
     */
    protected $icon;

    /**
     * Ссылка на detail картинку страницы
     *
     * @Serializer\Type("api_image_src")
     * @Serializer\SerializedName("icon_detail")
     *
     * @var null|ImageInterface
     */
    protected $iconDetail;

    /**
     *
     * @Serializer\Type("api_image_collection_src")
     * @Serializer\SerializedName("images")
     *
     * @var Collection|ImageInterface[]
     */
    public $images;

    /**
     * @var array
     */
    protected $participants = [];
    /**
     * Признак открытого/закрытого голосования
     *
     * @Serializer\Type("bool")
     * @Serializer\SerializedName("vote_enabled")
     *
     * @var bool
     */
    protected $voteEnabled = false;

    /**
     * Список объектов ОбъектКаталога.КраткийТовар
     *
     * @Serializer\Type("ArrayCollection<FourPaws\MobileApiBundle\Dto\Object\Catalog\FullProduct>")
     * @Serializer\SerializedName("goods")
     *
     * @var Collection|FullProduct[]
     */
    protected $goods;

    /**
     * Список вложенных (дочерних) объектов Инфо
     *
     * @Serializer\Type("ArrayCollection<FourPaws\MobileApiBundle\Dto\Object\Info>")
     * @Serializer\SerializedName("subitems")
     *
     * @var Collection|Info[]
     */
    protected $subItems;

    /** @var bool $isNeedLoad */
    protected $isNeedLoad = false;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Info
     */
    public function setId(string $id): Info
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Info
     */
    public function setType(string $type): Info
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return null|\DateTime
     */
    public function getDateFrom(): \DateTime
    {
        return $this->dateFrom;
    }

    /**
     * @param null|\DateTime $dateFrom
     *
     * @return Info
     */
    public function setDateFrom(\DateTime $dateFrom = null): Info
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    /**
     * @return null|\DateTime
     */
    public function getDateTo(): \DateTime
    {
        return $this->dateTo;
    }

    /**
     * @param null|\DateTime $dateTo
     *
     * @return Info
     */
    public function setDateTo($dateTo): Info
    {
        $this->dateTo = $dateTo;
        return $this;
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
     *
     * @return Info
     */
    public function setName(string $name): Info
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewText(): string
    {
        return $this->previewText;
    }

    /**
     * @param string $previewText
     *
     * @return Info
     */
    public function setPreviewText(string $previewText): Info
    {
        $this->previewText = $previewText;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetailText(): string
    {
        return $this->detailText;
    }

    /**
     * @param string $detailText
     *
     * @return Info
     */
    public function setDetailText(string $detailText): Info
    {
        $this->detailText = $detailText;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Info
     */
    public function setUrl(string $url): Info
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return null|ImageInterface
     */
    public function getIcon(): ImageInterface
    {
        return $this->icon;
    }

    /**
     * @param null|ImageInterface $icon
     *
     * @return Info
     */
    public function setIcon(ImageInterface $icon = null): Info
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return ImageInterface|null
     */
    public function getIconDetail(): ?ImageInterface
    {
        return $this->iconDetail;
    }

    /**
     * @param ImageInterface|null $iconDetail
     */
    public function setIconDetail(?ImageInterface $iconDetail): void
    {
        $this->iconDetail = $iconDetail;
    }

    /**
     * @return Collection|ImageInterface[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Collection|ImageInterface[] $images
     *
     * @return Info
     */
    public function setImages($images)
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVoteEnabled(): bool
    {
        return $this->voteEnabled;
    }

    /**
     * @param bool $voteEnabled
     *
     * @return Info
     */
    public function setVoteEnabled(bool $voteEnabled): Info
    {
        $this->voteEnabled = $voteEnabled;
        return $this;
    }

    /**
     * @return Collection|FullProduct[]
     */
    public function getGoods()
    {
        return $this->goods;
    }

    /**
     * @param Collection|FullProduct[] $goods
     *
     * @return Info
     */
    public function setGoods($goods)
    {
        $this->goods = $goods;
        return $this;
    }

    /**
     * @return Collection|Info[]
     */
    public function getSubItems()
    {
        return $this->subItems;
    }

    /**
     * @param Collection|Info[] $subItems
     *
     * @return Info
     */
    public function setSubItems($subItems)
    {
        $this->subItems = $subItems;
        return $this;
    }

    /**
     * @param bool $need
     * @return Info
     */
    public function setIsNeedLoad($need)
    {
        $this->isNeedLoad = $need;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsNeedLoad()
    {
        return $this->isNeedLoad;
    }
}
