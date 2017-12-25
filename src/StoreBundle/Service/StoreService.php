<?php

namespace FourPaws\StoreBundle\Service;

use Adv\Bitrixtools\Tools\HLBlock\HLBlockFactory;
use FourPaws\Location\LocationService;
use FourPaws\StoreBundle\Collection\StoreCollection;
use FourPaws\StoreBundle\Entity\Store;
use FourPaws\StoreBundle\Entity\Base as BaseEntity;
use FourPaws\StoreBundle\Exception\NotFoundException;
use FourPaws\StoreBundle\Exception\BaseException;
use FourPaws\StoreBundle\Repository\StoreRepository;

class StoreService
{
    /**
     * Все склады
     */
    const TYPE_ALL = 'TYPE_ALL';

    /**
     * Склады, не являющиеся магазинами
     */
    const TYPE_STORE = 'TYPE_STORE';

    /**
     * Склады, являющиеся магазинами
     */
    const TYPE_SHOP = 'TYPE_SHOP';

    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @var StoreRepository
     */
    protected $repository;

    public function __construct(LocationService $locationService, StoreRepository $repository)
    {
        $this->locationService = $locationService;
        $this->repository = $repository;
    }

    /**
     * Получить склад по ID
     *
     * @param int $id
     *
     * @return bool|BaseEntity|Store
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getById(int $id)
    {
        $store = false;
        try {
            $store = $this->repository->find($id);
        } catch (BaseException $e) {
        }

        if (!$store) {
            throw new NotFoundException('Склад с ID=' . $id . ' не найден');
        }

        return $store;
    }
    
    /**
     * Получить склад по XML_ID
     *
     * @param $xmlId
     *
     * @return Store
     * @throws NotFoundException
     * @throws \Exception
     */
    public function getByXmlId($xmlId): Store
    {
        $store = false;
        try {
            $store = $this->repository->findBy(['XML_ID' => $xmlId, [], 1])->first();
        } catch (BaseException $e) {
        }

        if (!$store) {
            throw new NotFoundException('Склад с XML_ID=' . $xmlId . ' не найден');
        }

        return $store;
    }

    /**
     * Получить склады в текущем местоположении
     *
     * @param string $type
     *
     * @return StoreCollection
     * @throws \Exception
     */
    public function getByCurrentLocation($type = self::TYPE_ALL): StoreCollection
    {
        $location = $this->locationService->getCurrentLocation();

        return $this->getByLocation($location, $type);
    }

    /**
     * Получить склады, привязанные к указанному местоположению
     *
     * @param string $locationCode
     * @param string $type
     *
     * @return StoreCollection
     * @throws \Exception
     */
    public function getByLocation(string $locationCode, string $type = self::TYPE_ALL)
    {
        $filter = array_merge(
            ['UF_LOCATION' => $locationCode],
            $this->getTypeFilter($type)
        );

        return $this->repository->findBy($filter);
    }

    /**
     * Получить склады по массиву XML_ID
     *
     * @param array $codes
     *
     * @return StoreCollection
     * @throws \Exception
     */
    public function getMultipleByXmlId(array $codes)
    {
        return $this->repository->findBy(['XML_ID' => $codes]);
    }

    /**
     * @param $type
     *
     * @return array
     */
    public function getTypeFilter($type): array
    {
        switch ($type) {
            case self::TYPE_SHOP:
                return ['UF_IS_SHOP' => 1];
            case self::TYPE_STORE:
                return ['UF_IS_SHOP' => 0];
        }

        return [];
    }

    /**
     * @param array $filter
     *
     * @param array $select
     *
     * @return array
     * @throws \Exception
     */
    public function getMetroInfo(array $filter = [], array $select = ['*']): array
    {
        $highloadStation = HLBlockFactory::createTableObject('MetroStations');
        $branchIds = [];
        $result = [];
        $res = $highloadStation::query()->setFilter($filter)->setSelect($select)->exec();
        while ($item = $res->fetch()) {
            $result[$item['ID']] = $item;
            if (!\in_array($item['UF_BRANCH'], $branchIds, true)) {
                $branchIds[$item['ID']] = $item['UF_BRANCH'];
            }
        }

        if (\is_array($branchIds) && !empty($branchIds)) {
            $highloadBranch = HLBlockFactory::createTableObject('MetroWays');
            $res = $highloadBranch::query()->setFilter(['ID' => $branchIds])->exec();
            $reverseBranchIds = [];
            foreach ($branchIds as $id => $branch) {
                $reverseBranchIds[$branch][] = $id;
            }
            while ($item = $res->fetch()) {
                if (\is_array($reverseBranchIds[$item['ID']]) && !empty($reverseBranchIds[$item['ID']])) {
                    foreach ($reverseBranchIds[$item['ID']] as $id) {
                        $item['CLASS'] = $item['UF_CLASS'] ?? '';
                        $result[$id]['BRANCH'] = $item;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return \FourPaws\Location\LocationService
     */
    public function getLocationService(): LocationService
    {
        return $this->locationService;
    }

    /**
     * @param array $filter
     * @param array $select
     *
     * @return array
     * @throws \Exception
     */
    public function getServicesInfo(array $filter, array $select = ['*']): array
    {
        $highloadServices = HLBlockFactory::createTableObject('StoreServices');

        $result = [];
        $res = $highloadServices::query()->setFilter($filter)->setSelect($select)->exec();
        while ($item = $res->fetch()) {
            $result[$item['ID']] = $item;
        }

        return $result;
    }

    /**
     * @return \FourPaws\StoreBundle\Repository\StoreRepository
     */
    public function getRepository(): StoreRepository
    {
        return $this->repository;
    }
}
