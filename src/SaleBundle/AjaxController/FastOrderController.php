<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\SaleBundle\AjaxController;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale\Order;
use FourPaws\App\Application as App;
use FourPaws\App\Response\JsonResponse;
use FourPaws\App\Response\JsonSuccessResponse;
use FourPaws\AppBundle\Service\AjaxMess;
use FourPaws\SaleBundle\Exception\OrderCreateException;
use FourPaws\SaleBundle\Service\OrderService;
use FourPaws\SaleBundle\Service\OrderStorageService;
use FourPaws\UserBundle\Exception\ConstraintDefinitionException;
use FourPaws\UserBundle\Exception\InvalidIdentifierException;
use FourPaws\UserBundle\Exception\NotAuthorizedException;
use FourPaws\UserBundle\Service\CurrentUserProviderInterface;
use FourPaws\UserBundle\Service\UserAuthorizationInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * @var OrderStorageService
     */
    private $orderStorageService;

    /**
     * @var UserAuthorizationInterface
     */
    private $userAuthProvider;

    /** @var CurrentUserProviderInterface */
    private $currentUserProvider;
    /** @var AjaxMess */
    private $ajaxMess;

    /**
     * OrderController constructor.
     *
     * @param OrderService                 $orderService
     * @param OrderStorageService          $orderStorageService
     * @param UserAuthorizationInterface   $userAuthProvider
     * @param CurrentUserProviderInterface $currentUserProvider
     * @param AjaxMess                     $ajaxMess
     */
    public function __construct(
        OrderService $orderService,
        OrderStorageService $orderStorageService,
        UserAuthorizationInterface $userAuthProvider,
        CurrentUserProviderInterface $currentUserProvider,
        AjaxMess $ajaxMess
    ) {
        $this->orderService = $orderService;
        $this->orderStorageService = $orderStorageService;
        $this->userAuthProvider = $userAuthProvider;
        $this->currentUserProvider = $currentUserProvider;
        $this->ajaxMess = $ajaxMess;
    }

    /**
     * @Route("/load/", methods={"GET"})
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function loadAction(Request $request): JsonResponse
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent(
            'fourpaws:fast.order',
            '',
            ['TYPE' => 'innerForm'],
            null,
            ['HIDE_ICONS' => 'Y']
        );

        return JsonSuccessResponse::createWithData('подгружено', ['html' => ob_get_clean()]);
    }

    /**
     * @Route("/create/", methods={"GET"})
     * @param Request $request
     *
     * @return JsonResponse
     * @throws InvalidIdentifierException
     * @throws ConstraintDefinitionException
     */
    public function createAction(Request $request): JsonResponse
    {
        $orderStorage = $this->orderStorageService->getStorage();
        $phone = $request->get('phone', '');
        $name = $request->get('name', '');

        $orderStorage->setPhone($phone);
        $orderStorage->setName($name);
        $orderStorage->setFuserId($this->currentUserProvider->getCurrentFUserId());

        if ($this->userAuthProvider->isAuthorized()) {
            try {
                $user = $this->currentUserProvider->getCurrentUser();
                $orderStorage->setEmail($user->getEmail());
            } catch (NotAuthorizedException $e) {
                /** никогда не сработает */
            }
        }

        try {
            $order = $this->orderService->createOrder($orderStorage);
        } catch (ArgumentOutOfRangeException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (ArgumentTypeException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (ArgumentException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (NotImplementedException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (NotSupportedException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (ObjectNotFoundException $e) {
            return $this->ajaxMess->getSystemError();
        } catch (OrderCreateException $e) {
            return $this->ajaxMess->getOrderCreateError($e->getMessage());
        } catch (\Exception $e) {
            return $this->ajaxMess->getSystemError();
        }
        if ($order instanceof Order && $order->getId() > 0) {

            ob_start();
            require_once App::getDocumentRoot()
                . '/local/components/fourpaws/fast.order/templates/.default/success.php';
            $html = ob_get_clean();

            return JsonSuccessResponse::createWithData('Быстрый заказ успешно создан', ['html' => $html]);
        }

        return $this->ajaxMess->getOrderCreateError();
    }
}
