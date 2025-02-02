<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\LocationBundle\AjaxController;

use Adv\Bitrixtools\Exception\IblockNotFoundException;
use Exception;
use FourPaws\App\Response\JsonSuccessResponse;
use FourPaws\LocationBundle\LocationService;
use FourPaws\LocationBundle\Service\YandexGeocodeService;
use FourPaws\MobileApiBundle\Services\Api\CityService as ApiCityService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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

    /** @var ApiCityService */
    private $apiCityService;

    /** @var YandexGeocodeService */
    private $yandexGeocodeService;

    /**
     * CityController constructor.
     * @param LocationService $locationService
     * @param ApiCityService $apiCityService
     * @param YandexGeocodeService $yandexGeocodeService
     */
    public function __construct(LocationService $locationService, ApiCityService $apiCityService, YandexGeocodeService $yandexGeocodeService)
    {
        $this->locationService = $locationService;
        $this->apiCityService = $apiCityService;
        $this->yandexGeocodeService = $yandexGeocodeService;
    }

    /**
     * @Route(
     *     "/list/",
     *      methods={"GET"},
     *      name="location.city.list"
     * )
     *
     * @return JsonResponse
     * @throws ServiceCircularReferenceException
     * @throws IblockNotFoundException
     * @throws Exception
     * @throws ServiceNotFoundException
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
     * @return JsonResponse
     * @throws ServiceCircularReferenceException
     * @throws Exception
     * @throws ServiceNotFoundException
     */
    public function citySelectAction(): JsonResponse
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

    /**
     * @Route("/suggest/address", methods={"POST"}, name="location.city.suggest.address")
     * @param Request $request
     * @return JsonResponse
     */
    public function getAddress(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent());

        $query = $content->query;
        $limit = intval($content->count);

        /**
         * Баг, когда местоположения имеют одинаковые названия
         * Специально фейлим запрос, чтобы инфромация о выбранном городе взялась в фронта, а не из этого запроса
         */
        if ($limit === 1) {
            return new JsonResponse([]);
        }

        $exact = $limit === 1;
        $filter = [($exact ? '=' : '?') . 'NAME.NAME_UPPER' => ToUpper($query)];

        $locations = $this->locationService->findLocationNew($filter, $limit, true, true, true);

        $result = $this->apiCityService->convertInDadataFormat($locations);

        return new JsonResponse([
            'suggestions' => $result,
        ]);
    }

    /**
     * @Route("/geocode/", methods={"GET"})
     *
     * @param Request $request
     * @return \FourPaws\App\Response\JsonResponse
     */
    public function geocodeAction(Request $request): JsonResponse
    {
        try {
            $coords = $this->yandexGeocodeService->getCityCoords($request->get('city', ''));
        } catch (\Exception $e) {
            $coords = YandexGeocodeService::DEFAULT_COORDS;
        }

        return new JsonSuccessResponse(['coords' => $coords]);
    }

    /**
     * @Route("/suggest/address", methods={"OPTIONS"})
     */
    public function getAddressOption(): JsonResponse
    {
        return new JsonResponse([]);
    }

    /**
     * @Route("/status/address", methods={"GET"})
     */
    public function getAddressGet(): JsonResponse
    {
        return new JsonResponse([]);
    }
}
