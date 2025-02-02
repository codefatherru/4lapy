<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\FoodSelectionBundle\Service;

use Adv\Bitrixtools\Exception\IblockNotFoundException;
use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use FourPaws\BitrixOrm\Model\IblockElement;
use FourPaws\BitrixOrm\Model\IblockSect;
use FourPaws\Catalog\Model\Product;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;
use FourPaws\FoodSelectionBundle\Repository\FoodSelectionRepository;

/**
 * Class FoodSelectionService
 *
 * @package FourPaws\FoodSelectionBundle\Service
 */
class FoodSelectionService
{
    /**
     * @var FoodSelectionRepository
     */
    private $foodSelectionRepository;

    private $iblockId;

    /**
     * FoodSelectionService constructor.
     *
     * @param FoodSelectionRepository $foodSelectionRepository
     *
     * @throws IblockNotFoundException
     */
    public function __construct(FoodSelectionRepository $foodSelectionRepository)
    {
        $this->foodSelectionRepository = $foodSelectionRepository;
        $this->iblockId = IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::FOOD_SELECTION);
    }

    /**
     * @param array $params
     *
     * @return array|IblockElement[]
     */
    public function getItems(array $params = []): array
    {
        if (!isset($params['filter']['IBLOCK_ID'])) {
            $params['filter']['IBLOCK_ID'] = $this->iblockId;
        }

        return $this->foodSelectionRepository->getItems($params);
    }

    /**
     * @param int $parentSectionID
     *
     * @return array|IblockSect[]
     */
    public function getSectionsByParentSectionId(
        int $parentSectionID
    ): array {
        $filter = ['=SECTION_ID' => $parentSectionID];

        return $this->getSections(['filter' => $filter]);
    }

    /**
     * @param array $params
     *
     * @return array|IblockSect[]
     */
    public function getSections(array $params = []): array
    {
        if (!isset($params['filter']['IBLOCK_ID'])) {
            $params['filter']['IBLOCK_ID'] = $this->iblockId;
        }
        $params['order'] = ['SORT' => 'asc', 'NAME' => 'asc'];

        return $this->foodSelectionRepository->getSections($params);
    }

    /**
     * @param string $xmlId
     * @param int    $depthLvl
     *
     * @return int|null
     */
    public function getSectionIdByXmlId(string $xmlId, int $depthLvl = 0): ?int
    {
        $res = $this->getSectionsByXmlId($xmlId, $depthLvl);
        if (\is_array($res) && !empty($res)) {
            return current($res)->getId();
        }

        return null;
    }

    /**
     * @param     $xmlId
     * @param int $depthLvl
     *
     * @return array
     */
    public function getSectionIdsByXmlId($xmlId, int $depthLvl = 0): array
    {
        $res = $this->getSectionsByXmlId($xmlId, $depthLvl);
        if (\is_array($res)) {
            $ids = [];
            foreach ($res as $IblockSect) {
                $ids[$IblockSect->getId()] = $IblockSect->getXmlId();
            }
            return $ids;
        }

        return [];
    }

    /**
     * @param     $xmlId
     * @param int $depthLvl
     *
     * @return IblockSect|null
     */
    public function getSectionByXmlId($xmlId, int $depthLvl = 0): ?IblockSect
    {
        $res = $this->getSectionsByXmlId($xmlId, $depthLvl);
        if (\is_array($res)) {
            return current($res);
        }

        return null;
    }

    /**
     * @param     $xmlId
     * @param int $depthLvl
     *
     * @return array|IblockSect[]|null
     */
    public function getSectionsByXmlId($xmlId, int $depthLvl = 0): ?array
    {
        if (!\is_array($xmlId)) {
            $xmlId = (string)$xmlId;
            if (!\is_string($xmlId) || empty($xmlId)) {
                return null;
            }
        }
        if (!empty($xmlId)) {
            $filter = ['XML_ID' => $xmlId];
        }
        if ($depthLvl > 0) {
            $filter['DEPTH_LEVEL'] = $depthLvl;
        }
        $items = $this->getSections(['filter' => $filter]);
        if (!empty($items)) {
            return $items;
        }

        return null;
    }

    /**
     * @param array $sections
     *
     * @param array $exceptionItems
     *
     * @param int   $limit
     *
     * @param array $excludeSections
     *
     * @return array|Product[]
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public function getProductsBySections(
        array $sections,
        array $exceptionItems = [],
        int $limit = 6,
        array $excludeSections = []
    ): array {
        return $this->foodSelectionRepository->getProductsBySections($sections, $this->iblockId, $exceptionItems,
            $limit, $excludeSections);
    }
}
