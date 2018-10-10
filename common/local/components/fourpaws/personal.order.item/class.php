<?php

use Adv\Bitrixtools\Tools\Log\LazyLoggerAwareTrait;
use Bitrix\Main\Application as BitrixApplication;
use Bitrix\Main\SystemException;
use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\App\Application;
use FourPaws\Helpers\TaggedCacheHelper;
use FourPaws\PersonalBundle\Entity\Order;
use FourPaws\PersonalBundle\Service\OrderSubscribeService;
use FourPaws\StoreBundle\Service\StoreService;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @noinspection AutoloadingIssuesInspection
 *
 * Class FourPawsPersonalCabinetOrderItemComponent
 */
class FourPawsPersonalCabinetOrderItemComponent extends CBitrixComponent
{
    use LazyLoggerAwareTrait;

    /**
     * @var OrderSubscribeService $orderSubscribeService
     */
    private $orderSubscribeService;

    /**
     * @var StoreService
     */
    private $storeService;

    /**
     * FourPawsPersonalCabinetOrderItemComponent constructor.
     *
     * @param null|\CBitrixComponent $component
     *
     * @throws LogicException
     * @throws SystemException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function __construct($component = null)
    {
        // LazyLoggerAwareTrait не умеет присваивать имя по классам без неймспейса
        // делаем это вручную
        $this->logName = __CLASS__;
        $this->storeService = Application::getInstance()->getContainer()->get('store.service');
        parent::__construct($component);
    }

    /**
     * @param $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params): array
    {
        $params['ORDER'] = $params['ORDER'] ?? null;
        if (!$params['ORDER'] instanceof Order) {
            $params['ORDER'] = null;
        }

        $params['CACHE_TYPE'] = $params['CACHE_TYPE'] ?? 'A';
        $params['CACHE_TIME'] = $params['CACHE_TIME'] ?? 3600;

        // подстраховка для идентификатора кеша
        $params['ORDER_ID'] = $params['ORDER']->getId();

        return parent::onPrepareComponentParams($params);
    }

    /**
     * @return array
     * @throws SystemException
     * @throws Exception
     */
    public function executeComponent()
    {
        /** @var Order $personalOrder */
        $personalOrder = $this->arParams['ORDER'];

        $cachePath = BitrixApplication::getInstance()->getManagedCache()->getCompCachePath(
            $this->getRelativePath()
        );
        // к пути кеша добавляем идентификатор заказа
        $cachePath = $cachePath . '/' . $personalOrder->getId();

        if ($this->startResultCache(false, false, $cachePath)) {
            (new TaggedCacheHelper($cachePath))->addTag('order:item:' . $personalOrder->getId());

            $this->arResult['ORDER'] = $personalOrder;
            $this->arResult['METRO'] = new ArrayCollection($this->storeService->getMetroInfo());

            $this->includeComponentTemplate();
        }

        return $this->arResult;
    }

    /**
     * @return OrderSubscribeService
     * @throws LogicException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function getOrderSubscribeService(): OrderSubscribeService
    {
        if (!$this->orderSubscribeService) {
            $appCont = Application::getInstance()->getContainer();
            $this->orderSubscribeService = $appCont->get('order_subscribe.service');
        }

        return $this->orderSubscribeService;
    }
}
