<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$APPLICATION->IncludeComponent(
    'fourpaws:personal.address',
    '',
    [],
    $component,
    ['HIDE_ICONS' => 'Y']
);
