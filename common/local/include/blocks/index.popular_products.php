<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/**
 * @todo региональность (PRICE_...)
 *
 * Блок на главной странице: популярные
 */

/** @global $APPLICATION */
$APPLICATION->IncludeComponent('fourpaws:catalog.snippet.list', '', [
    'COUNT'        => 12,
    'OFFER_FILTER' => [
        '=PROPERTY_IS_POPULAR' => '1',
        '>CATALOG_PRICE_2' => 0
    ],
    'TITLE'        => 'Популярные товары',
], false, ['HIDE_ICONS' => 'Y']);
