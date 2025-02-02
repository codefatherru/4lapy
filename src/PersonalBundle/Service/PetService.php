<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\PersonalBundle\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Security\SecurityException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserFieldTable;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Collection\UserFieldEnumCollection;
use FourPaws\AppBundle\Entity\BaseEntity;
use FourPaws\AppBundle\Entity\UserFieldEnumValue;
use FourPaws\AppBundle\Exception\EmptyEntityClass;
use FourPaws\AppBundle\Exception\NotFoundException;
use FourPaws\External\Exception\ManzanaServiceException;
use FourPaws\External\ExpertsenderService;
use FourPaws\External\Manzana\Model\Client;
use FourPaws\External\ManzanaService;
use FourPaws\Helpers\HighloadHelper;
use FourPaws\PersonalBundle\Entity\Pet;
use FourPaws\PersonalBundle\Models\PetCongratulationsNotify;
use FourPaws\PersonalBundle\Repository\PetRepository;
use FourPaws\UserBundle\Entity\User;
use FourPaws\UserBundle\Exception\BitrixRuntimeException;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Exception\ValidationException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use function in_array;
use function is_array;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Adv\Bitrixtools\Tools\HLBlock\HLBlockFactory;
use FourPaws\AppBundle\Service\UserFieldEnumService;

/**
 * Class PetService
 *
 * @package FourPaws\PersonalBundle\Service
 */
class PetService
{
    /** @var PetRepository */
    private $petRepository;

    /** @var CurrentUserProviderInterface $currentUser */
    private $currentUser;

    /** @var ManzanaService $currentUser */
    private $manzanaService;

    /** @var UserFieldEnumService */
    private $userFieldEnumService;

    /** @var UserFieldEnumCollection */
    private $genders;

    /** @var UserFieldEnumCollection */
    private $sizes;

    /**
     * @var ExpertsenderService
     */
    private $expertSenderService;

    public const PETS_TYPE = [
        'koshki' => 'cat',
        'sobaki' => 'dog',
        'ryby' => 'fish',
        'ptitsy' => 'bird',
        '9' => 'reptile',
        'gryzuny' => 'rodent'
    ];

    /**
     * PetService constructor.
     *
     * @param PetRepository $petRepository
     * @param CurrentUserProviderInterface $currentUserProvider
     * @param ManzanaService $manzanaService
     * @param UserFieldEnumService $userFieldEnumService
     */
    public function __construct(
        PetRepository $petRepository,
        CurrentUserProviderInterface $currentUserProvider,
        ManzanaService $manzanaService,
        UserFieldEnumService $userFieldEnumService,
        ExpertsenderService $expertsenderService
    ) {
        $this->petRepository = $petRepository;
        $this->currentUser = $currentUserProvider;
        $this->manzanaService = $manzanaService;
        $this->userFieldEnumService = $userFieldEnumService;
        $this->expertSenderService = $expertsenderService;
    }

    /**
     * @param array $data
     *
     * @throws EmptyEntityClass
     * @throws NotAuthorizedException
     * @throws ConstraintDefinitionException
     * @throws ServiceNotFoundException
     * @throws InvalidIdentifierException
     * @throws ServiceCircularReferenceException
     * @throws RuntimeException
     * @throws ValidationException
     * @throws BitrixRuntimeException
     * @throws Exception
     * @return bool
     */
    public function add(array $data): bool
    {
        $data['UF_NAME'] = strip_tags($data['UF_NAME']);
        if (empty($data['UF_USER_ID'])) {
            $data['UF_USER_ID'] = $this->currentUser->getCurrentUserId();
        }
        if (!empty($data['UF_PHOTO_TMP'])) {
            $this->petRepository->addFileList(['UF_PHOTO' => $data['UF_PHOTO_TMP']]);
        } else {
            unset($data['UF_PHOTO']);
        }
        /** @var Pet $entity */
        $entity = $this->petRepository->dataToEntity($data, Pet::class);

        if(!$this->isDogType($entity)){
            $entity->deleteSizeInfo();
        }


        $res = HLBlockFactory::createTableObject(Pet::PET_TYPE)::query()->setFilter(['ID' => $entity->getType()])->setSelect(
            [
                'ID',
                'UF_EXPERT_SENDER_ID',
            ]
        )->setOrder(['UF_SORT' => 'asc'])->exec();

        if (($petType = $res->Fetch()) && ($petType['UF_EXPERT_SENDER_ID'])) {
            $this->expertSenderService->sendAfterPetUpdateAsync($this->currentUser, $petType['UF_EXPERT_SENDER_ID']);
        }

        $this->petRepository->setEntity($entity);
        return $this->petRepository->create();
    }

    /**
     * @param int|User $user
     *
     * @throws ApplicationCreateException
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function updateManzanaPets($user = null): void
    {
        $types = [];

        if ($user !== null) {
            $pets = $this->getUserPets($user);
        } else {
            $pets = $this->getCurUserPets();
        }

        if (!$pets->isEmpty()) {
            /** @var Pet $pet */
            foreach ($pets as $pet) {
                $types[] = $pet->getCodeType();
            }
        }

        try {
            $contactId = $this->manzanaService->getContactIdByUser();
            $client = new Client();
            if(!empty($contactId)) {
                $client->contactId = $contactId;
            }
        } catch (ManzanaServiceException $e) {
            $client = new Client();
            $this->currentUser->setClientPersonalDataByCurUser($client);
        }

        $this->setClientPets($client, $types);
        $this->manzanaService->updateContactAsync($client);
    }

    /**
     * @return ArrayCollection
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws NotAuthorizedException
     * @throws SystemException
     */
    public function getCurUserPets(): ArrayCollection
    {
        return $this->petRepository->findByCurUser();
    }

    /**
     * @param int $id
     * @return Pet
     * @throws ObjectPropertyException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCurUserPetById(int $id): Pet
    {
        return $this->petRepository->findByCurUserAndId($id)->current();
    }

    /**
     * @param User|int $user
     *
     * @return ArrayCollection
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getUserPets($user): ArrayCollection
    {
        return $this->petRepository->findByUser($user);
    }

    /**
     * @param array $users
     * @return ArrayCollection
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getUsersPets(array $users): ArrayCollection
    {
        return $this->petRepository->findByUsersIds($users);
    }

    /**
     * @param User|int $userId
     *
     * @return array
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getUserPetsTypesCodes($userId): array
    {
        $userPets = $this->petRepository->findByUser($userId);
        $petsTypes = [];
        if (!$userPets->isEmpty()) {
            $userPetsTypeIds = [];
            foreach ($userPets as $pet) {
                $userPetsTypeIds[] = $pet->getType();
            }
            if (count($userPetsTypeIds)) {
                $petBreeds = $this->getPetTypes(['ID' => $userPetsTypeIds]);
                foreach ($petBreeds as $petBreedCode => $petBreed) {
                    if (in_array($petBreedCode, array_keys(static::PETS_TYPE))) {
                        $petsTypes[static::PETS_TYPE[$petBreedCode]] = true;
                    }
                }
            }
        }
        return $petsTypes;
    }

    /**
     * @param Client $client
     * @param array  $types
     */
    public function setClientPets(&$client, array $types): void
    {
        $baseTypes = [
            'ptitsy',
            'koshki',
            'sobaki',
            'ryby',
            'gryzuny',
            'ptitsy-gryzuny',
            '90000001',
            'koshki-sobaki',
            '3@11',
        ];
        $client->ffBird = in_array('ptitsy', $types, true) || in_array('ptitsy-gryzuny', $types,
            true) || in_array('90000001', $types, true) ? 1 : 0;
        $client->ffCat = in_array('koshki', $types, true) || in_array('koshki-sobaki', $types,
            true) || in_array('3@11', $types, true) ? 1 : 0;
        $client->ffDog = in_array('sobaki', $types, true) || in_array('koshki-sobaki', $types,
            true) || in_array('3@11', $types, true) ? 1 : 0;
        $client->ffFish = in_array('ryby', $types, true) ? 1 : 0;
        $client->ffRodent = in_array('gryzuny', $types, true) || in_array('ptitsy-gryzuny', $types,
            true) || in_array('90000001', $types, true) ? 1 : 0;
        $others = 0;
        if (is_array($types) && !empty($types)) {
            foreach ($types as $type) {
                if (!in_array($type, $baseTypes, true)) {
                    $others = 1;
                    break;
                }
            }
        }
        $client->ffOthers = $others;
    }

    /**
     * @param array $data
     *
     * @throws SecurityException
     * @throws NotFoundException
     * @throws NotAuthorizedException
     * @throws EmptyEntityClass
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws RuntimeException
     * @throws ValidationException
     * @throws InvalidIdentifierException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @throws ObjectPropertyException
     * @throws Exception
     * @return bool
     */
    public function update(array $data): bool
    {
        if (empty($data['UF_PHOTO_TMP'])) {
            unset($data['UF_PHOTO']);
            $this->petRepository->addFileList(['UF_PHOTO' => 'skip']);
        } else {
            $this->petRepository->addFileList(['UF_PHOTO' => $data['UF_PHOTO_TMP']]);
        }

        /** @var Pet $entity */
        $entity = $this->petRepository->dataToEntity($data, Pet::class);

        $updateEntity = $this->getById($entity->getId());

        if ($updateEntity->getUserId() !== $this->currentUser->getCurrentUserId()) {
            throw new SecurityException('не хватает прав доступа для совершения данной операции');
        }

        $expertSenderPetIds = [];

        $res = HLBlockFactory::createTableObject(Pet::PET_TYPE)::query()->setFilter(['ID' => [$entity->getType(), $updateEntity->getType()]])->setSelect(
            [
                'ID',
                'UF_EXPERT_SENDER_ID',
            ]
        )->setOrder(['UF_SORT' => 'asc'])->exec();

        while ($petType = $res->Fetch()) {
            $expertSenderPetIds[$petType['ID']] = $petType['UF_EXPERT_SENDER_ID'];
        }

        $this->expertSenderService->sendAfterPetUpdateAsync($this->currentUser, $expertSenderPetIds[$entity->getType()], $expertSenderPetIds[$updateEntity->getType()]);

        if ($entity->getUserId() === 0) {
            $entity->setUserId($updateEntity->getUserId());
        }

        if(
            (!$entity->getType() && !$this->isDogType($updateEntity))
            ||
            ($entity->getType() > 0 && !$this->isDogType($entity))
        ){
            $entity->deleteSizeInfo();
        }

        return $this->petRepository->setEntity($entity)->update();
    }

    /**
     * @param Pet $pet
     * @return bool
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function isDogType(Pet $pet){
        $petType = array_pop($this->getPetTypes(['ID' => $pet->getType()]));
        return $petType['UF_NAME'] == 'Собаки';
    }

    /**
     * @param int $id
     *
     * @throws NotFoundException
     * @throws NotAuthorizedException
     * @throws SecurityException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws RuntimeException
     * @throws InvalidIdentifierException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @throws ObjectPropertyException
     * @throws Exception
     * @return bool
     */
    public function delete(int $id): bool
    {
        $deleteEntity = $this->getById($id);


        if ($deleteEntity->getUserId() !== $this->currentUser->getCurrentUserId()) {
            throw new SecurityException('не хватает прав доступа для совершения данной операции');
        }

        $res = HLBlockFactory::createTableObject(Pet::PET_TYPE)::query()->setFilter(['ID' => $deleteEntity->getType()])->setSelect(
            [
                'ID',
                'UF_EXPERT_SENDER_ID',
            ]
        )->setOrder(['UF_SORT' => 'asc'])->exec();

        if (($petType = $res->Fetch()) && ($petType['UF_EXPERT_SENDER_ID'])) {
            $this->expertSenderService->sendAfterPetUpdateAsync($this->currentUser, null, $petType['UF_EXPERT_SENDER_ID']);
        }

        return $this->petRepository->delete($id);
    }

    /**
     * @param int $id
     *
     * @throws ObjectPropertyException
     * @throws Exception
     * @throws NotFoundException
     * @return BaseEntity|Pet
     */
    public function getById(int $id): Pet
    {
        return $this->petRepository->findById($id);
    }

    /**
     * @return array
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws ObjectException
     * @throws SystemException
     */
    public function getBirthdayPets()
    {
        $pets = $this->petRepository->findPetsForBirthDayNotify();
        $result = [];
        foreach ($pets as $pet) {
            $result[] = (new PetCongratulationsNotify())->setPetId($pet['PET_ID'])
                ->setPetName($pet['PET_NAME'])
                ->setPetType($pet['PET_TYPE'])
                ->setOwnerEmail($pet['USER_EMAIL'])
                ->setBirthDay($pet['PET_BIRTHDAY'])
                ->setOwnerName($pet['USER_NAME']);
        }
        return $result;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPetBreedAll(): array
    {
        //if ($this->startResultCache()){
        // Для тегированного кеша нет функционала для highload-иб
        /*TaggedCacheHelper::addManagedCacheTags([
            'hlb:field:pets_user:' . $this->currentUserProvider->getCurrentUserId()
        ]);*/

        return HLBlockFactory::createTableObject(Pet::PET_BREED)::query()
            ->setSelect([
                'ID',
                'UF_NAME',
                'UF_PET_TYPE'
            ])
            ->setOrder([
                'UF_NAME' => 'asc'
            ])
            ->exec()
            ->fetchAll();
        //}
    }


    /**
     * @param int $typeId
     * @return array
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getPetBreed(int $typeId): array
    {
        //if ($this->startResultCache()){
            // Для тегированного кеша нет функционала для highload-иб
            /*TaggedCacheHelper::addManagedCacheTags([
                'hlb:field:pets_user:' . $this->currentUserProvider->getCurrentUserId()
            ]);*/

            $arBreeds = [];
            $res =
                HLBlockFactory::createTableObject(Pet::PET_BREED)::query()->setFilter(['UF_PET_TYPE' => $typeId])->setSelect(
                    [
                        'ID',
                        'UF_NAME',
                    ]
                )->setOrder(['UF_NAME' => 'asc'])->exec();
            while ($item = $res->fetch()) {
                $arBreeds[$item['ID']] = $item['UF_NAME'];
            }

            return $arBreeds;
        //}
    }

    /**
     * @param array $filter
     * @return array
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getPetTypes(array $filter): array
    {
        $arBreeds = [];
        $res = HLBlockFactory::createTableObject(Pet::PET_TYPE)::query()->setFilter($filter)->setSelect(
            [
                'ID',
                'UF_NAME',
                'UF_CODE'
            ]
        )->setOrder(['UF_CODE' => 'asc'])->exec();
        while ($item = $res->fetch()) {
            $arBreeds[$item['UF_CODE']] = $item;
        }

        return $arBreeds;
    }

    /**
     * @param int $petId
     * @return bool
     * @throws NotFoundException
     * @throws ObjectPropertyException
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function deletePetPhoto(int $petId)
    {
        /** @var Pet $pet */
        $pet = $this->petRepository->findById($petId);
        if ($photoId = $pet->getPhoto()) {
            \CFile::delete($photoId);
        }
        $pet->setPhoto(0);
        return $this->petRepository->setEntity($pet)->update();
    }

    /**
     * @return \FourPaws\AppBundle\Collection\UserFieldEnumCollection
     * @throws \Bitrix\Main\ArgumentException
     * @throws LoaderException
     */
    public function getGenders()
    {
        if(null === $this->genders){
            $userFieldId = UserFieldTable::query()->setSelect(['ID', 'XML_ID'])->setFilter(
                [
                    'FIELD_NAME' => 'UF_GENDER',
                    'ENTITY_ID' => 'HLBLOCK_' . HighloadHelper::getIdByName('Pet'),
                ]
            )->exec()->fetch()['ID'];
            $this->genders = $this->userFieldEnumService->getEnumValueCollection($userFieldId);
        }

        return $this->genders;
    }

    /**
     * @param string $genderCode
     * @return UserFieldEnumValue
     * @throws \Bitrix\Main\ArgumentException
     * @throws LoaderException
     */
    public function getGenderByCode(string $genderCode)
    {
        return $this->getGenders()->filter(function ($gender) use($genderCode) {
            /** @var UserFieldEnumValue $gender */
            return $genderCode === $gender->getXmlId();
        })->current();
    }

    /**
     * @return UserFieldEnumCollection
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public function getSizes()
    {
        if(null === $this->sizes){
            $userFieldId = UserFieldTable::query()->setSelect(['ID', 'XML_ID'])->setFilter(
                [
                    'FIELD_NAME' => 'UF_SIZE',
                    'ENTITY_ID' => 'HLBLOCK_' . HighloadHelper::getIdByName('Pet'),
                ]
            )->exec()->fetch()['ID'];
            $this->sizes = $this->userFieldEnumService->getEnumValueCollection($userFieldId);
        }

        return $this->sizes;
    }

    /**
     * @param string $sizeCode
     * @return UserFieldEnumValue|false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public function getSizeByCode(string $sizeCode)
    {
        return $this->getSizes()->filter(function ($size) use($sizeCode) {
            /** @var UserFieldEnumValue $size */
            return $sizeCode === $size->getXmlId();
        })->current();
    }

    /**
     * @param string $sizeId
     * @return UserFieldEnumValue|false
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws LoaderException
     */
    public function getSizeById($sizeId)
    {
        return $this->getSizes()->filter(function ($size) use($sizeId) {
            /** @var UserFieldEnumValue $size */
            return (int)$sizeId === $size->getId();
        })->current();
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws NotAuthorizedException
     * @throws Exception
     */
    public function getCurUserPetSizes(): UserFieldEnumCollection
    {
        $petSizeIds = [];

        /** @var Pet $userPet */
        foreach ($this->getCurUserPets() as $userPet) {
            if (!$userPet->getSize()) {
                continue;
            }

            if (!in_array($userPet->getSize(), $petSizeIds, true)) {
                $petSizeIds[] = $userPet->getSize();
            }
        }

        return $this->getSizes()->filter(static function ($size) use($petSizeIds) {
            /** @var UserFieldEnumValue $size */
            return in_array($size->getId(), $petSizeIds, true);
        });
    }
}
