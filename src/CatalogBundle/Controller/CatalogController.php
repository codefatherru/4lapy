<?php

namespace FourPaws\CatalogBundle\Controller;

use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Bitrix\Main\Entity\DataManager;
use Exception;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\Catalog\Query\CategoryQuery;
use FourPaws\CatalogBundle\Dto\ChildCategoryRequest;
use FourPaws\CatalogBundle\Dto\RootCategoryRequest;
use FourPaws\CatalogBundle\Dto\SearchRequest;
use FourPaws\CatalogBundle\Exception\RuntimeException as CatalogRuntimeException;
use FourPaws\CatalogBundle\Service\CatalogLandingService;
use FourPaws\EcommerceBundle\Service\DataLayerService;
use FourPaws\EcommerceBundle\Service\GoogleEcommerceService;
use FourPaws\EcommerceBundle\Service\RetailRocketService;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;
use FourPaws\Search\Model\ProductSearchResult;
use FourPaws\Search\SearchService;
use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * Class CatalogController
 *
 * @package FourPaws\CatalogBundle\Controller
 *
 * @Route("/catalog")
 */
class CatalogController extends Controller
{
    /**
     * @var SearchService
     */
    private $searchService;
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var GoogleEcommerceService
     */
    private $ecommerceService;
    /**
     * @var CatalogLandingService
     */
    private $landingService;
    /**
     * @var RetailRocketService
     */
    private $retailRocketService;
    /**
     * @var DataLayerService
     */
    private $dataLayerService;

    /**
     * CatalogController constructor.
     *
     * @param SearchService          $searchService
     * @param ValidatorInterface     $validator
     * @param GoogleEcommerceService $ecommerceService
     * @param CatalogLandingService  $landingService
     * @param RetailRocketService    $retailRocketService
     * @param DataLayerService       $dataLayerService
     */
    public function __construct(SearchService $searchService, ValidatorInterface $validator, GoogleEcommerceService $ecommerceService, CatalogLandingService $landingService, RetailRocketService $retailRocketService, DataLayerService $dataLayerService)
    {
        $this->searchService = $searchService;
        $this->validator = $validator;
        $this->ecommerceService = $ecommerceService;
        $this->retailRocketService = $retailRocketService;
        $this->landingService = $landingService;
        $this->dataLayerService = $dataLayerService;
    }

    /** @noinspection MoreThanThreeArgumentsInspection
     *
     * @Route("/search/")
     *
     * @param Request       $request
     * @param SearchRequest $searchRequest
     *
     * @return Response
     *
     * @throws CatalogRuntimeException
     * @throws RuntimeException
     * @throws ApplicationCreateException
     * @throws Exception
     */
    public function searchAction(Request $request, SearchRequest $searchRequest): Response
    {
        $result = null;

        //костыль для заказчика
        $searchString = mb_strtolower($searchRequest->getSearchString());

        $arSelect = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'PROPERTY_TRANSLITS'
        ];

        $arFilter = [
            'IBLOCK_ID' => IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::BRANDS),
            'ACTIVE' => 'Y',
            '!PROPERTY_TRANSLITS' => false
        ];

        $brandFound = false;
        $dbItems = \CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
        while ($arItem = $dbItems->Fetch()) {
            if (!empty($arItem['PROPERTY_TRANSLITS_VALUE'])) {
                $arTranslits = explode(',', $arItem['PROPERTY_TRANSLITS_VALUE']);
                foreach ($arTranslits as $translit) {
                    $translit = mb_strtolower(trim($translit));
                    if (mb_strpos($searchString, $translit) !== false) {
                        $searchString = str_replace($translit,
                            mb_strtolower($arItem['NAME']), $searchString);
                        $brandFound = true;
                        break;
                    }
                }
            }
            if ($brandFound) {
                break;
            }
        }

        if (!$this->validator->validate($searchRequest)
                             ->count()) {
            /** @var ProductSearchResult $result */
            $result = $this->searchService->searchProducts(
                $searchRequest->getCategory()->getFilters(),
                $searchRequest->getSorts()->getSelected(),
                $searchRequest->getNavigation(),
                $searchString
            );
        }

        $categories = (new CategoryQuery())
            ->withFilterParameter('SECTION_ID', false)
            ->exec();

        $retailRocketViewScript = $searchRequest->getCategory()->getId()
            ? \sprintf(
                '<script>%s</script>',
                $this->retailRocketService->renderCategoryView($searchRequest->getCategory()->getId())
            )
            : '';

        $tpl = 'FourPawsCatalogBundle:Catalog:search.html.php';

        if ($request->query->get('partial') === 'Y') {
            $tpl = 'FourPawsCatalogBundle:Catalog:search.filter.container.html.php';
        }

        return $this->render($tpl, [
            'request'                => $request,
            'productSearchResult'    => $result,
            'catalogRequest'         => $searchRequest,
            'categories'             => $categories,
            'ecommerceService'       => $this->ecommerceService,
            'dataLayerService'       => $this->dataLayerService,
            'retailRocketViewScript' => $retailRocketViewScript
        ]);
    }

    /**
     * @Route(
     *      "/{path}/",
     *      condition="request.get('landing', null) !== null",
     *      name="category.landing"
     * )
     *
     * @param RootCategoryRequest  $rootCategoryRequest
     * @param ChildCategoryRequest $categoryRequest
     * @param Request              $request
     *
     * @return Response
     */
    public function categoryLandingAction(RootCategoryRequest $rootCategoryRequest, ChildCategoryRequest $categoryRequest, Request $request): Response
    {
        $categoryRequest->setCategory($rootCategoryRequest->getLanding()->setActiveLandingCategory(true));
        $categoryRequest->setCurrentPath($rootCategoryRequest->getLanding()->getSectionPageUrl());

        return $this->forward('FourPawsCatalogBundle:Catalog:childCategory', \compact('request', 'categoryRequest'));
    }

    /**
     * @todo место для вашего Middleware, глубокоуважаемые
     *
     * @param RootCategoryRequest $rootCategoryRequest
     * @param Request             $request
     *
     * @return Response
     * @Route("/{path}/")
     *
     */
    public function filterSetAction(RootCategoryRequest $rootCategoryRequest, Request $request): Response
    {
        if ($rootCategoryRequest->getFilterSetId()) {
            $fSetRequest = Request::create(
                $request->getUriForPath($rootCategoryRequest->getFilterSetTarget())
            );
            $fSetRequest->request->set('filterset', $rootCategoryRequest->getFilterSetId());

            if ($request->query->get('partial') === 'Y') {
                $fSetRequest->query->replace($request->query->all());
            }

            return $this->get('http_kernel')->handle($fSetRequest);
        }

        return $this->forward('FourPawsCatalogBundle:Catalog:rootCategory', \compact('rootCategoryRequest', 'request'));
    }

    /**
     * @Route("/{path}/")
     *
     * @param RootCategoryRequest $rootCategoryRequest
     * @param Request             $request
     *
     * @return Response
     *
     * @throws CatalogRuntimeException
     * @throws ApplicationCreateException
     * @throws Exception
     * @throws RuntimeException
     */
    public function rootCategoryAction(RootCategoryRequest $rootCategoryRequest, Request $request): Response
    {
        $result = $this->searchService->searchProducts(
            $rootCategoryRequest->getCategory()->getFilters(),
            $rootCategoryRequest->getSorts()->getSelected(),
            $rootCategoryRequest->getNavigation(),
            $rootCategoryRequest->getSearchString()
        );

        $retailRocketViewScript = $rootCategoryRequest->getCategory()->getId()
            ? \sprintf(
                '<script>%s</script>',
                $this->retailRocketService->renderCategoryView($rootCategoryRequest->getCategory()->getId())
            )
            : '';

        return $this->render('FourPawsCatalogBundle:Catalog:rootCategory.html.php', \compact('rootCategoryRequest', 'request', 'result', 'retailRocketViewScript'));
    }

    /**
     * @Route("/{path}/", requirements={"path"="[^\.]+(?!\.html)$" })
     *
     * @param Request              $request
     * @param ChildCategoryRequest $categoryRequest
     *
     * @return Response
     *
     * @throws RuntimeException
     * @throws CatalogRuntimeException
     * @throws ApplicationCreateException
     * @throws Exception
     */
    public function childCategoryAction(Request $request, ChildCategoryRequest $categoryRequest): Response
    {
        $category = $categoryRequest->getCategory();

        $result = $this->searchService->searchProducts(
            $category->getFilters(),
            $categoryRequest->getSorts()->getSelected(),
            $categoryRequest->getNavigation(),
            $categoryRequest->getSearchString()
        );

        if ($this->landingService->isLanding($request) && $category->isActiveLandingCategory()) {
            foreach ($categoryRequest->getLandingCollection() as $landing) {
                if ($category->getId() === $landing->getId()) {
                    $landing->setActiveLandingCategory(true);

                    break;
                }
            }
        }

        try {
            $productWithMinPrice = $this->searchService->searchOneWithMinPrice($category->getFilters());
        } catch (Throwable $e) {
            $productWithMinPrice = false;
        }

        $retailRocketViewScript = \sprintf(
            '<script>%s</script>',
            $this->retailRocketService->renderCategoryView($category->getId())
        );

        $tpl = 'FourPawsCatalogBundle:Catalog:catalog.html.php';

        if ($request->query->get('partial') === 'Y') {
            $tpl = 'FourPawsCatalogBundle:Catalog:catalog.filter.container.html.php';
        }

        return $this->render($tpl, [
            'productSearchResult'    => $result,
            'catalogRequest'         => $categoryRequest,
            'ecommerceService'       => $this->ecommerceService,
            'request'                => $request,
            'landingService'         => $this->landingService,
            'dataLayerService'       => $this->dataLayerService,
            'retailRocketViewScript' => $retailRocketViewScript,
            'productWithMinPrice'    => $productWithMinPrice
        ]);
    }
}
