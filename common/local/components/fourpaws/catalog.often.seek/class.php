<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

use Bitrix\Main\Application as BitrixApplication;
use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\SystemException;
use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\CatalogBundle\Service\OftenSeekInterface;
use FourPaws\Helpers\TaggedCacheHelper;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/** @noinspection AutoloadingIssuesInspection */
class CatalogOftenSeekComponent extends CBitrixComponent
{
    /** @var OftenSeekInterface $oftenSeekService */
    private $oftenSeekService;

    /**
     * CFourPawsFoodSelectionComponent constructor.
     *
     * @param \CBitrixComponent|null $component
     *
     * @throws ServiceNotFoundException
     * @throws SystemException
     * @throws \RuntimeException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(\CBitrixComponent $component = null)
    {
        parent::__construct($component);
        try {
            $container = App::getInstance()->getContainer();
        } catch (ApplicationCreateException $e) {
            $logger = LoggerFactory::create('component');
            $logger->error(sprintf('Component execute error: %s', $e->getMessage()));
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new SystemException($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e);
        }

        $this->oftenSeekService = $container->get(OftenSeekInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function onPrepareComponentParams($params): array
    {
        if (!empty($params['SECTION_ID'])) {
            $params['SECTION_ID'] = (int)$params['SECTION_ID'];
        }

        $params['LEFT_MARGIN'] = (int)$params['LEFT_MARGIN'];
        $params['RIGHT_MARGIN'] = (int)$params['RIGHT_MARGIN'];
        $params['DEPTH_LEVEL'] = (int)$params['DEPTH_LEVEL'];

        /** кешируем на минуту - чтобы снизить нагрузку при оновременных запросах и в тоже время сохранить рандомную сортировку */
        $params['CACHE_TIME'] = $params['CACHE_TIME'] ?: 60;

        return $params;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function executeComponent()
    {
        if ($this->arParams['SECTION_ID'] <= 0) {
            return null;
        }

        if ($this->startResultCache()) {
            $this->arResult['ITEMS'] = $this->oftenSeekService->getItems(
                $this->arParams['SECTION_ID'],
                $this->arParams['LEFT_MARGIN'],
                $this->arParams['RIGHT_MARGIN'],
                $this->arParams['DEPTH_LEVEL']
            );

            TaggedCacheHelper::addManagedCacheTags([
                'catalog:often_seek:'.$this->arParams['SECTION_ID'],
                'catalog:often_seek'
            ]);

            $this->includeComponentTemplate();
        }

        return true;
    }
}
