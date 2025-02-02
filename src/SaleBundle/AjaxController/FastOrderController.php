<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SaleBundle\AjaxController;

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\Delivery\Services\Table as DeliveryTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Order;
use Exception;
use FourPaws\App\Application as App;
use FourPaws\App\Response\JsonResponse;
use FourPaws\App\Response\JsonSuccessResponse;
use FourPaws\AppBundle\Service\AjaxMess;
use FourPaws\EcommerceBundle\Preset\Bitrix\SalePreset;
use FourPaws\EcommerceBundle\Service\GoogleEcommerceService;
use FourPaws\EcommerceBundle\Service\RetailRocketService;
use FourPaws\Helpers\Exception\WrongPhoneNumberException;
use FourPaws\Helpers\PhoneHelper;
use FourPaws\Helpers\ProtectorHelper;
use FourPaws\SaleBundle\Entity\OrderStorage;
use FourPaws\SaleBundle\Exception\BaseExceptionInterface;
use FourPaws\SaleBundle\Exception\DeliveryNotAvailableException;
use FourPaws\SaleBundle\Exception\OrderCreateException;
use FourPaws\SaleBundle\Service\BasketService;
use FourPaws\SaleBundle\Service\BasketViewService;
use FourPaws\SaleBundle\Service\OrderService;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Service\StoreService;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use FourPaws\UserBundle\Service\UserAuthorizationInterface;
use FourPaws\UserBundle\Service\UserCitySelectInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SplObjectStorage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use FourPaws\ReCaptchaBundle\Service\ReCaptchaInterface;
use FourPaws\DeliveryBundle\Service\DeliveryService;


/**
 * Class FastOrderController
 *
 * @package FourPaws\SaleBundle\AjaxController
 * @Route("/fast_order")
 */
class FastOrderController extends Controller
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var UserAuthorizationInterface
     */
    private $userAuthProvider;

    /** @var CurrentUserProviderInterface */
    private $currentUserProvider;
    /** @var UserCitySelectInterface */
    private $citySelectProvider;
    /** @var AjaxMess */
    private $ajaxMess;
    /** @var BasketService */
    private $basketService;
    /** @var BasketViewService */
    private $basketViewService;
    /** @var StoreService */
    private $storeService;
    /**
     * @var GoogleEcommerceService
     */
    private $ecommerceService;
    /**
     * @var SalePreset
     */
    private $salePreset;
    /**
     * @var GoogleEcommerceService
     */
    private $retailRocketService;

    /**
     * OrderController constructor.
     *
     * @param OrderService                 $orderService
     * @param UserAuthorizationInterface   $userAuthProvider
     * @param CurrentUserProviderInterface $currentUserProvider
     * @param UserCitySelectInterface      $citySelectProvider
     * @param AjaxMess                     $ajaxMess
     * @param BasketService                $basketService
     * @param BasketViewService            $basketViewService
     * @param StoreService                 $storeService
     * @param GoogleEcommerceService       $ecommerceService
     * @param RetailRocketService          $retailRocketService
     * @param SalePreset                   $salePreset
     */
    public function __construct(
        OrderService $orderService,
        UserAuthorizationInterface $userAuthProvider,
        CurrentUserProviderInterface $currentUserProvider,
        UserCitySelectInterface $citySelectProvider,
        AjaxMess $ajaxMess,
        BasketService $basketService,
        BasketViewService $basketViewService,
        StoreService $storeService,
        GoogleEcommerceService $ecommerceService,
        RetailRocketService $retailRocketService,
        SalePreset $salePreset
    )
    {
        $this->orderService = $orderService;
        $this->userAuthProvider = $userAuthProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->citySelectProvider = $citySelectProvider;
        $this->ajaxMess = $ajaxMess;
        $this->basketService = $basketService;
        $this->basketViewService = $basketViewService;
        $this->storeService = $storeService;
        $this->ecommerceService = $ecommerceService;
        $this->retailRocketService = $ecommerceService;
        $this->salePreset = $salePreset;
    }

    /**
     * @Route("/load/", methods={"GET"})
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     * @throws ArgumentNullException
     * @throws ArgumentException
     */
    public function loadAction(Request $request): JsonResponse
    {
        $addData = [];
        $requestType = $request->get('type', 'basket');
        if ($requestType === 'card') {
            /**
             * add to basket
             *
             * @see \FourPaws\SaleBundle\AjaxController\BasketController
             */
            $offerId = (int)$request->get('offerId', 0);

            if ($offerId === 0) {
                $offerId = (int)$request->get('offerid', 0);
            }

            $quantity = (int)$request->get('quantity', 1);

            try {
                $basketItem = $this->basketService->addOfferToBasket($offerId, $quantity);

                $addData = [
                    'miniBasket' => $this->basketViewService->getMiniBasketHtml(true),
                ];

                $temporaryItem = $basketItem->createClone(new SplObjectStorage());
                $temporaryItem->setFieldNoDemand('QUANTITY', $quantity);
                $addData['command'] = $this->ecommerceService->renderScript(
                    $this->salePreset->createAddFromBasketItem($temporaryItem),
                    false
                );

            } catch (BaseExceptionInterface $e) {
                return $this->ajaxMess->getSystemError();
            } catch (LoaderException | ObjectNotFoundException | \RuntimeException $e) {
                $logger = LoggerFactory::create('system');
                $logger->critical('Ошибка загрузки сервисов - ' . $e->getMessage());

                return $this->ajaxMess->getSystemError();
            }
        }

        // Актуализация остатков
        $this->basketService->getBasket(true);

        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            'fourpaws:fast.order',
            '',
            [
                'TYPE'         => 'innerForm',
                'REQUEST_TYPE' => $requestType,
            ],
            null,
            ['HIDE_ICONS' => 'Y']
        );
        $html = ob_get_clean();

        $data = ['html' => $html];

        if ($addData) {
            $data = \array_merge($data, $addData);
        }

        return JsonSuccessResponse::createWithData('подгружено', $data);
    }

    /**
     * @Route("/create/", methods={"GET"})
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function createAction(Request $request): JsonResponse
    {

        if (!ProtectorHelper::checkToken($request->get(ProtectorHelper::getField(ProtectorHelper::TYPE_FAST_ORDER_CREATE)), ProtectorHelper::TYPE_FAST_ORDER_CREATE)) {
            return $this->ajaxMess->getOrderCreateError('Оформление быстрого заказа невозможно, пожалуйста обратитесь к администратору или попробуйте полный процесс оформления');
        }

        /** @var \FourPaws\ReCaptchaBundle\Service\ReCaptchaService $recaptchaService */
        $recaptchaService = App::getInstance()->getContainer()->get(ReCaptchaInterface::class);

        if (!$recaptchaService->checkCaptcha($request->get('g-recaptcha-response'))) {
            return $this->ajaxMess->getOrderCreateError('Оформление быстрого заказа невозможно, пожалуйста обратитесь к администратору или попробуйте полный процесс оформления');
        }

        $orderStorage = new OrderStorage();

        try {
            $phone = PhoneHelper::normalizePhone($request->get('phone', ''));
        } catch (WrongPhoneNumberException $e) {
            return $this->ajaxMess->getWrongPhoneNumberException();
        }

        $name = $request->get('name', '');

        $selectedCity = $this->citySelectProvider->getSelectedCity();
        $orderStorage
            ->setSplit(false)
            ->setFastOrder(true)// быстрый заказ теперь определяется через storage
            ->setPhone($phone)
            ->setName($name)
            ->setFuserId($this->currentUserProvider->getCurrentFUserId())
            /** оплата наличными при доставке ставим всегда */
            ->setPaymentId(PaySystemActionTable::query()
                                               ->setSelect(['ID'])
                                               ->setFilter(['CODE' => 'cash'])
                                               ->setCacheTtl(360000)
                                               ->exec()
                                               ->fetch()['ID'])
            ->setDeliveryId(DeliveryTable::query()
                                         ->setSelect(['ID'])
                                         ->setFilter(['CODE' => DeliveryService::INNER_DELIVERY_CODE])
                                         ->setCacheTtl(360000)
                                         ->exec()
                                         ->fetch()['ID'])
            ->setCity($selectedCity['NAME'])
            ->setCityCode($selectedCity['CODE']);

        if ($this->userAuthProvider->isAuthorized()) {
            try {
                $user = $this->currentUserProvider->getCurrentUser();
                $orderStorage->setEmail($user->getEmail());
                $orderStorage->setUserId($user->getId());
            } catch (NotAuthorizedException $e) {
                /** никогда не сработает */
            } catch (InvalidIdentifierException|ConstraintDefinitionException $e) {
                $logger = LoggerFactory::create('params');
                $logger->error('Ошибка параметров - ' . $e->getMessage());
            }
        }

        try {
            $order = $this->orderService->initOrder($orderStorage);
            $this->orderService->saveOrder($order, $orderStorage);
            if ($order instanceof Order && $order->getId() > 0) {
                if ($request->get('type', 'basket') === 'card') {
                    ob_start();
                    require_once App::getDocumentRoot()
                                 . '/local/components/fourpaws/fast.order/templates/.default/success.php';
                    $html = ob_get_clean();

                    $data = [
                        'html'       => $html,
                        'miniBasket' => $this->basketViewService->getMiniBasketHtml(),
                    ];

                    $ecommerce =
                        \sprintf(
                            "%s\n%s",
                            $this->ecommerceService->renderScript(
                                $this->salePreset->createPurchaseFromBitrixOrder($order, 'Покупка в 1 клик')
                            ),
                            $this->retailRocketService->renderOrderTransaction(
                                $this->salePreset->createRetailRocketTransactionFromBitrixOrder($order)
                            )
                        );

                    if ($ecommerce) {
                        $data['command'] = $ecommerce;
                    }

                    return JsonSuccessResponse::createWithData('Быстрый заказ успешно создан', $data);
                }

                return JsonSuccessResponse::create(
                    'Быстрый заказ успешно создан',
                    200,
                    [],
                    [
                        'redirect' => \sprintf('/cart/successFastOrder.php?orderId=%d', $order->getId())
                    ]
                );
            }
        } catch (ArgumentOutOfRangeException|ArgumentTypeException|ArgumentException $e) {
            $logger = LoggerFactory::create('params');
            $logger->error('Ошибка параметров - ' . $e->getMessage());

            return $this->ajaxMess->getSystemError();
        } catch (DeliveryNotAvailableException $e) {
            return $this->ajaxMess->getOrderCreateError('Доставка выбранных позиций в вашем регионе недоступна, пожалуйста попробуйте заказать другие товары или дождитесь появления данных товаров в вашем регионе');
        } catch (OrderCreateException $e) {
            return $this->ajaxMess->getOrderCreateError('Оформление быстрого заказа невозможно, пожалуйста обратитесь к администратору или попробуйте полный процесс оформления');
        } catch (NotImplementedException | NotSupportedException | ObjectNotFoundException | Exception $e) {
            $logger = LoggerFactory::create('system');
            $logger->error('Системная ошибка - ' . $e->getMessage());

            return $this->ajaxMess->getSystemError();
        }

        return $this->ajaxMess->getOrderCreateError();
    }
}
