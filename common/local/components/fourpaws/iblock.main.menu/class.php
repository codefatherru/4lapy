<?if(!defined('B_PROLOG_INCLUDED')||B_PROLOG_INCLUDED!==true)die();
/**
 * Class CFourPawsIBlockMainMenu
 * Компонент главного меню сайта, генерируемого по специальному инфоблоку
 *
 * @updated: 23.12.2017
 */

class CFourPawsIBlockMainMenu extends \CBitrixComponent {
	/** @var int $iMenuIBlockId */
	private $iMenuIBlockId = -1;
	/** @var array $arMenuIBlockSectionsTree */
	private $arMenuIBlockSectionsTree = null;
	/** @var array $arMenuIBlockElements */
	private $arMenuIBlockElements = null;

	/**
	 * @param \CBitrixComponent|null $obParentComponent
	 */
	public function __construct($obParentComponent = null) {
		parent::__construct($obParentComponent);
		$this->constructInit($obParentComponent);
	}

	/**
	 * @param \CBitrixComponent|null $obParentComponent
	 * @return void
	 */
	protected function constructInit($obParentComponent = null) {
		//
	}

	/**
	 * @param array $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams) {
		$arParams['IBLOCK_TYPE'] = isset($arParams['IBLOCK_TYPE']) ? trim($arParams['IBLOCK_TYPE']) : 'menu';
		$arParams['IBLOCK_CODE'] = isset($arParams['IBLOCK_CODE']) ? trim($arParams['IBLOCK_CODE']) : 'main_menu';

		$arParams['CACHE_TIME'] = isset($arParams['CACHE_TIME']) ? intval($arParams['CACHE_TIME']) : 43200;
		if($arParams['CACHE_TYPE'] === 'N' || ($arParams['CACHE_TYPE'] === 'A' && \COption::GetOptionString('main', 'component_cache_on', 'Y') === 'N')) {
			$arParams['CACHE_TIME'] = 0;
		}

		$arParams['MAX_DEPTH_LEVEL'] = isset($arParams['MAX_DEPTH_LEVEL']) ? intval($arParams['MAX_DEPTH_LEVEL']) : 4;

		return $arParams;
	}

	/**
	 * @return array
	 */
	public function executeComponent() {
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		if(!strlen($arParams['IBLOCK_TYPE']) || !strlen($arParams['IBLOCK_CODE'])) {
			return false;
		}

		$arGroups = array();

		$sCacheDir = SITE_ID.'/'.basename(__DIR__);
		$sCacheDir = '/'.ltrim($sCacheDir, '/');
		$sCachePath = $sCacheDir; 

		$sCacheId = md5(serialize(array($arGroups)));

		if($this->startResultCache($arParams['CACHE_TIME'], $sCacheId, $sCachePath)) {
			if(!\Bitrix\Main\Loader::includeModule('iblock')) {
				$this->abortResultCache();
				return $arResult;
			}

			$arParams['IBLOCK_ID'] = $this->getMenuIBlockId();

			if($arParams['IBLOCK_ID'] <= 0) {
				$this->abortResultCache();
				return $arResult;
			}

			$arResult['MENU_TREE'] = $this->getNestedMenu();

			$this->endResultCache();
		}

		$this->includeComponentTemplate();
		//$this->templateCachedData = $this->getTemplateCachedData();

		return $arResult;
	}

	/**
	 * @return array
	 */
	public function getNestedMenu() {
		$arData = array();
		$arSectionsTree = $this->getMenuIBlockSectionsTree();
		$arMenuElements = $this->getMenuIBlockElements();

		$arMenuIBlockElements2Sections = array();
		foreach($arMenuElements as $arItem) {
			$arMenuIBlockElements2Sections[intval($arItem['IBLOCK_SECTION_ID'])][] = $arItem['ID'];
		}

		$iMaxDepthLevel = 0;
		foreach($arSectionsTree as $iSectionId => $arSect) {
			$arSect['IS_DIR'] = true;
			$arSect['NESTED'] = array();
			if(!empty($arMenuIBlockElements2Sections[$iSectionId])) {
				foreach($arMenuIBlockElements2Sections[$iSectionId] as $iElementId) {
					$arElem = $arMenuElements[$iElementId];
					$arElem['DEPTH_LEVEL'] = $arSect['DEPTH_LEVEL'] + 1;
					$arElem['IS_DIR'] = false;
					$arElem['NESTED'] = array();
					$arSect['NESTED']['E'.$arElem['ID']] = $arElem;
				}
			}
			// пока все секции привязываем к корню, перенос по веткам будет выполнен ниже
			$arData['S'.$arSect['ID']] = $arSect;
			$iMaxDepthLevel = $arSect['DEPTH_LEVEL'] > $iMaxDepthLevel ? $arSect['DEPTH_LEVEL'] : $iMaxDepthLevel;
		}

		// заполнение вложенности секций, двигаемся сверху вниз (от максимального уровня вложенности)
		$iCurLevel = $iMaxDepthLevel;
		while($iCurLevel > 1) {
			foreach($arData as $mKey => $arItem) {
				if(!$arItem['IS_DIR']) {
					continue;
				}
				if($arItem['DEPTH_LEVEL'] != $iCurLevel) {
					continue;
				}
				if($arItem['IBLOCK_SECTION_ID'] && isset($arData['S'.$arItem['IBLOCK_SECTION_ID']])) {
					$arData['S'.$arItem['IBLOCK_SECTION_ID']]['NESTED']['S'.$arItem['ID']] = $arItem;
					unset($arData[$mKey]);
				}
			}
			--$iCurLevel;
		}

		if(!empty($arMenuIBlockElements2Sections[0])) {
			foreach($arMenuIBlockElements2Sections[0] as $iElementId) {
				$arElem = $arMenuElements[$iElementId];
				$arElem['DEPTH_LEVEL'] = 1;
				$arElem['IS_DIR'] = false;
				$arElem['NESTED'] = array();
				$arData['E'.$arElem['ID']] = $arElem;
			}
		}

		$arData = $this->sortRecursive($arData);

//_log_array($arSectionsTree, '$arSectionsTree');
//_log_array($arMenuElements, '$arMenuElements');
_log_array($arData, '$arData');
		return $arData;
	}

	/**
	 * @param array $arData
	 * @return array
	 */
	protected function sortRecursive($arData) {
		$iIdx = 0;
		foreach($arData as &$arItem) {
			if($arItem['NESTED']) {
				$arItem['NESTED'] = $this->sortRecursive($arItem['NESTED']);
			}
			// формируем поле для сортировки: DEPTH_LEVEL-SORT-IS_DIR-IDX
			$arTmp = array();
			$arTmp[] = $arItem['DEPTH_LEVEL'];
			$arTmp[] = $arItem['SORT'];
			// секциям отдаем больший вес
			$arTmp[] = $arItem['IS_DIR'] ? '0' : '1';
			// чтобы сохранялась исходная последовательность среди равных
			$arTmp[] = ++$iIdx;
			$arItem['SORT_IDX'] = implode('-', $arTmp);
		}
		unset($arItem);

		uasort(
			$arData,
			function($arA, $arB) {
				return strnatcmp($arA['SORT_IDX'], $arB['SORT_IDX']);
			}
		);

		return $arData;
	}

	/**
	 * @return int
	 */
	public function getMenuIBlockId() {
		if($this->iMenuIBlockId < 0) {
			$this->iMenuIBlockId = $this->getIBlockIdByCode($this->arParams['IBLOCK_CODE'], $this->arParams['IBLOCK_TYPE']);
		}

		return $this->iMenuIBlockId;
	}

	/**
	 * @param string $sIBlockCode
	 * @param string $sIBlockType
	 * @return int
	 */
	protected function getIBlockIdByCode($sIBlockCode, $sIBlockType = '') {
		$iReturn = 0;

		if(!\Bitrix\Main\Loader::includeModule('iblock')) {
			return $iReturn;
		}

		$arFilter = array(
			'CHECK_PERMISSIONS' => 'N',
			'CODE' => $sIBlockCode,
			'SITE_ID' => SITE_ID,
		);
		if(strlen($sIBlockType)) {
			$arFilter['TYPE'] = $sIBlockType;
		}
		$arIBlock = \CIBlock::GetList(array('ID' => 'ASC'), $arFilter)->fetch();
		$iReturn = $arIBlock ? $arIBlock['ID'] : 0;

		return $iReturn;
	}

	/**
	 * @return array
	 */
	public function getMenuIBlockSectionsTree() {
		if(is_null($this->arMenuIBlockSectionsTree)) {
			$this->obtainMenuIBlockSectionsTree();
		}
		return $this->arMenuIBlockSectionsTree;
	}

	/**
	 * @return void
	 */
	protected function obtainMenuIBlockSectionsTree() {
		$arData = array();
		$this->arMenuIBlockSectionsTree =& $arData;

		// здесь делается подключение модуля инфоблоков
		$iIBlockId = $this->getMenuIBlockId();

		if(!$iIBlockId) {
			return;
		}

		$arRelElements = array();
		$arRelSections = array();
		$dbItems = \CIBlockSection::GetList(
			array(
				'LEFT_MARGIN' => 'ASC' // !!!
			),
			array(
				'IBLOCK_ID' => $iIBlockId,
				'ACTIVE' => 'Y',
				'GLOBAL_ACTIVE' => 'Y',
				'<=DEPTH_LEVEL' => $this->arParams['MAX_DEPTH_LEVEL'],
			),
			false,
			array(
				'ID', 'NAME', 'IBLOCK_SECTION_ID',
				'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL',
				'SORT',
				//'CODE', 'XML_ID',
				'UF_*',
			)
		);
		while($arItem = $dbItems->getNext(true, false)) {
			$arData[$arItem['ID']] = array(
				'ID' => $arItem['ID'],
				'NAME' => $arItem['NAME'],
				'IBLOCK_SECTION_ID' => intval($arItem['IBLOCK_SECTION_ID']),
				'SORT' => $arItem['SORT'],
				'DEPTH_LEVEL' => $arItem['DEPTH_LEVEL'],
				'HREF' => isset($arItem['UF_HREF']) ? trim($arItem['UF_HREF']) : '',
				'ELEMENT_HREF_ID' => 0,
				'ELEMENT_HREF' => array(), // заполняются ниже
				'SECTION_HREF_ID' => isset($arItem['UF_SECTION_HREF']) ? intval($arItem['UF_SECTION_HREF']) : 0,
				'SECTION_HREF' => array(), // заполняются ниже
				'TARGET_BLANK' => isset($arItem['UF_TARGET_BLANK']) ? intval($arItem['UF_TARGET_BLANK']) : 0,
				'IS_BRAND_MENU'  => isset($arItem['UF_BRAND_MENU']) ? intval($arItem['UF_BRAND_MENU']) : 0,
			);


			if($arData[$arItem['ID']]['ELEMENT_HREF_ID'] > 0) {
				$arRelElements[$arData[$arItem['ID']]['ELEMENT_HREF_ID']][] = $arItem['ID'];
			}
			if($arData[$arItem['ID']]['SECTION_HREF_ID'] > 0) {
				$arRelSections[$arData[$arItem['ID']]['SECTION_HREF_ID']][] = $arItem['ID'];
			}
		}

		// заполнение данными связанных элементов
		$arData = $this->completeRelElements($arData, $arRelElements);

		// заполнение данными связанных секций
		$arData = $this->completeRelSections($arData, $arRelSections);
	}

	/**
	 * @return array
	 */
	public function getMenuIBlockElements() {
		if(is_null($this->arMenuIBlockElements)) {
			$this->obtainMenuIBlockElements();
		}
		return $this->arMenuIBlockElements;
	}

	/**
	 * @return void
	 */
	protected function obtainMenuIBlockElements() {
		$arData = array();
		$this->arMenuIBlockElements =& $arData;

		// здесь делается подключение модуля инфоблоков
		$iIBlockId = $this->getMenuIBlockId();

		if(!$iIBlockId) {
			return;
		}

		$arRelElements = array();
		$arRelSections = array();
		$dbItems = \CIBlockElement::GetList(
			array(
				'SORT' => 'ASC',
				'ID' => 'ASC',
			),
			array(
				'IBLOCK_ID' => $iIBlockId,
				'ACTIVE' => 'Y',
				'ACTIVE_DATE' => 'Y',
			),
			false,
			false,
			array(
				'ID', 'NAME', 'IBLOCK_ID', 'IBLOCK_SECTION_ID',
				'SORT',
				//'CODE', 'XML_ID',
				'PROPERTY_HREF',
				'PROPERTY_ELEMENT_HREF',
				'PROPERTY_SECTION_HREF',
				'PROPERTY_TARGET_BLANK',
			)
		);
		while($arItem = $dbItems->getNext(true, false)) {
			$arData[$arItem['ID']] = array(
				'ID' => $arItem['ID'],
				'NAME' => $arItem['NAME'],
				'IBLOCK_SECTION_ID' => intval($arItem['IBLOCK_SECTION_ID']),
				'SORT' => $arItem['SORT'],
				'HREF' => isset($arItem['PROPERTY_HREF_VALUE']) ? trim($arItem['PROPERTY_HREF_VALUE']) : '',
				'ELEMENT_HREF_ID' => isset($arItem['PROPERTY_ELEMENT_HREF_VALUE']) ? intval($arItem['PROPERTY_ELEMENT_HREF_VALUE']) : 0,
				'ELEMENT_HREF' => array(), // заполняются ниже
				'SECTION_HREF_ID' => isset($arItem['PROPERTY_SECTION_HREF_VALUE']) ? intval($arItem['PROPERTY_SECTION_HREF_VALUE']) : 0,
				'SECTION_HREF' => array(), // заполняются ниже
				'TARGET_BLANK' => isset($arItem['PROPERTY_TARGET_BLANK_VALUE']) ? intval($arItem['PROPERTY_TARGET_BLANK_VALUE']) : 0,
			);

			if($arData[$arItem['ID']]['ELEMENT_HREF_ID'] > 0) {
				$arRelElements[$arData[$arItem['ID']]['ELEMENT_HREF_ID']][] = $arItem['ID'];
			}
			if($arData[$arItem['ID']]['SECTION_HREF_ID'] > 0) {
				$arRelSections[$arData[$arItem['ID']]['SECTION_HREF_ID']][] = $arItem['ID'];
			}
		}

		// заполнение данными связанных элементов
		$arData = $this->completeRelElements($arData, $arRelElements);

		// заполнение данными связанных секций
		$arData = $this->completeRelSections($arData, $arRelSections);
	}


	/**
	 * @param array $arData
	 * @param array $arRelElements
	 * @return array
	 */
	private function completeRelElements($arData, $arRelElements) {
		if($arRelElements) {
			$dbItems = \CIBlockElement::GetList(
				array(),
				array(
					'ID' => array_keys($arRelElements),
					'ACTIVE' => 'Y',
					'ACTIVE_DATE' => 'Y',
				),
				false,
				false,
				array(
					'ID', 'DETAIL_PAGE_URL',
				)
			);
			while($arItem = $dbItems->getNext(true, false)) {
				if($arRelElements[$arItem['ID']]) {
					foreach($arRelElements[$arItem['ID']] as $iTmpId) {
						if($arData[$iTmpId]) {
							$arData[$iTmpId]['ELEMENT_HREF'] = array(
								'ID' => $arItem['ID'],
								'URL' => $arItem['DETAIL_PAGE_URL'],
								'IBLOCK_ID' => $arItem['IBLOCK_ID'],
								'IBLOCK_CODE' => $arItem['IBLOCK_CODE'],
								//'IBLOCK_SECTION_ID' => $arItem['IBLOCK_SECTION_ID'],
								//'CODE' => $arItem['CODE'],
							);
						}
					}
				}
			}
		}
		return $arData;
	}

	/**
	 * @param array $arData
	 * @param array $arRelSections
	 * @return array
	 */
	private function completeRelSections($arData, $arRelSections) {
		if($arRelSections) {
			$dbItems = \CIBlockSection::GetList(
				array(),
				array(
					'ID' => array_keys($arRelSections),
					'ACTIVE' => 'Y',
					'GLOBAL_ACTIVE' => 'Y',
				),
				false,
				false,
				array(
					'ID', 'SECTION_PAGE_URL',
				)
			);
			while($arItem = $dbItems->getNext(true, false)) {
				if($arRelSections[$arItem['ID']]) {
					foreach($arRelSections[$arItem['ID']] as $iTmpId) {
						if($arData[$iTmpId]) {
							$arData[$iTmpId]['SECTION_HREF'] = array(
								'ID' => $arItem['ID'],
								'URL' => $arItem['SECTION_PAGE_URL'],
								'IBLOCK_ID' => $arItem['IBLOCK_ID'],
								'IBLOCK_CODE' => $arItem['IBLOCK_CODE'],
								//'IBLOCK_SECTION_ID' => $arItem['IBLOCK_SECTION_ID'],
								//'CODE' => $arItem['CODE'],
							);

						}
					}
				}
			}
		}

		return $arData;
	}
}
