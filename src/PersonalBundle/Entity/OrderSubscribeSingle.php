<?php
/**
 * Created by PhpStorm.
 * User: mmasterkov
 * Date: 25.03.2019
 * Time: 16:18
 */

namespace FourPaws\PersonalBundle\Entity;


use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Basket;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Entity\BaseEntity;
use FourPaws\AppBundle\Entity\UserFieldEnumValue;
use FourPaws\AppBundle\Service\UserFieldEnumService;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\AppBundle\Traits\UserFieldEnumTrait;
use FourPaws\Helpers\DateHelper;
use FourPaws\LocationBundle\LocationService;
use FourPaws\PersonalBundle\Exception\NotFoundException;
use FourPaws\PersonalBundle\Exception\OrderSubscribeException;
use FourPaws\StoreBundle\Exception\NotFoundException as NotFoundStoreException;
use FourPaws\PersonalBundle\Service\AddressService;
use FourPaws\PersonalBundle\Service\OrderSubscribeHistoryService;
use FourPaws\PersonalBundle\Service\OrderSubscribeService;
use FourPaws\StoreBundle\Service\StoreService;
use FourPaws\UserBundle\Entity\User;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use FourPaws\App\Application;


/**
 * Class OrderSubscribeSingle
 *
 * @package FourPaws\PersonalBundle\Repository
 */
class OrderSubscribeSingle extends BaseEntity
{
    use UserFieldEnumTrait;

    /**
     * @var int
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("UF_SUBSCRIBE_ID")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $subscribeId;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_DATA")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $data;

    /**
     * @var array
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("UF_ITEMS")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $items;

    /**
     * @var array
     * @Serializer\Type("array<string>")
     * @Serializer\SerializedName("UF_QUANTITY")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $quantity;

    /**
     * @var DateTime
     * @Serializer\Type("bitrix_date_time_ex")
     * @Serializer\SerializedName("UF_DATE_CREATE")
     * @Serializer\Groups(groups={"create","read"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $dateCreate;

    /**
     * @return int
     */
    public function getSubscribeId(): int
    {
        return $this->subscribeId;
    }

    /**
     * @param int $subscribeId
     * @return OrderSubscribeSingle
     */
    public function setSubscribeId(int $subscribeId): OrderSubscribeSingle
    {
        $this->subscribeId = $subscribeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     * @return OrderSubscribeSingle
     */
    public function setData(string $data): OrderSubscribeSingle
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $offers
     * @return OrderSubscribeSingle
     */
    public function setItems(array $items): OrderSubscribeSingle
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return array
     */
    public function getQuantity(): array
    {
        return $this->quantity;
    }

    /**
     * @param array $quantity
     * @return OrderSubscribeSingle
     */
    public function setQuantity(array $quantity): OrderSubscribeSingle
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreate(): DateTime
    {
        return $this->dateCreate;
    }

    /**
     * @param DateTime $dateCreate
     * @return OrderSubscribeSingle
     */
    public function setDateCreate(DateTime $dateCreate): OrderSubscribeSingle
    {
        $this->dateCreate = $dateCreate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubscribe(): array
    {
        return \unserialize($this->getData());
    }
}