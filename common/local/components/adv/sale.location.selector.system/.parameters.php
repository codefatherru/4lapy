<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        
        'ENTITY_PRIMARY' => [
            'NAME'     => Loc::getMessage('SALE_SLSS_ENTITY_PRIMARY_PARAMETER'),
            'PARENT'   => 'BASE',
            'TYPE'     => 'STRING',
            'MULTIPLE' => 'N',
        ],
        'INPUT_NAME'     => [
            'NAME'    => Loc::getMessage('SALE_SLSS_INPUT_NAME_PARAMETER'),
            'PARENT'  => 'BASE',
            'TYPE'    => 'STRING',
            'DEFAULT' => 'LOCATION',
        ],
        
        'LINK_ENTITY_NAME' => [
            'NAME'     => Loc::getMessage('SALE_SLSS_LINK_ENTITY_NAME_PARAMETER'),
            'PARENT'   => 'BASE',
            'TYPE'     => 'STRING',
            'MULTIPLE' => 'N',
        ],
        
        'ENTITY_VARIABLE_NAME' => [
            'NAME'     => Loc::getMessage('SALE_SLSS_ENTITY_VARIABLE_NAME_PARAMETER'),
            'PARENT'   => 'BASE',
            'TYPE'     => 'STRING',
            'DEFAULT'  => 'id',
            'MULTIPLE' => 'N',
        ],
        
        'PROP_LOCATION' => [
            'NAME'     => Loc::getMessage('SALE_SLSS_PROP_LOCATION_PARAMETER'),
            'PARENT'   => 'BASE',
            'TYPE'     => 'CHECKBOX',
            'DEFAULT'  => 'N',
            'MULTIPLE' => 'N',
        ],
        
        'CACHE_TIME' => ['DEFAULT' => 36000000],
    ],
];