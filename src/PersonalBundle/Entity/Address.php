<?php

namespace FourPaws\PersonalBundle\Entity;

use FourPaws\AppBundle\Entity\BaseEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class Address extends BaseEntity
{
    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_NAME")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $name = '';

    /**
     * @var int
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("UF_USER_ID")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $userId;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_CITY")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $city = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_CITY_LOCATION")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $location = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_STREET")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $street = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_HOUSE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $house = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_HOUSING")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $housing = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_ENTRANCE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $entrance = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_FLOOR")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $floor = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_FLAT")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $flat = '';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_INTERCOM_CODE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $intercomCode = '';

    /**
     * @var string
     * @Assert\Length(min="0", max="1024")
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_DETAILS")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $details = '';

    /**
     * @var bool
     * @Serializer\Type("bitrix_bool_d7")
     * @Serializer\SerializedName("UF_MAIN")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $main = false;

    /**
     * @var bool
     * @Serializer\Type("bitrix_bool_d7")
     * @Serializer\SerializedName("UF_MAIN")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $haveShops = false;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * @param string $name
     *
     * @return Address
     */
    public function setName(string $name): Address
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->main ?? false;
    }

    /**
     * @param bool $main
     *
     * @return Address
     */
    public function setMain(bool $main): Address
    {
        $this->main = $main;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId ?? 0;
    }

    /**
     * @param int $userId
     *
     * @return Address
     */
    public function setUserId(int $userId): Address
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullAddress(): string
    {
        $values = [
            'street'       => $this->getStreet(),
            'house'        => $this->getHouse(),
            'housing'      => $this->getHousing(),
            'entrance'     => $this->getEntrance(),
            'floor'        => $this->getFloor(),
            'flat'         => $this->getFlat(),
            'intecomeCode' => $this->getIntercomCode(),
            'city'         => $this->getCity(),
        ];
        $formatted = [
            'house'        => ['begin' => 'д. '],
            'housing'      => ['begin' => 'корпус '],
            'entrance'     => ['begin' => 'подъезд '],
            'floor'        => ['begin' => 'этаж '],
            'flat'         => ['begin' => 'кв. '],
            'intecomeCode' => ['begin' => 'код домофона '],
        ];
        TrimArr($values, true);
        $implodeValues = [];
        foreach ($values as  $key => $value) {
            $implodeValues[$key] = '';
            if($formatted[$key]['begin']){
                $implodeValues[$key] .= $formatted[$key]['begin'];
            }
            $implodeValues[$key] .= $value;
            if($formatted[$key]['end']){
                $implodeValues[$key] .= $formatted[$key]['end'];
            }
        }

        return implode(', ', array_values($implodeValues));
    }

    /**
     * @return string
     */
    public function getHousing(): string
    {
        return $this->housing ?? '';
    }

    /**
     * @param string $housing
     *
     * @return Address
     */
    public function setHousing(string $housing): Address
    {
        $this->housing = $housing;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntrance(): string
    {
        return $this->entrance ?? '';
    }

    /**
     * @param string $entrance
     *
     * @return Address
     */
    public function setEntrance(string $entrance): Address
    {
        $this->entrance = $entrance;

        return $this;
    }

    /**
     * @return string
     */
    public function getFloor(): string
    {
        return $this->floor ?? '';
    }

    /**
     * @param string $floor
     *
     * @return Address
     */
    public function setFloor(string $floor): Address
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * @return string
     */
    public function getFlat(): string
    {
        return $this->flat ?? '';
    }

    /**
     * @param string $flat
     *
     * @return Address
     */
    public function setFlat(string $flat): Address
    {
        $this->flat = $flat;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntercomCode(): string
    {
        return $this->intercomCode ?? '';
    }

    /**
     * @param string $intercomCode
     *
     * @return Address
     */
    public function setIntercomCode(string $intercomCode): Address
    {
        $this->intercomCode = $intercomCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getHouse(): string
    {
        return $this->house ?? '';
    }

    /**
     * @param string $house
     *
     * @return Address
     */
    public function setHouse(string $house): Address
    {
        $this->house = $house;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street ?? '';
    }

    /**
     * @param string $street
     *
     * @return Address
     */
    public function setStreet(string $street): Address
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city ?? '';
    }

    /**
     * @param string $city
     *
     * @return Address
     */
    public function setCity(string $city): Address
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location ?? 0;
    }

    /**
     * @param string $location
     *
     * @return Address
     */
    public function setLocation(string $location): Address
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return string
     */
    public function getDetails(): string
    {
        return $this->details ?? '';
    }

    /**
     * @param string $details
     *
     * @return Address
     */
    public function setDetails(string $details): Address
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $map = [
            ['value' => $this->city, 'prefix' => ''],
            ['value' => $this->street, 'prefix' => ''],
            ['value' => $this->house, 'prefix' => ''],
            ['value' => $this->housing, 'prefix' => 'корпус'],
            ['value' => $this->entrance, 'prefix' => 'подъезд'],
            ['value' => $this->floor, 'prefix' => 'этаж'],
            ['value' => $this->flat, 'prefix' => 'кв.'],
            ['value' => $this->intercomCode, 'prefix' => 'код домофона'],
        ];

        $result = \array_filter(\array_map(function ($item) {
            $result = '';
            if ($item['value']) {
                $result = $item['prefix'] ? $item['prefix'] . ' ' . $item['value'] : $item['value'];
            }
            return $result;
        }, $map));

        return implode(', ', $result);
    }

    /**
     * @param bool $have
     *
     * @return Address
     */
    public function setHaveShop(bool $have): Address
    {
        $this->haveShops = $have;
        return $this;
    }

    /**
     * @return bool
     */
    public function getHaveShop(): bool
    {
        return $this->haveShops;
    }
}
