<?php

namespace Sprint\Migration;


use Adv\Bitrixtools\Exception\IblockNotFoundException;
use Adv\Bitrixtools\Migration\SprintMigrationBase;
use Adv\Bitrixtools\Tools\Iblock\IblockUtils;
use FourPaws\Enum\IblockCode;
use FourPaws\Enum\IblockType;

class AddHLBlockStampsProductSections20191010131548 extends SprintMigrationBase
{
    protected $description = 'Добавляет новый HL-блок "Марки: разделы товаров"';

    protected const TABLE_NAME = '4lapy_stamps_sections';
    protected const HL_BLOCK_TYPE = 'StampsProductSections';

    /**
     * @return bool|void
     * @throws IblockNotFoundException
     */
    public function up()
    {
        $helper = new HelperManager();

        $hlblockId = $helper->Hlblock()->addHlblockIfNotExists([
            'NAME' => self::HL_BLOCK_TYPE,
            'TABLE_NAME' => self::TABLE_NAME,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Марки: разделы товаров',
                ],
            ],
        ]);

        $entityId = 'HLBLOCK_' . $hlblockId;

        $helper->UserTypeEntity()->addUserTypeEntityIfNotExists($entityId, 'UF_PRODUCT_SECTION', [
            'FIELD_NAME' => 'UF_PRODUCT_SECTION',
            'USER_TYPE_ID' => 'iblock_section',
            'XML_ID' => 'UF_PRODUCT_SECTION',
            'SORT' => '100',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'N',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'SETTINGS' =>
                [
                    'DISPLAY' => 'LIST',
                    'LIST_HEIGHT' => 5,
                    'IBLOCK_ID' => IblockUtils::getIblockId(IblockType::CATALOG, IblockCode::PRODUCTS),
                    'DEFAULT_VALUE' => '',
                    'ACTIVE_FILTER' => 'N',
                ],
            'EDIT_FORM_LABEL' =>
                [
                    'ru' => 'Раздел товаров',
                ],
            'LIST_COLUMN_LABEL' =>
                [
                    'ru' => 'Раздел товаров',
                ],
            'LIST_FILTER_LABEL' =>
                [
                    'ru' => 'Раздел товаров',
                ],
            'ERROR_MESSAGE' =>
                [
                    'ru' => 'Раздел товаров',
                ],
            'HELP_MESSAGE' =>
                [
                    'ru' => 'Раздел товаров',
                ],
        ]);
    }

    /**
     * @return bool|void
     */
    public function down()
    {
        $helper = new HelperManager();

        $helper->Hlblock()->deleteHlblockIfExists(self::HL_BLOCK_TYPE);
    }
}
