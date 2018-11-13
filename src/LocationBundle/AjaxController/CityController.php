<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\LocationBundle\AjaxController;

use FourPaws\App\Response\JsonSuccessResponse;
use FourPaws\LocationBundle\LocationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CityController
 * @package FourPaws\LocationBundle\Controller
 * @Route("/city")
 */
class CityController extends Controller
{
    private const MINIMUM_QUERY_LENGTH = 2;

    private const DEFAULT_LIMIT = 10;

    private const MAX_LIMIT = 100;

    /**@var LocationService */
    protected $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * @Route(
     *     "/list/",
     *      methods={"GET"},
     *      name="location.city.list"
     * )
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Exception
     * @return JsonResponse
     */
    public function listAction(): JsonResponse
    {
        return JsonSuccessResponse::createWithData(
            '',
            $this->locationService->getAvailableCities()
        );
    }

    /**
     * @Route(
     *     "/select/list/",
     *      methods={"GET"},
     *      name="location.city.select.list"
     * )
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Adv\Bitrixtools\Exception\IblockNotFoundException
     * @throws \Exception
     * @return JsonResponse
     */
    public function citySelectAction()
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeComponent('fourpaws:city.selector', 'popup', ['GET_STORES' => true], null,
            ['HIDE_ICONS' => 'Y']);
        $html = ob_get_clean();
        return new JsonResponse([
            'html' => $html,
        ]);
    }
}
