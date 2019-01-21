<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\MobileApiBundle\Controller\v0;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FourPaws\MobileApiBundle\Dto\Request\CardActivatedRequest;
use FourPaws\MobileApiBundle\Dto\Response;
use FourPaws\MobileApiBundle\Services\Api\CardService as ApiCardService;

class CardController extends FOSRestController
{
    /**
     * @var ApiCardService
     */
    private $apiCardService;

    public function __construct(ApiCardService $apiCardService)
    {
        $this->apiCardService = $apiCardService;
    }

    /**
     * @param CardActivatedRequest $request
     * @Rest\Get("/card_activated/")
     * @Rest\View()
     *
     * @return Response
     */
    public function isCardActivatedAction(CardActivatedRequest $request): Response
    {
        return $this->apiCardService->isActive($request);
    }
}
