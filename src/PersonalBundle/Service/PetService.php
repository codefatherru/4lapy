<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\PersonalBundle\Service;

use Bitrix\Main\Application;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Security\SecurityException;
use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Entity\BaseEntity;
use FourPaws\AppBundle\Exception\EmptyEntityClass;
use FourPaws\AppBundle\Exception\NotFoundException;
use FourPaws\External\Exception\ManzanaServiceException;
use FourPaws\External\Manzana\Model\Client;
use FourPaws\External\ManzanaService;
use FourPaws\PersonalBundle\Entity\Pet;
use FourPaws\PersonalBundle\Repository\PetRepository;
use FourPaws\UserBundle\Exception\BitrixRuntimeException;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Exception\ValidationException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class PetService
 *
 * @package FourPaws\PersonalBundle\Service
 */
class PetService
{
    /**
     * @var PetRepository
     */
    private $petRepository;
    
    /** @var CurrentUserProviderInterface $currentUser */
    private $currentUser;
    
    /** @var ManzanaService $currentUser */
    private $manzanaService;
    
    /**
     * PetService constructor.
     *
     * @param PetRepository                $petRepository
     * @param CurrentUserProviderInterface $currentUserProvider
     * @param ManzanaService               $manzanaService
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(
        PetRepository $petRepository,
        CurrentUserProviderInterface $currentUserProvider,
        ManzanaService $manzanaService
    )
    {
        $this->petRepository  = $petRepository;
        $this->currentUser    = $currentUserProvider;
        $this->manzanaService = $manzanaService;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws EmptyEntityClass
     * @throws NotAuthorizedException
     * @throws ConstraintDefinitionException
     * @throws ServiceNotFoundException
     * @throws InvalidIdentifierException
     * @throws ServiceCircularReferenceException
     * @throws \RuntimeException
     * @throws ApplicationCreateException
     * @throws ValidationException
     * @throws BitrixRuntimeException
     * @throws ObjectPropertyException
     * @throws \Exception
     */
    public function add(array $data) : bool
    {
        if (empty($data['UF_USER_ID'])) {
            $data['UF_USER_ID'] = $this->currentUser->getCurrentUserId();
        }
        if (!empty($data['UF_PHOTO_TMP'])) {
            $this->petRepository->addFileList(['UF_PHOTO' => $data['UF_PHOTO_TMP']]);
        }
        else{
            unset($data['UF_PHOTO']);
        }
        /** @var Pet $entity */
        $entity = $this->petRepository->dataToEntity($data, Pet::class);
        $this->petRepository->setEntity($entity);
        $res = $this->petRepository->create();
        if ($res) {
            $this->updateManzanaPets();

            if (\defined('BX_COMP_MANAGED_CACHE')) {
                /** Очистка кеша */
                $instance = Application::getInstance();
                $tagCache = $instance->getTaggedCache();
                $tagCache->clearByTag('personal:pets:' . $entity->getUserId());
            }
        }
        
        return $res;
    }

    /**
     * @throws NotAuthorizedException
     * @throws ConstraintDefinitionException
     * @throws ServiceNotFoundException
     * @throws InvalidIdentifierException
     * @throws ApplicationCreateException
     * @throws \RuntimeException
     * @throws ServiceCircularReferenceException
     * @throws ObjectPropertyException
     */
    protected function updateManzanaPets(): void
    {
        $types     = [];

        $pets = $this->getCurUserPets();
        if (!$pets->isEmpty()) {
            /** @var Pet $pet */
            foreach ($pets as $pet) {
                $types[] = $pet->getCodeType();
            }
        }

        $client = null;
        try {
            $contactId = $this->manzanaService->getContactIdByUser();
            $client = new Client();
            $client->contactId = $contactId;
        } catch (ManzanaServiceException $e) {
            $client = new Client();
            $this->currentUser->setClientPersonalDataByCurUser($client);
        }

        if ($client instanceof Client) {
            $this->setClientPets($client, $types);
            $this->manzanaService->updateContactAsync($client);
        }
    }

    /**
     * @throws ObjectPropertyException
     * @throws NotAuthorizedException
     * @throws InvalidIdentifierException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @return ArrayCollection
     */
    public function getCurUserPets() : ArrayCollection
    {
        return $this->petRepository->findByCurUser();
    }
    
    /**
     * @param Client $client
     * @param array  $types
     */
    public function setClientPets(&$client, array $types): void
    {
        /** @todo set actual types */
        $baseTypes        = [
            'ptitsy',
            'koshki',
            'sobaki',
            'ryby',
            'gryzuny',
            'ptitsy-gryzuny',
            'koshki-sobaki',
        ];
        $client->ffBird   = \in_array('ptitsy', $types, true) || \in_array('ptitsy-gryzuny', $types, true) ? 1 : 0;
        $client->ffCat    = \in_array('koshki', $types, true) || \in_array('koshki-sobaki', $types, true) ? 1 : 0;
        $client->ffDog    = \in_array('sobaki', $types, true) || \in_array('koshki-sobaki', $types, true) ? 1 : 0;
        $client->ffFish   = \in_array('ryby', $types, true) ? 1 : 0;
        $client->ffRodent = \in_array('gryzuny', $types, true) || \in_array('ptitsy-gryzuny', $types, true) ? 1 : 0;
        $others           = 0;
        if (\is_array($types) && !empty($types)) {
            foreach ($types as $type) {
                if (!\in_array($type, $baseTypes, true)) {
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
     * @throws \RuntimeException
     * @throws ApplicationCreateException
     * @throws ValidationException
     * @throws InvalidIdentifierException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @return bool
     * @throws ObjectPropertyException
     * @throws \Exception
     */
    public function update(array $data) : bool
    {
        /** @var Pet $entity */
        $entity = $this->petRepository->dataToEntity($data, Pet::class);

        $updateEntity = $this->getById($entity->getId());
        if ($updateEntity->getUserId() !== $this->currentUser->getCurrentUserId()) {
            throw new SecurityException('не хватает прав доступа для совершения данной операции');
        }

        if (empty($data['UF_USER_ID'])) {
            $data['UF_USER_ID'] = $this->currentUser->getCurrentUserId();
        }
        if (!empty($data['UF_PHOTO_TMP'])) {
            $this->petRepository->addFileList(['UF_PHOTO' => $data['UF_PHOTO_TMP']]);
        }
        else{
            unset($data['UF_PHOTO']);
        }

        $res = $this->petRepository->setEntity($entity)->update();
        if ($res) {
            $this->updateManzanaPets();

            if (\defined('BX_COMP_MANAGED_CACHE')) {
                /** Очистка кеша */
                $instance = Application::getInstance();
                $tagCache = $instance->getTaggedCache();
                $tagCache->clearByTag('personal:pets:' . $updateEntity->getUserId());
            }
        }
        
        return $res;
    }

    /**
     * @param int $id
     *
     * @throws NotFoundException
     * @throws NotAuthorizedException
     * @throws SecurityException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws \RuntimeException
     * @throws ApplicationCreateException
     * @throws InvalidIdentifierException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @return bool
     * @throws ObjectPropertyException
     * @throws \Exception
     */
    public function delete(int $id) : bool
    {
        $deleteEntity = $this->getById($id);
        if ($deleteEntity->getUserId() !== $this->currentUser->getCurrentUserId()) {
            throw new SecurityException('не хватает прав доступа для совершения данной операции');
        }

        $res = $this->petRepository->delete($id);
        if ($res) {
            $this->updateManzanaPets();

            if (\defined('BX_COMP_MANAGED_CACHE')) {
                /** Очистка кеша */
                $instance = Application::getInstance();
                $tagCache = $instance->getTaggedCache();
                $tagCache->clearByTag('personal:pets:' . $deleteEntity->getUserId());
            }
        }
        
        return $res;
    }

    /**
     * @param int $id
     *
     * @return Pet|BaseEntity
     * @throws ObjectPropertyException
     * @throws \Exception
     * @throws NotFoundException
     */
    public function getById(int $id): Pet
    {
        return $this->petRepository->findById($id);
    }
}
