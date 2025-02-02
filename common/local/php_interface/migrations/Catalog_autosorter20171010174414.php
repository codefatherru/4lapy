<?php

namespace Sprint\Migration;

use Adv\Bitrixtools\Migration\SprintMigrationBase;
use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use Bitrix\Main\Application;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;

class Catalog_autosorter20171010174414 extends SprintMigrationBase
{

    protected $description = "Автосортировка товаров";

    public function up()
    {
        $this->createPropCondTable();
        $this->createUfPropCond();
        $this->createManualAutoSortProp();
    }

    private function createPropCondTable()
    {
        $query = <<<END
CREATE TABLE IF NOT EXISTS `4lp_elem_prop_cond` (
  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `UF_ID` INT UNSIGNED NOT NULL COMMENT 'ID пользовательского свойства',
  `SECTION_ID` INT UNSIGNED NOT NULL COMMENT 'ID раздела, в котором используется кастомное свойство \"Условие для свойств элемента\"',
  `PROPERTY_ID` INT UNSIGNED NOT NULL COMMENT 'ID свойства элемента, которое надо проверить.',
  `PROPERTY_VALUE` VARCHAR(255) NULL COMMENT 'Значение свойства. Если null - символизирует незаполненное свойство.',
  PRIMARY KEY (`ID`),
  INDEX `main_working_index` USING BTREE (`UF_ID` ASC, `SECTION_ID` ASC, `PROPERTY_ID` ASC, `PROPERTY_VALUE` ASC ))
END;

        Application::getConnection()->queryExecute($query);
    }

    private function createUfPropCond()
    {
        $this->getHelper()->UserTypeEntity()->addUserTypeEntityIfNotExists(
            sprintf('IBLOCK_%s_SECTION', IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::PRODUCTS)),
            'UF_PROP_COND',
            [
                'USER_TYPE_ID'      => 'element_property_condition',
                'XML_ID'            => 'UF_PROP_COND',
                'SORT'              => '100',
                'MULTIPLE'          => 'Y',
                'MANDATORY'         => 'N',
                'SHOW_FILTER'       => 'I',
                'SHOW_IN_LIST'      => 'Y',
                'EDIT_IN_LIST'      => 'Y',
                'IS_SEARCHABLE'     => 'N',
                'SETTINGS'          =>
                    [],
                'EDIT_FORM_LABEL'   =>
                    [
                        'en' => '',
                        'ru' => 'Условие для свойств товара',
                    ],
                'LIST_COLUMN_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Условие для свойств товара',
                    ],
                'LIST_FILTER_LABEL' =>
                    [
                        'en' => '',
                        'ru' => 'Условие для свойств товара',
                    ],
                'ERROR_MESSAGE'     =>
                    [
                        'en' => '',
                        'ru' => '',
                    ],
                'HELP_MESSAGE'      =>
                    [
                        'en' => '',
                        'ru' => 'Выберите свойство товара/торгового предложения и выберите значение для него. '
                            . 'Если возможен выбор из справочника, значения подгрузятся автоматически. '
                            . 'Товары, полностью удовлетворяющие всем условиям, будут попадать в эту категорию.',
                    ],
            ]

        );
    }

    private function createManualAutoSortProp()
    {
        $this->getHelper()->Iblock()->addPropertyIfNotExists(
            IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::PRODUCTS),
            [

                'NAME'               => 'Автоматически определить категории',
                'ACTIVE'             => 'Y',
                'SORT'               => '500',
                'CODE'               => 'APPLY_AUTOSORT',
                'DEFAULT_VALUE'      => 0,
                'PROPERTY_TYPE'      => 'N',
                'ROW_COUNT'          => '1',
                'COL_COUNT'          => '30',
                'LIST_TYPE'          => 'C',
                'MULTIPLE'           => 'N',
                'XML_ID'             => '',
                'FILE_TYPE'          => '',
                'MULTIPLE_CNT'       => '5',
                'TMP_ID'             => null,
                'LINK_IBLOCK_ID'     => '0',
                'WITH_DESCRIPTION'   => 'N',
                'SEARCHABLE'         => 'N',
                'FILTRABLE'          => 'Y',
                'IS_REQUIRED'        => 'N',
                'VERSION'            => '2',
                'USER_TYPE'          => 'YesNoPropertyType',
                'USER_TYPE_SETTINGS' => null,
                'HINT'               =>
                    'Отметье, чтобы при сохранении товар был ' . 'автоматически определён в подходящие категории.',
            ]
        );
    }

}
