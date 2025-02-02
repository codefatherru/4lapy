<?php

use Articul\BlackFriday\Orm\BFSectionsTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Iblock\Model\Section;

/**
 * Class BlackFridaySections
 */
class BlackFridaySections extends \CBitrixComponent
{
    /**
     * @var int $iblockId
     */
    private $iblockId;
    
    /**
     * @var string $iblockCode
     */
    private $iblockCode = 'black_friday_sections';
    
    /**
     * @var $sectinonsWithElements
     */
    private $sectinonsWithElements;
    
    /**
     * @param array $params
     *
     * @return array
     */
    public function onPrepareComponentParams($params): array
    {
        $params['CACHE_TIME'] = $params['CACHE_TIME'] ?? 360000;
        $params['CACHE_TYPE'] = $params['CACHE_TYPE'] ?? 'Y';
        
        return parent::onPrepareComponentParams($params);
    }
    
    /**
     * @return mixed|void
     * @throws \Bitrix\Main\LoaderException
     */
    public function executeComponent()
    {
        if (!Loader::includeModule('articul.blackfriday')) {
            echo 'Модуль Лэндинга Черной Пятницы не установлен';
            return;
        }
        
        if ($this->StartResultCache($this->arParams['CACHE_TIME'])) {
            $this->iblockId = $this->getIblockId();
            
            $this->arResult['SECTION_WITH_ELEMENTS'] = $this->getElements();
            $this->arResult['EMPTY_SECTIONS']        = $this->getSections();
            
            $this->setSectionPropertiesForSectionWithElements();
            
            if (!$this->arResult['SECTION_WITH_ELEMENTS'] && !$this->arResult['EMPTY_SECTIONS']) {
                $this->abortResultCache();
            }
            
            $this->includeComponentTemplate();
        }
    }
    
    /**
     * @return array
     */
    private function getElements()
    {
        $elements = BFSectionsTable::query()
            ->setSelect(['ID', 'IBLOCK_SECTION_ID', 'NAME', 'PREVIEW_PICTURE', 'LINK' => 'UTS.LINK', 'SECTION_NAME' => 'SECTION.NAME'])
            ->setFilter(['=IBLOCK_ID' => $this->iblockId, '=ACTIVE' => 'Y'])
            ->registerRuntimeField(new ReferenceField(
                'SECTION',
                '\Bitrix\Iblock\SectionTable',
                ['=this.IBLOCK_SECTION_ID' => 'ref.ID']
            ))
            ->setOrder(['SORT' => 'ASC'])
            ->setCacheTtl($this->arParams['CACHE_TIME'])
            ->exec()
            ->fetchAll();
        
        foreach ($elements as &$element) {
            $element['PREVIEW_PICTURE'] = \CFile::GetPath($element['PREVIEW_PICTURE']);
        }
        
        $result = $this->allocateElements($elements);
        
        return $result;
    }
    
    /**
     * @return mixed
     */
    private function getSections()
    {
        $entity   = Section::compileEntityByIblock($this->iblockId);
        $sections = $entity::query()
            ->setSelect(['ID', 'NAME', 'UF_LINK', 'UF_DESKTOP_PICTURE', 'UF_TABLET_PICTURE', 'UF_MOBILE_PICTURE'])
            ->setFilter(['=IBLOCK_ID' => $this->iblockId, '=ACTIVE' => 'Y', '!ID' => $this->sectinonsWithElements])
            ->setOrder(['SORT' => 'ASC'])
            ->setCacheTtl($this->arParams['CACHE_TIME'])
            ->exec()
            ->fetchAll();
        
        foreach ($sections as $key => $section) {
            $sections[$key]['DESKTOP_PICTURE'] = \CFile::GetPath($section['UF_DESKTOP_PICTURE']);
            $sections[$key]['TABLET_PICTURE']  = \CFile::GetPath($section['UF_TABLET_PICTURE']);
            $sections[$key]['MOBILE_PICTURE']  = \CFile::GetPath($section['UF_MOBILE_PICTURE']);
        }
        
        return $sections;
    }
    
    /**
     * @return mixed
     */
    private function getIblockId()
    {
        return \CIBlock::GetList([], ['CODE' => $this->iblockCode])->Fetch()['ID'];
    }
    
    /**
     * @param $elements
     * @return array
     */
    private function allocateElements($elements)
    {
        $result = [];
        
        foreach ($elements as $key => $element) {
            if (!in_array($element['IBLOCK_SECTION_ID'], $this->sectinonsWithElements)) {
                $this->sectinonsWithElements[] = $element['IBLOCK_SECTION_ID'];
            }
            $result[$element['IBLOCK_SECTION_ID']]['SECTION_NAME'] = $element['SECTION_NAME'];
            $result[$element['IBLOCK_SECTION_ID']]['ITEMS'][]      = $element;
        }
        
        return $result;
    }
    
    /**
     * @return mixed
     */
    private function setSectionPropertiesForSectionWithElements()
    {
        foreach ($this->arResult['SECTION_WITH_ELEMENTS'] as $key => $section) {
            $sectionsId[] = $key;
        }
        
        $entity   = Section::compileEntityByIblock($this->iblockId);
        $sections = $entity::query()
            ->setSelect(['ID', 'UF_LINK', 'UF_DESKTOP_PICTURE', 'UF_TABLET_PICTURE', 'UF_MOBILE_PICTURE', 'UF_LABEL_LEFT', 'UF_LABEL_RIGHT', 'UF_DISCOUNT_SIZE'])
            ->setFilter(['=IBLOCK_ID' => $this->iblockId, '=ACTIVE' => 'Y', '=ID' => $sectionsId])
            ->setOrder(['SORT' => 'ASC'])
            ->setCacheTtl($this->arParams['CACHE_TIME'])
            ->exec()
            ->fetchAll();
        
        foreach ($sections as $section) {
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['LINK']            = $section['UF_LINK'];
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['DESKTOP_PICTURE'] = \CFile::GetPath($section['UF_DESKTOP_PICTURE']);
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['TABLET_PICTURE']  = \CFile::GetPath($section['UF_TABLET_PICTURE']);
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['MOBILE_PICTURE']  = \CFile::GetPath($section['UF_MOBILE_PICTURE']);
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['LABEL_LEFT']      = $section['UF_LABEL_LEFT'];
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['LABEL_RIGHT']     = $section['UF_LABEL_RIGHT'];
            $this->arResult['SECTION_WITH_ELEMENTS'][$section['ID']]['DISCOUNT_SIZE']   = $section['UF_DISCOUNT_SIZE'];
        }
        
        return $sections;
    }
}
