<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\SystemException;
use FourPaws\App\Application;

class FourPawsCitySelectorComponent extends \CBitrixComponent
{

    /** {@inheritdoc} */
    public function onPrepareComponentParams($params): array
    {
        return $params;
    }

    /** {@inheritdoc} */
    public function executeComponent()
    {
        try {
            $this->prepareResult();

            $this->includeComponentTemplate();
        } catch (\Exception $e) {
            try {
                $logger = LoggerFactory::create('component');
                $logger->error(sprintf('Component execute error: %s', $e->getMessage()));
            } catch (\RuntimeException $e) {
            }
        }
    }

    /**
     * @return $this
     *
     * @throws SystemException
     */
    protected function prepareResult()
    {
        $locationService = Application::getInstance()->getContainer()->get('location.service');
        $userService = Application::getInstance()->getContainer()->get('user.service');
        $availableCities = $locationService->getAvailableCities();

        $this->arResult['POPULAR_CITIES'] = isset($availableCities['popular'])
            ? $availableCities['popular']
            : [];
        $this->arResult['MOSCOW_CITIES'] = isset($availableCities['moscow_region'])
            ? $availableCities['moscow_region']
            : [];
        $this->arResult['DEFAULT_CITY'] = $locationService->getDefaultCity();
        $this->arResult['SELECTED_CITY'] = $userService->getSelectedCity();

        return $this;
    }
}
