<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\PersonalBundle\Service;

use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Mail\Event;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\UI\PageNavigation;
use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\AppBundle\Exception\EmptyEntityClass;
use FourPaws\External\Exception\ManzanaServiceContactSearchNullException;
use FourPaws\External\Exception\ManzanaServiceException;
use FourPaws\External\Manzana\Exception\CardNotFoundException;
use FourPaws\External\Manzana\Exception\ContactUpdateException;
use FourPaws\External\Manzana\Model\CardByContractCards;
use FourPaws\External\Manzana\Model\Client;
use FourPaws\External\Manzana\Model\Referral as ManzanaReferal;
use FourPaws\External\Manzana\Model\ReferralParams as ManzanaReferalParams;
use FourPaws\External\ManzanaService;
use FourPaws\Helpers\Exception\WrongPhoneNumberException;
use FourPaws\Helpers\PhoneHelper;
use FourPaws\PersonalBundle\Entity\Referral;
use FourPaws\PersonalBundle\Repository\ReferralRepository;
use FourPaws\UserBundle\Entity\User;
use FourPaws\UserBundle\Exception\BitrixRuntimeException;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Exception\ValidationException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class ReferralService
 *
 * @package FourPaws\PersonalBundle\Service
 */
class ReferralService
{
    /**
     * @var ReferralRepository
     */
    public $referralRepository;
    
    /**
     * @var ManzanaService
     */
    public $manzanaService;
    
    /** @var CurrentUserProviderInterface $currentUser */
    private $currentUser;
    
    /**
     * ReferralService constructor.
     *
     * @param ReferralRepository $referralRepository
     * @param ManzanaService     $manzanaService
     *
     * @throws ServiceNotFoundException
     * @throws ApplicationCreateException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(ReferralRepository $referralRepository, ManzanaService $manzanaService)
    {
        $this->referralRepository = $referralRepository;
        $this->manzanaService     = $manzanaService;
        $this->currentUser        = App::getInstance()->getContainer()->get(CurrentUserProviderInterface::class);
    }

    /**
     * @param bool                $redirectIfAdd
     * @param PageNavigation|null $nav
     *
     * @throws EmptyEntityClass
     * @throws CardNotFoundException
     * @throws ServiceNotFoundException
     * @throws ValidationException
     * @throws InvalidIdentifierException
     * @throws SystemException
     * @throws \Exception
     * @throws ApplicationCreateException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @throws NotAuthorizedException
     * @throws ServiceCircularReferenceException
     * @return ArrayCollection
     */
    public function getCurUserReferrals(bool $redirectIfAdd = false, &$nav = null) : ArrayCollection
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $request = Application::getInstance()->getContext()->getRequest();
        $search  = (string)$request->get('search');
        $filter  = [];
        if (!empty($search)) {
            $filter['=UF_CARD'] = $search;
        }
        $referralType = $this->getReferralType();
        if (!empty($referralType)) {
            switch ($referralType) {
                case 'active':
                    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                    $filter['<=UF_CARD_CLOSED_DATE'] = new Date();
                    $filter['UF_MODERATED']          = 0;
                    break;
                case 'moderated':
                    $filter['UF_MODERATED'] = 1;
                    break;
            }
        }
        if ($nav instanceof PageNavigation) {
            $this->referralRepository->setNav($nav);
        }
        $curUser = $this->referralRepository->curUserService->getCurrentUser();
        if (!empty($filter)) {
            $filter['UF_USER_ID'] = $curUser->getId();
            $referrals            = $this->referralRepository->findBy(
                [
                    'filter' => $filter,
                    'ttl'    => 360000,
                ]
            );
        } else {
            $referrals = $this->referralRepository->findByCurUser();
        }
        if ($nav instanceof PageNavigation) {
            $nav = $this->referralRepository->getNav();
            $this->referralRepository->clearNav();
        }
        
        $this->setDataByManzana($curUser, $referrals, $request, $redirectIfAdd);
        
        return $referrals;
    }
    
    /**
     * @throws SystemException
     * @return string
     */
    public function getReferralType() : string
    {
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $request      = Application::getInstance()->getContext()->getRequest();
        $referralType = (string)$request->get('referral_type');
        $search       = (string)$request->get('search');
        if (!empty($search)) {
            $referralType = 'all';
        }
        
        return $referralType;
    }
    
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param User            $curUser
     * @param ArrayCollection $referrals
     * @param HttpRequest     $request
     * @param bool            $redirectIfAdd
     *
     * @throws EmptyEntityClass
     * @throws CardNotFoundException
     * @throws ServiceNotFoundException
     * @throws ValidationException
     * @throws InvalidIdentifierException
     * @throws \Exception
     * @throws ApplicationCreateException
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @throws ServiceCircularReferenceException
     */
    private function setDataByManzana(User $curUser, ArrayCollection $referrals, HttpRequest $request, bool $redirectIfAdd)
    {
        $arCards = [];
        if (!$referrals->isEmpty()) {
            /** @var Referral $item */
            foreach ($referrals as $key => $item) {
                $arCards[$item->getCard()] = $key;
            }
        }
        
        $manzanaReferrals = [];
        try {
            $manzanaReferrals = $this->manzanaService->getUserReferralList($curUser);
        } catch (ManzanaServiceException $e) {
        } catch (NotAuthorizedException $e) {
        }
        if (\is_array($manzanaReferrals) && !empty($manzanaReferrals)) {
            /** @var ManzanaReferal $item */
            $haveAdd = false;
            foreach ($manzanaReferrals as $item) {
                if (!array_key_exists($item->cardNumber, $arCards)) {
                    $data = [
                        'UF_CARD'    => $item->cardNumber,
                        'UF_USER_ID' => $curUser->getId(),
                    ];
                    try {
                        $card     = $this->manzanaService->searchCardByNumber($item->cardNumber);
                        $cardInfo = $this->manzanaService->getCardInfo($item->cardNumber, $card->contactId);
                        try {
                            $phone = PhoneHelper::normalizePhone($card->phone);
                        } catch (WrongPhoneNumberException $e) {
                            $phone = '';
                        }
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $data = array_merge(
                            $data,
                            [
                                'UF_NAME'             => $card->firstName,
                                'UF_LAST_NAME'        => $card->lastName,
                                'UF_SECOND_NAME'      => $card->secondName,
                                'UF_EMAIL'            => $card->email,
                                'UF_PHONE'            => $phone,
                                'UF_CARD'             => $item->cardNumber,
                                'UF_USER_ID'          => $curUser->getId(),
                                'UF_CARD_CLOSED_DATE' => $cardInfo instanceof
                                                         CardByContractCards ? $cardInfo->getExpireDate()->format(
                                    'd.m.Y'
                                ) : '',
                                'UF_MODERATED'        => $item->isQuestionnaireActual === 'Не указано' ? 'Y' : 'N',
                            ]
                        );
                        try {
                            $this->add($data);
                            if (!$haveAdd) {
                                $haveAdd = true;
                            }
                        } catch (BitrixRuntimeException $e) {
                            echo $e->getMessage();
                        } catch (\Exception $e) {
                            echo $e->getMessage();
                        }
                    } catch (ManzanaServiceException $e) {
                    }
                }
                /** @var Referral $referral */
                if (array_key_exists($item->cardNumber, $arCards)) {
                    $referral =& $referrals[$arCards[$item->cardNumber]];
                    if ($referral instanceof Referral) {
                        $referral->setBonus((float)$item->sumReferralBonus);
                        $lastModerate = $referral->isModerate();
                        $referral->setModerate($item->isQuestionnaireActual === 'Не указано');
                        if ($lastModerate !== $referral->isModerate()) {
                            $this->update(
                                [
                                    'ID'           => $referral->getId(),
                                    'UF_MODERATED' => $referral->isModerate() ? 'Y' : 'N',
                                ]
                            );
                        }
                    }
                }
            }
            unset($referral);
            if ($haveAdd && $redirectIfAdd) {
                /** обновляем если добавилась инфа, чтобы была актуальная постраничка, табы и поиск */
                LocalRedirect($request->getRequestUri());
            }
        }
    }

    /**
     * @param array $data
     *
     * @param bool  $updateManzana
     *
     * @throws EmptyEntityClass
     * @throws ManzanaServiceException
     * @throws ContactUpdateException
     * @throws ValidationException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     * @throws ApplicationCreateException
     * @throws \Exception
     * @throws BitrixRuntimeException
     * @return bool
     */
    public function add(array $data, bool $updateManzana = true) : bool
    {
        if (empty($data['UF_USER_ID'])) {
            $data['UF_USER_ID'] = $this->currentUser->getCurrentUserId();
        }
        /** @var Referral $entity */
        $entity = $this->referralRepository->dataToEntity($data, Referral::class);
        $res    = $this->referralRepository->setEntity($entity)->create();
        if ($res && $updateManzana) {
            $referralClient = $this->getClientReferral($entity);
            if (!empty($referralClient->contactId) && !empty($referralClient->cardNumber)) {
                $this->manzanaService->addReferralByBonusCard($referralClient);
            }
            /** @var User $user */
            $user = $this->referralRepository->curUserService->getUserRepository()->find($entity->getUserId());
            if ($user instanceof User) {
                Event::send(
                    [
                        'EVENT_NAME' => 'ReferralAdd',
                        'LID'        => SITE_ID,
                        'C_FIELDS'   => [
                            'CARD'       => $entity->getCard(),
                            'MAIN_PHONE' => tplvar('phone_main'),
                        ],
                    ]
                );
            }
        }
        
        return $res;
    }
    
    /**
     * @param Referral $referral
     *
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     * @throws ApplicationCreateException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws ContactUpdateException
     * @return ManzanaReferalParams
     */
    public function getClientReferral(Referral $referral) : ManzanaReferalParams
    {
        $client = new ManzanaReferalParams();
        
        $contactId = '';
        try {
            $contactId = $this->manzanaService->getContactIdByCurUser();
        } catch (ManzanaServiceContactSearchNullException $e) {
            $contactClient = new Client();
            try {
                $this->referralRepository->curUserService->setClientPersonalDataByCurUser($contactClient);
                try {
                    $res       = $this->manzanaService->updateContact($contactClient);
                    $contactId = $res->contactId;
                } catch (ManzanaServiceException $e) {
                }
            } catch (NotAuthorizedException $e) {
            }
        } catch (NotAuthorizedException $e) {
        } catch (ManzanaServiceException $e) {
        }
        if (!empty($contactId)) {
            $client->contactId = $contactId;
        }
        $client->cardNumber = $referral->getCard();
        $client->phone      = $referral->getPhone();
        $client->email      = $referral->getEmail();
        $client->lastName   = $referral->getLastName();
        $client->secondName = $referral->getSecondName();
        $client->name       = $referral->getName();
        
        return $client;
    }

    /**
     * @param array $data
     *
     * @throws EmptyEntityClass
     * @throws ValidationException
     * @throws InvalidIdentifierException
     * @throws \Exception
     * @throws BitrixRuntimeException
     * @throws ConstraintDefinitionException
     * @return bool
     */
    public function update(array $data) : bool
    {
        return $this->referralRepository->setEntityFromData($data, Referral::class)->update();
    }
    
    /**
     * @return int
     */
    public function getAllCountByUser() : int
    {
        try {
            return $this->referralRepository->getCount(
                ['UF_USER_ID' => $this->referralRepository->curUserService->getCurrentUserId()]
            );
        } catch (ObjectPropertyException $e) {
        } catch (NotAuthorizedException $e) {
        }
        
        return 0;
    }

    /**
     * @return int
     * @throws ObjectException
     */
    public function getActiveCountByUser() : int
    {
        try {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return $this->referralRepository->getCount(
                [
                    'UF_USER_ID'           => $this->referralRepository->curUserService->getCurrentUserId(),
                    '>UF_CARD_CLOSED_DATE' => new Date(),
                    'UF_MODERATED'         => 0,
                ]
            );
        } catch (ObjectPropertyException $e) {
        } catch (NotAuthorizedException $e) {
        }
        
        return 0;
    }
    
    /**
     * @return int
     */
    public function getModeratedCountByUser() : int
    {
        try {
            return $this->referralRepository->getCount(
                [
                    'UF_USER_ID'   => $this->referralRepository->curUserService->getCurrentUserId(),
                    'UF_MODERATED' => 1,
                ]
            );
        } catch (ObjectPropertyException $e) {
        } catch (NotAuthorizedException $e) {
        }
        
        return 0;
    }
}
