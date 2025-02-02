<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\PersonalBundle\Entity;

use Adv\Bitrixtools\Tools\HLBlock\HLBlockFactory;
use Bitrix\Main\Type\Date;
use FourPaws\App\Application;
use FourPaws\AppBundle\Entity\BaseEntity;
use FourPaws\BitrixOrm\Model\CropImageDecorator;
use FourPaws\BitrixOrm\Model\Exceptions\FileNotFoundException;
use FourPaws\Helpers\WordHelper;
use FourPaws\PersonalBundle\Service\PetService;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;

class Pet extends BaseEntity
{
    const PET_TYPE = 'ForWho';
    const PET_BREED = 'PetBreed';

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_NAME")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $name;

    /**
     * @var int
     * @Serializer\Type("integer")
     * @Serializer\SerializedName("UF_USER_ID")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Assert\NotBlank(groups={"create"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $userId;

    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\SerializedName("UF_PHOTO")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $photo;

    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\SerializedName("UF_TYPE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $type;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("UF_BREED")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $breed;

    /**
     * @var string
     * @Serializer\Type("int")
     * @Serializer\SerializedName("UF_BREED_ID")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $breedId;

    /**
     * @var Date|string
     * @Serializer\Type("bitrix_date_ex")
     * @Serializer\SerializedName("UF_BIRTHDAY")
     * @Serializer\Groups(groups={"create","read","update"})
     */
    protected $birthday;

    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\SerializedName("UF_GENDER")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $gender;

    /**
     * @var int
     * @Serializer\Type("int")
     * @Serializer\SerializedName("UF_SIZE")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $size = 0;

    /**
     * @var float
     * @Serializer\Type("float")
     * @Serializer\SerializedName("UF_CHEST")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $chest = 0.0;

    /**
     * @var float
     * @Serializer\Type("float")
     * @Serializer\SerializedName("UF_BACK")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $back = 0.0;

    /**
     * @var float
     * @Serializer\Type("float")
     * @Serializer\SerializedName("UF_NECK")
     * @Serializer\Groups(groups={"create","read","update"})
     * @Serializer\SkipWhenEmpty()
     */
    protected $neck = 0.0;

    protected $stringGender = '';

    protected $codeGender = '';

    protected $stringType   = '';

    protected $codeType     = '';

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name ?? '';
    }

    /**
     * @param string $name
     *
     * @return Pet
     */
    public function setName(?string $name = '') : Pet
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId() : int
    {
        return $this->userId ?? 0;
    }

    /**
     * @param int $userId
     *
     * @return Pet
     */
    public function setUserId(int $userId) : Pet
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string
     */
    public function getImgPath() : string
    {
        $photo = $this->getPhoto();
        if ($photo > 0) {
            return \CFile::GetPath($photo);
        }

        return '';
    }

    /**
     * @return int
     */
    public function getPhoto() : int
    {
        return $this->photo ?? 0;
    }

    /**
     * @param int $photo
     *
     * @return Pet
     */
    public function setPhoto(int $photo) : Pet
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return string
     */
    public function getResizeImgPath() : string
    {
        $photo = $this->getPhoto();
        if ($photo > 0) {
            try {
                return CropImageDecorator::createFromPrimary($photo)->setCropWidth(110)->setCropHeight(110)->getSrc();
            } catch (FileNotFoundException $e) {
            }
        }

        return (new CropImageDecorator(['src'=>'/static/build/images/inhtml/no_image.png']))->setCropWidth(110)->setCropHeight(110)->getSrc();
    }

    /**
     * @return string
     */
    public function getResizePopupImgPath() : string
    {
        $photo = $this->getPhoto();
        if ($photo > 0) {
            try {
                return CropImageDecorator::createFromPrimary($photo)->setCropWidth(180)->setCropHeight(180)->getSrc();
            } catch (FileNotFoundException $e) {
            }
        }

        return (new CropImageDecorator(['src'=>'/static/build/images/inhtml/no_image.png']))->setCropWidth(180)->setCropHeight(180)->getSrc();
    }

    /**
     * @return string
     */
    public function getStringType() : string
    {
        if (empty($this->stringType) && $this->getType() > 0) {
            try {
                $this->setStringType($this->getType());
            } catch (\Exception $e) {
            }
        }

        return $this->stringType;
    }

    /**
     * @param int $type
     *
     * @throws \Exception
     */
    protected function setStringType(int $type)
    {
        $item             = HLBlockFactory::createTableObject(static::PET_TYPE)::query()->setFilter(
            [
                'ID'            => $type,
                'UF_USE_BY_PET' => 1,
            ]
        )->setSelect(
            [
                'UF_NAME',
                'UF_CODE',
            ]
        )->exec()->fetch();
        $this->stringType = $item['UF_NAME'];
        $this->codeType   = $item['UF_CODE'];
    }

    /**
     * @return int
     */
    public function getType() : int
    {
        return $this->type ?? 0;
    }

    /**
     * @param int $type
     *
     * @throws \Exception
     * @return Pet
     */
    public function setType(int $type) : Pet
    {
        $this->type = $type;
        if ($type > 0) {
            $this->setStringType($type);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeType() : string
    {
        if (empty($this->codeType) && $this->getType() > 0) {
            try {
                $this->setStringType($this->getType());
            } catch (\Exception $e) {
            }
        }

        return $this->codeType ?? '';
    }

    /**
     * @return string
     */
    public function getBreed() : string
    {
        return $this->breed ?? '';
    }

    /**
     * @param string $breed
     *
     * @return Pet
     */
    public function setBreed(string $breed) : Pet
    {
        $this->breed = $breed;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBreedId() : ?int
    {
        return $this->breedId ?: null;
    }

    /**
     * @param int|null $breedId
     *
     * @return Pet
     */
    public function setBreedId(?int $breedId) : Pet
    {
        $this->breedId = $breedId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAgeString(): string
    {
        [$years, $months, $days] = $this->getAge();

        $return = '';
        if($years > 0) {
            $return .= $years . ' ' . WordHelper::declension(
                    $years,
                    [
                        'год',
                        'года',
                        'лет',
                    ]
                );
        }
        if ($months > 0) {
            $return .= ' ' . $months . ' ' . WordHelper::declension(
                    $months,
                    [
                        'месяц',
                        'месяца',
                        'месяцев',
                    ]
                );
        }
        if($days > 0 && $years === 0 && $months === 0){
            $return = 'меньше месяца';
        }

        return $return;
    }

    /**
     * @return array
     */
    public function getAge() : array
    {
        $birthday = $this->getBirthday();
        if (!($birthday instanceof Date)) {
            return [
                0,
                0,
            ];
        }
        $date     = new \DateTime($this->getBirthday()->format('Y-m-d'));
        $interval = $date->diff(new \DateTime(date('Y-m-d')));

        return [
            (int)$interval->format('%Y'),
            (int)$interval->format('%m'),
            (int)$interval->format('%d'),
        ];
    }

    /**
     * @return Date|string
     */
    public function getBirthday() : ?Date
    {
        if (!($this->birthday instanceof Date)) {
            return null;
        }

        return $this->birthday;
    }

    /**
     * @param null|string|Date $birthday
     *
     * @return Pet
     */
    public function setBirthday($birthday) : Pet
    {
        if ($birthday instanceof Date) {
            $this->birthday = $birthday;
        } elseif (\strlen($birthday) > 0) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->birthday = new Date($birthday, 'd.m.Y');
        } else {
            $this->birthday = '';
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getStringGender() : string
    {
        if (empty($this->stringGender) && $this->getGender() > 0) {
            $this->setStringGender($this->getGender());
        }

        return $this->stringGender ?? '';
    }

    /**
     * @param int $gender
     */
    protected function setStringGender(int $gender)
    {
        if (!(bool)$gender) {
            return;
        }
        $userFieldEnum = new \CUserFieldEnum();
        $item = $userFieldEnum->GetList([], ['ID' => $gender])->Fetch();
        $this->stringGender = $item['VALUE'];
        $this->setCodeGender($item['XML_ID']);
    }

    /**
     * @return int
     */
    public function getGender() : int
    {
        return $this->gender ?? 0;
    }

    /**
     * @param int $gender
     *
     * @return Pet
     */
    public function setGender(int $gender) : Pet
    {
        $this->gender = $gender;
        if ($gender > 0) {
            $this->setStringGender($gender);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getCodeGender(): string
    {
        if (empty($this->codeGender)) {
            $this->setStringGender($this->getGender());
        }
        return $this->codeGender;
    }

    /**
     * @param string $codeGender
     */
    public function setCodeGender(string $codeGender): void
    {
        $this->codeGender = $codeGender;
    }


    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size ?: 0;
    }

    /**
     * @param int $size
     * @return Pet
     */
    public function setSize(int $size): Pet
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return float
     */
    public function getChest(): float
    {
        return $this->chest ?: 0;
    }

    /**
     * @param float $chest
     * @return Pet
     */
    public function setChest(float $chest): Pet
    {
        $this->chest = $chest;
        return $this;
    }

    /**
     * @return float
     */
    public function getBack(): float
    {
        return $this->back ?: 0;
    }

    /**
     * @param float $back
     * @return Pet
     */
    public function setBack(float $back): Pet
    {
        $this->back = $back;
        return $this;
    }

    /**
     * @return float
     */
    public function getNeck(): float
    {
        return $this->neck ?: 0;
    }

    /**
     * @param float $neck
     * @return Pet
     */
    public function setNeck(float $neck): Pet
    {
        $this->neck = $neck;
        return $this;
    }

    /**
     * @return string
     */
    public function getSizeTitle(): string
    {
        /** @var PetService $petService */
        $petService = Application::getInstance()->getContainer()->get('pet.service');
        $petSizeId = $this->getSize();
        if(!$petSizeId){
            return '';
        }
        $petSizeEnum = $petService->getSizeById($petSizeId);
        $sizeTitle = $petSizeEnum->getValue();
        return $sizeTitle ?: '';
    }

    public function deleteSizeInfo()
    {
        $this->setSize(0)
            ->setBack(0)
            ->setChest(0)
            ->setNeck(0);
    }

}
