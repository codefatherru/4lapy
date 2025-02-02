<?php

namespace FourPaws\MobileApiBundle\Services\Api;

use Adv\Bitrixtools\Tools\HLBlock\HLBlockFactory;
use Adv\Bitrixtools\Tools\Log\LazyLoggerAwareTrait;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\FileTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use FourPaws\App\Application;
use FourPaws\BitrixOrm\Collection\ImageCollection;
use FourPaws\BitrixOrm\Model\Image;
use FourPaws\BitrixOrm\Model\Interfaces\ImageInterface;
use FourPaws\Catalog\Model\Offer;
use FourPaws\Catalog\Query\OfferQuery;
use FourPaws\MobileApiBundle\Dto\Object\Catalog\FullProduct;
use FourPaws\MobileApiBundle\Dto\Object\Quest\AnswerVariant;
use FourPaws\MobileApiBundle\Dto\Object\Quest\BarcodeTask;
use FourPaws\MobileApiBundle\Dto\Object\Quest\Pet;
use FourPaws\MobileApiBundle\Dto\Object\Quest\Prize;
use FourPaws\MobileApiBundle\Dto\Object\Quest\QuestionTask;
use FourPaws\MobileApiBundle\Dto\Object\Quest\QuestStatus;
use FourPaws\MobileApiBundle\Dto\Object\User;
use FourPaws\MobileApiBundle\Dto\Request\QuestBarcodeRequest;
use FourPaws\MobileApiBundle\Dto\Request\QuestQuestionRequest;
use FourPaws\MobileApiBundle\Dto\Request\QuestRegisterRequest;
use FourPaws\MobileApiBundle\Dto\Request\QuestStartRequest;
use FourPaws\MobileApiBundle\Dto\Response\QuestRegisterGetResponse;
use FourPaws\MobileApiBundle\Exception\AccessDeinedException;
use FourPaws\MobileApiBundle\Services\Api\ProductService as ApiProductService;
use FourPaws\UserBundle\Exception\NotFoundException;
use FourPaws\UserBundle\Service\UserSearchInterface;
use FourPaws\MobileApiBundle\Exception\RuntimeException as ApiRuntimeException;
use Symfony\Component\HttpFoundation\Request;
use WebArch\BitrixCache\BitrixCache;

class QuestService
{
    use LazyLoggerAwareTrait;

    protected const QUEST_CODE = 'ЧЕТЫРЕ ЛАПЫ';

    public const MIN_CORRECT_ANSWERS = 4;
    public const MIN_CORRECT_ANSWERS_TEXT = "Вы всегда можете проконсультироваться с продавцом перед покупкой.\n\n";
    public const CORRECT_ANSWERS = 'Вы ответили правильно на %d из %d!';

    protected const PET_HL_NAME = 'QuestPet';
    protected const PRIZE_HL_NAME = 'QuestPrize';
    protected const RESULT_HL_NAME = 'QuestResult';
    protected const TASK_HL_NAME = 'QuestTask';
    protected const PROMOCODE_HL_NAME = 'QuestPromocode';

    protected const TASK_SELECT = ['ID', 'UF_TITLE', 'UF_TASK', 'UF_IMAGE', 'UF_VARIANTS', 'UF_ANSWER', 'UF_PRODUCT_XML_ID', 'UF_QUESTION', 'UF_CATEGORY', 'UF_CORRECT_TEXT', 'UF_QUESTION_ERROR'];

    /**
     * @var ApiProductService
     */
    private $apiProductService;

    /**
     * @var ImageProcessor
     */
    protected $imageProcessor;

    /**
     * @var UserService
     */
    protected $apiUserService;

    /**
     * @var UserSearchInterface
     */
    protected $appUserService;

    /**
     * @var DataManager[]
     */
    protected $dataManagers;

    /**
     * @var User|null
     */
    protected $currentUser;

    /**
     * @var array|null
     */
    protected $currentUserResult;

    /**
     * QuestService constructor.
     * @param ProductService $apiProductService
     * @param ImageProcessor $imageProcessor
     * @param UserService $apiUserService
     * @param UserSearchInterface $appUserService
     */
    public function __construct(
        ApiProductService $apiProductService,
        ImageProcessor $imageProcessor,
        UserService $apiUserService,
        UserSearchInterface $appUserService
    )
    {
        $this->apiProductService = $apiProductService;
        $this->imageProcessor = $imageProcessor;
        $this->apiUserService = $apiUserService;
        $this->appUserService = $appUserService;
    }

    /**
     * todo delete
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function resetQuest(): void
    {
        $dataManager = $this->getDataManager(self::RESULT_HL_NAME);

        $res = $dataManager::query()
            ->setSelect(['ID'])
            ->exec();

        while ($result = $res->fetch()) {
            $dataManager::delete($result['ID']);
        }
    }

    /**
     * @return QuestRegisterGetResponse
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getQuestRegisterStatus(): QuestRegisterGetResponse
    {
        $userResult = $this->getUserResult();
        $result = (new QuestRegisterGetResponse())
            ->setNeedRegister(($userResult === null))
            ->setHasEmail(!empty($this->getCurrentUser()->getEmail()))
            ->setUserEmail($this->getCurrentUser()->getEmail());

        if (!$result->isNeedRegister()) {
            $needChoosePet = true;

            if ($this->isFinishStep($userResult)) {
                /** @var Pet $userPet */
                $userPet = current($this->getPetTypes([$userResult['UF_PET']]));
                if ($userPet && ($userPet !== null)) {
                    $needChoosePet = false;

                    if (empty($userResult['UF_PRIZE']) || ($userResult['UF_PRIZE'] === null)) {
                        $result
                            ->setIsFinishStep(true)
                            ->setPrizes($userPet->getPrizes())
                            ->setCorrectText($this->getCorrectText($userResult))
                            ->setPrizeText($this->getPrizeText($userResult));
                    } else {
                        $result
                            ->setShowPrize(true)
                            ->setPromocode($this->getUserPromocode())
                            ->setUserPrize($this->getUserPrize());
                    }
                }
            }

            if ($needChoosePet) {
                $result
                    ->setNeedChoosePet(true)
                    ->setPetTypes($this->getPetTypes());
            }
        }

        return $result;
    }

    /**
     * @param QuestRegisterRequest $questRegisterRequest
     * @return void
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws AccessDeinedException
     * @throws Exception
     */
    public function registerUser(QuestRegisterRequest $questRegisterRequest): void
    {
        $user = $this->getCurrentUser();

        $needUpdateUser = false;

        if ($userEmail = $user->getEmail()) {
            if ($userEmail !== $questRegisterRequest->getEmail()) {
                throw new AccessDeinedException('Ошибка в валидации электронной почты');
            }
        } else {
            $email = $questRegisterRequest->getEmail();

            try {
                $this->appUserService->findOneByEmail($email);
                throw new AccessDeinedException('Введеный вами email уже используется');
            } catch (NotFoundException $e) {
            }

            $user->setEmail($email);
        }

        if (ToUpper($questRegisterRequest->getCode()) !== self::QUEST_CODE) {
            throw new AccessDeinedException('Введите корректный код');
        }

        if ($needUpdateUser) {
            $expertSender = Application::getInstance()->getContainer()->get('expertsender.service');
            // todo обновить пользователя и послать письмо с подтвержение почты
        }

        $userResult = $this->getUserResult();

        if ($userResult !== null) {
            $this->getDataManager(self::RESULT_HL_NAME)::update($userResult['ID'], [
                'UF_PET' => null,
                'UF_TASKS' => null,
                'UF_CURRENT_TASK' => 0,
            ]);
        } else {
            $this->getDataManager(self::RESULT_HL_NAME)::add([
                'UF_USER_ID' => $user->getId(),
            ]);
        }
    }

    /**
     * @param QuestStartRequest $questStartRequest
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws AccessDeinedException
     * @throws Exception
     */
    public function startQuest(QuestStartRequest $questStartRequest): void
    {
        $userResult = $this->getUserResult();

        $pets = $this->getPetTypes([$questStartRequest->getPetTypeId()]);

        if (!isset($pets[$questStartRequest->getPetTypeId()])) {
            throw new AccessDeinedException('Неккоректный ID питомца');
        }

        /** @var Pet $userPet */
        $userPet = $pets[$questStartRequest->getPetTypeId()];

        $userResult['UF_PET'] = $userPet->getId();
        $userResult['UF_TASKS'] = serialize($this->generateTasks($userPet->getId()));
        $userResult['UF_CURRENT_TASK'] = 1;

        $this->updateCurrentUserResult($userResult);
    }

    /**
     * @param QuestBarcodeRequest $questBarcodeRequest
     * @return int
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function checkBarcodeTask(QuestBarcodeRequest $questBarcodeRequest): int
    {
        $productList = $this->apiProductService->getList(new Request(), 0, 'relevance', 1, 1, $questBarcodeRequest->getBarcode());

        // todo debug
        $skipCheck = false;
        if ($questBarcodeRequest->getBarcode() === '8595237013098') {
            $skipCheck = true;
        }

        $product = null;
        if ($currentProduct = $productList->current()) {
            /** @var FullProduct $product */
            $product = $currentProduct[0];
        }

        if ($product === null) {
            return BarcodeTask::SCAN_ERROR;
        }

        $offerCollection = (new OfferQuery())->withFilter(['=ID' => $product->getId()])->exec();

        if ($offerCollection->isEmpty()) {
            return BarcodeTask::SCAN_ERROR;
        }

        $currentTask = $this->getCurrentTask();

        /** @var Offer $offer */
        $offer = $offerCollection->first();

        if ($skipCheck || ($this->checkProductByCategory($offer, $currentTask) && $this->checkProductByXmlId($offer, $currentTask))) {
            $userResult = $this->getUserResult();

            if ($userResult === false) {
                throw new ApiRuntimeException('Начните проходить квест');
            }

            $userTasks = unserialize($userResult['UF_TASKS']);

            if (!isset($userTasks[$userResult['UF_CURRENT_TASK']]['ID'])) {
                throw new ApiRuntimeException('Задание не найдено');
            }

            $userTasks[$userResult['UF_CURRENT_TASK']]['BARCODE_COMPLETE'] = true;

            $userResult['UF_TASKS'] = serialize($userTasks);

            $this->updateCurrentUserResult($userResult);

            return BarcodeTask::SUCCESS_SCAN;
        }

        return BarcodeTask::INCORRECT_PRODUCT;
    }

    /**
     * @param Offer $offer
     * @param $currentTask
     * @return bool
     * @throws SystemException
     */
    protected function checkProductByCategory(Offer $offer, $currentTask): bool
    {
        if (!isset($currentTask['UF_CATEGORY']) || empty($currentTask['UF_CATEGORY']) || ($currentTask['UF_CATEGORY'] === null)) {
            return true;
        }

        return (in_array($currentTask['UF_CATEGORY'], $offer->getProduct()->getSectionsIdList(), false));
    }

    /**
     * @param Offer $offer
     * @param $currentTask
     * @return bool
     */
    protected function checkProductByXmlId(Offer $offer, $currentTask): bool
    {
        if (!isset($currentTask['UF_PRODUCT_XML_ID']) || empty($currentTask['UF_PRODUCT_XML_ID']) || ($currentTask['UF_PRODUCT_XML_ID'] === null)) {
            return true;
        }

        foreach ($currentTask['UF_PRODUCT_XML_ID'] as $productXmlId) {
            if ($productXmlId === $offer->getXmlId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param QuestQuestionRequest $questQuestionRequest
     * @return bool
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function checkQuestionTask(QuestQuestionRequest $questQuestionRequest): bool
    {
        $userResult = $this->getUserResult();

        if ($userResult === false) {
            throw new ApiRuntimeException('Начните проходить квест');
        }

        $userTasks = unserialize($userResult['UF_TASKS']);

        if (!isset($userTasks[$userResult['UF_CURRENT_TASK']]['ID'])) {
            throw new ApiRuntimeException('Задание не найдено');
        }

        if ($userTasks[$userResult['UF_CURRENT_TASK']]['BARCODE_COMPLETE'] === false) {
            throw new ApiRuntimeException('Выполните предыдущее задание');
        }

        $currentTask = $this->getCurrentTask();

        $userAnswer = null;

        foreach ($currentTask['UF_VARIANTS'] as $key => $variant) {
            if ($questQuestionRequest->getVariantId() === $key) {
                $userAnswer = $variant;
            }
        }

        if ($userAnswer === null) {
            throw new ApiRuntimeException('Не найден вариант ответа');
        }

        $correctAnswer = ($userAnswer === $currentTask['UF_ANSWER']);

        $userTasks[$userResult['UF_CURRENT_TASK']]['QUESTION_RESULT'] = ($correctAnswer) ? QuestionTask::STATUS_SUCCESS_COMPLETE : QuestionTask::STATUS_FAIL_COMPLETE;

        $userResult['UF_TASKS'] = serialize($userTasks);
        ++$userResult['UF_CURRENT_TASK'];

        $this->updateCurrentUserResult($userResult);

        return $correctAnswer;
    }

    /**
     * @param $prizeId
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function choosePrize($prizeId): void
    {
        $userResult = $this->getUserResult();

        if (!$this->isFinishStep($userResult)) {
            throw new AccessDeinedException('Вы не прошли квест до конца');
        }

        /** @var Prize $prize */
        $prize = current($this->getPrizes([$prizeId]));

        if (!$prize) {
            throw new ApiRuntimeException('Выбранный приз не найден');
        }

        $userResult['UF_PROMOCODE'] = $this->getNewPromocode($prize->getId());
        $userResult['UF_PRIZE'] = $prize->getId();

        $this->updateCurrentUserResult($userResult);
    }

    /**
     * @return string
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getUserPromocode(): string
    {
        $userResult = $this->getUserResult();

        if (!$this->isFinishStep($userResult)) {
            throw new AccessDeinedException('Вы не прошли квест до конца');
        }

        if (empty($userResult['UF_PRIZE']) || ($userResult['UF_PRIZE'] === null)) {
            throw new ApiRuntimeException('Выберите приз');
        }

        if (empty($userResult['UF_PROMOCODE']) || ($userResult['UF_PROMOCODE'] === null)) {
            throw new ApiRuntimeException('Промокод не найден');
        }

        return $userResult['UF_PROMOCODE'];
    }

    /**
     * @return Prize|null
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getUserPrize(): ?Prize
    {
        $userResult = $this->getUserResult();

        if ($userResult['UF_PRIZE'] === null) {
            return null;
        }

        /** @var Prize $userPrize */
        $userPrize = current($this->getPrizes([$userResult['UF_PRIZE']]));

        if (!$userPrize) {
            return null;
        }

        return $userPrize;
    }

    /**
     * @param $prizeId
     * @return string
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getNewPromocode($prizeId): string
    {
        $res = $this->getDataManager(self::PROMOCODE_HL_NAME)::query()
            ->setFilter(['UF_ACTIVE' => 1, 'UF_PRIZE' => $prizeId])
            ->setSelect(['ID', 'UF_PROMOCODE'])
            ->exec();

        if ($arPromocode = $res->fetch()) {
            $updateResult = $this->getDataManager(self::PROMOCODE_HL_NAME)::update($arPromocode['ID'], ['UF_ACTIVE' => 0]);
            if ($updateResult->isSuccess()) {
                return $arPromocode['UF_PROMOCODE'];
            }

            throw new ApiRuntimeException('При присваивании промокода произошла ошибка');
        }

        throw new ApiRuntimeException('Промокоды кончились');
    }

    /**
     * @return User
     *
     * @throws AccessDeinedException
     */
    public function getCurrentUser(): User
    {
        if ($this->currentUser === null) {
            try {
                $this->currentUser = $this->apiUserService->getCurrentApiUser();
            } catch (Exception $e) {
            }

            if ($this->currentUser === null) {
                throw new AccessDeinedException('Авторизуйтесь для участия в квесте');
            }
        }

        return $this->currentUser;
    }

    /**
     * @param bool $reload
     * @return array|null
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getUserResult(bool $reload = false): ?array
    {
        if ($this->currentUserResult === null || $reload) {
            $result = $this->getDataManager(self::RESULT_HL_NAME)::query()
                ->setFilter(['=UF_USER_ID' => $this->getCurrentUser()->getId()])
                ->setSelect(['ID', 'UF_PET', 'UF_TASKS', 'UF_CURRENT_TASK', 'UF_PRIZE', 'UF_PROMOCODE'])
                ->exec()
                ->fetch();

            if ($result !== false) {
                $this->currentUserResult = $result;
            }
        }

        return $this->currentUserResult;
    }

    /**
     * @param $userResult
     *
     * @throws Exception
     */
    protected function updateCurrentUserResult($userResult): void
    {
        $updateResult = $this->getDataManager(self::RESULT_HL_NAME)::update($userResult['ID'], $userResult);
        if ($updateResult->isSuccess()) {
            $this->currentUserResult = $userResult;
        }
    }

    /**
     * @return array|false
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getCurrentTask(): array
    {
        $userResult = $this->getUserResult();

        if ($userResult === false) {
            throw new ApiRuntimeException('Начните проходить квест');
        }

        $userTasks = unserialize($userResult['UF_TASKS']);

        if (!isset($userTasks[$userResult['UF_CURRENT_TASK']]['ID'])) {
            throw new ApiRuntimeException('Задание не найдено');
        }

        $taskId = $userTasks[$userResult['UF_CURRENT_TASK']]['ID'];

        $cacheFinder = function () use ($taskId) {
            return $this->getDataManager(self::TASK_HL_NAME)::query()
                ->setSelect(self::TASK_SELECT)
                ->setFilter(['=ID' => $taskId])
                ->exec()
                ->fetch();
        };

        $currentTask = (new BitrixCache())
            ->withTag('quest_task')
            ->withTime(360000)
            ->withId(__METHOD__ . serialize(['taskId' => $taskId]))
            ->resultOf($cacheFinder);

        if ($currentTask === false) {
            throw new ApiRuntimeException('Задание не найдено');
        }

        return $currentTask;
    }

    /**
     * @return BarcodeTask
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     * @throws Exception
     */
    public function getCurrentBarcodeTask(): BarcodeTask
    {
        $currentTask = $this->getCurrentTask();

        $image = null;
        if ($currentTask['UF_IMAGE']) {

            $item = FileTable::query()->addFilter('=ID', $currentTask['UF_IMAGE'])->addSelect('*')->exec()->fetch();
            if ($item === false) {
                $item = null;
            } else {
                $image = (new Image($item))->getSrc();
            }
        }

        return (new BarcodeTask())
            ->setTask($currentTask['UF_TASK'])
            ->setTitle($currentTask['UF_TITLE'])
            ->setImage($image);
    }

    /**
     * @return QuestionTask
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getCurrentQuestionTask(): QuestionTask
    {
        $currentTask = $this->getCurrentTask();
        $variants = [];

        foreach ($currentTask['UF_VARIANTS'] as $key => $variant) {
            $variants[] = (new AnswerVariant())
                ->setId($key)
                ->setTitle($variant);
        }

        shuffle($variants);

        return (new QuestionTask())
            ->setQuestion($currentTask['UF_QUESTION'])
            ->setVariants($variants);
    }

    /**
     * @return QuestStatus
     *
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getQuestStatus(): QuestStatus
    {
        $userResult = $this->getUserResult();

        $taskResult = unserialize($userResult['UF_TASKS']);

        $prevResult = [];

        foreach ($taskResult as $result) {
            if ($result['QUESTION_RESULT'] === QuestionTask::STATUS_SUCCESS_COMPLETE) {
                $prevResult[] = true;
            } else if ($result['QUESTION_RESULT'] === QuestionTask::STATUS_FAIL_COMPLETE) {
                $prevResult[] = false;
            }
        }

        return (new QuestStatus())
            ->setNumber($userResult['UF_CURRENT_TASK'])
            ->setTotalCount(count($taskResult))
            ->setPrevTasks($prevResult);
    }

    /**
     * @param $userResult
     * @return bool
     */
    public function isFinishStep($userResult): bool
    {
        if (($userResult['UF_PET'] !== null) && ($userResult['UF_TASKS'] !== null)) {

            foreach (unserialize($userResult['UF_TASKS']) as $userTask) {
                if ($userTask['QUESTION_RESULT'] === QuestionTask::STATUS_NOT_START) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $userResult
     * @return int
     */
    public function getCorrectAnswers($userResult): int
    {
        $correctAnswers = 0;
        foreach (unserialize($userResult['UF_TASKS']) as $userTask) {
            if ($userTask['QUESTION_RESULT'] === QuestionTask::STATUS_SUCCESS_COMPLETE) {
                ++$correctAnswers;
            }
        }

        return $correctAnswers;
    }

    /**
     * @param $userResult
     * @return int
     */
    public function getTotalTaskCount($userResult): int
    {
        return count(unserialize($userResult['UF_TASKS']));
    }

    /**
     * @param array $petTypeId
     * @return Pet[]
     *
     * @throws Exception
     */
    public function getPetTypes(array $petTypeId = []): array
    {
        $cacheFinder = function () use ($petTypeId) {
            $result = [];
            $pets = [];
            $imageIds = [];
            $prizeIds = [];

            $res = $this->getDataManager(self::PET_HL_NAME)::query()
                ->setSelect(['ID', 'UF_NAME', 'UF_IMAGE', 'UF_DESCRIPTION', 'UF_PRIZE']);

            if ($petTypeId && !empty($petTypeId)) {
                $res->setFilter(['ID' => $petTypeId]);
            }

            $res->exec();

            foreach ($res->fetchAll() as $pet) {
                $pets[$pet['ID']] = $pet;

                if ($pet['UF_IMAGE']) {
                    $imageIds[] = $pet['UF_IMAGE'];
                }

                if ($pet['UF_PRIZE']) {
                    foreach ($pet['UF_PRIZE'] as $prizeId) {
                        $prizeIds[] = $prizeId;
                    }
                }
            }

            $imageCollection = ImageCollection::createFromIds($imageIds);

            $prizes = $this->getPrizes($prizeIds);

            foreach ($pets as $pet) {
                $petPrizes = [];
                foreach ($pet['UF_PRIZE'] as $prizeId) {
                    if (isset($prizes[$prizeId])) {
                        $petPrizes[] = $prizes[$prizeId];
                    }
                }

                $result[$pet['ID']] = (new Pet())
                    ->setId($pet['ID'])
                    ->setTitle($pet['UF_NAME'])
                    ->setDescription($pet['UF_DESCRIPTION'])
                    ->setImage($this->getImageFromCollection($pet['UF_IMAGE'], $imageCollection))
                    ->setPrizes($petPrizes);
            }

            return $result;
        };

        try {
            return (new BitrixCache())
                ->withTag('quest_pets')
                ->withTime(360000)
                ->withId(__METHOD__ . serialize(['petTypeIds' => $petTypeId]))
                ->resultOf($cacheFinder);
        } catch (Exception $e) {
            $this->log()->error(sprintf('failed to get pets for quest: %s', $e->getMessage()), [
                'pet type ids' => var_export($petTypeId, true),
            ]);
            return [];
        }
    }

    /**
     * @param array $prizeIds
     * @return Prize[]
     *
     * @throws Exception
     */
    public function getPrizes(array $prizeIds = []): array
    {
        $cacheFinder = function () use ($prizeIds) {
            $result = [];
            $prizes = [];
            $imageIds = [];

            if ((!$prizeIds || empty($prizeIds))) {
                return [];
            }

            $res = $this->getDataManager(self::PRIZE_HL_NAME)::query()
                ->setFilter(['=ID' => $prizeIds])
                ->setSelect(['ID', 'UF_NAME', 'UF_IMAGE', 'UF_PRODUCT_XML_ID'])
                ->exec();

            foreach ($res as $prize) {
                if ($prize['UF_IMAGE']) {
                    $imageIds[] = $prize['UF_IMAGE'];
                }

                $prizes[$prize['ID']] = $prize;
            }

            $imageCollection = ImageCollection::createFromIds($imageIds);

            foreach ($prizes as $prize) {
                $result[$prize['ID']] = (new Prize())
                    ->setId($prize['ID'])
                    ->setName($prize['UF_NAME'])
                    ->setImage($this->getImageFromCollection($prize['UF_IMAGE'], $imageCollection))
                    ->setXmlId($prize['UF_PRODUCT_XML_ID']);
            }

            return $result;
        };

        try {
            return (new BitrixCache())
                ->withTag('quest_prizes')
                ->withTime(360000)
                ->withId(__METHOD__ . serialize(['prizeIds' => $prizeIds]))
                ->resultOf($cacheFinder);
        } catch (Exception $e) {
            $this->log()->error(sprintf('failed to get pri for quest: %s', $e->getMessage()), [
                'prizes ids' => var_export($prizeIds, true),
            ]);
            return [];
        }
    }

    /**
     * @param $imageId
     * @param $imageCollection
     * @return ImageInterface|null
     *
     */
    protected function getImageFromCollection($imageId, $imageCollection): ?ImageInterface
    {
        if (!$imageId || ($imageId === null)) {
            return null;
        }

        return $this->imageProcessor->findImage($imageId, $imageCollection) ?: null;
    }

    /**
     * @param $petTypeId
     * @return array
     *
     * @throws Exception
     */
    protected function generateTasks($petTypeId): array
    {
        $cacheFinder = function () use ($petTypeId) {
            $res = $this->getDataManager(self::TASK_HL_NAME)::query()
                ->setFilter(['=UF_PET' => $petTypeId])
                ->setSelect(['ID'])
                ->exec();

            $tasks = [];
            foreach ($res as $task) {
                $tasks[] = [
                    'ID' => $task['ID'],
                    'BARCODE_COMPLETE' => false,
                    'QUESTION_RESULT' => QuestionTask::STATUS_NOT_START
                ];
            }

            return $tasks;
        };

        $tasks = (new BitrixCache())
            ->withTag('quest_pet_tasks')
            ->withTime(360000)
            ->withId(__METHOD__ . serialize(['pet type id' => $petTypeId]))
            ->resultOf($cacheFinder);


        shuffle($tasks);
        $number = 1;

        $result = [];

        foreach ($tasks as $task) {
            $result[$number] = $task;
            $number++;
        }

        return $result;
    }

    /**
     * @param $entityName
     * @return DataManager
     *
     * @throws Exception
     */
    protected function getDataManager($entityName): DataManager
    {
        if (!isset($this->dataManagers[$entityName])) {
            $this->dataManagers[$entityName] = HLBlockFactory::createTableObject($entityName);
        }

        return $this->dataManagers[$entityName];
    }

    /**
     * @param $userResult
     * @return string
     */
    public function getCorrectText($userResult): string
    {
        return sprintf(self::CORRECT_ANSWERS, $this->getCorrectAnswers($userResult), $this->getTotalTaskCount($userResult));
    }

    public function getPrizeText($userResult): string
    {
        $prizeText = ($this->getCorrectAnswers($userResult) < self::MIN_CORRECT_ANSWERS) ? self::MIN_CORRECT_ANSWERS_TEXT : '';
        $prizeText .= 'Выберите Ваш заслуженный подарок';

        return $prizeText;
    }
}
