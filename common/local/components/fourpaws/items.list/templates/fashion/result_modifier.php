<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

use FourPaws\BitrixOrm\Model\CropImageDecorator;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!\is_array($arResult['ITEMS']) || empty($arResult['ITEMS'])) {
    return;
}

foreach ($arResult['ITEMS'] as $key => &$item) {
    if (isset($image)) {
        unset($image);
    }
    if (is_array($item['PREVIEW_PICTURE']) && !empty($item['PREVIEW_PICTURE'])) {
        $image = new CropImageDecorator($item['PREVIEW_PICTURE']);
    } elseif (is_numeric($item['~PREVIEW_PICTURE']) && (int)$item['~PREVIEW_PICTURE'] > 0) {
        /** @noinspection PhpUnhandledExceptionInspection */
        $image = CropImageDecorator::createFromPrimary($item['~PREVIEW_PICTURE']);
    }
    if ($image instanceof CropImageDecorator) {
        if ($key === 0) {
            $cropWidth  = 740;
            $cropHeight = 342;
        } else {
            $cropWidth  = 360;
            $cropHeight = 165;
        }
        $item['PREVIEW_PICTURE']['SRC'] = $image->setCropWidth($cropWidth)->setCropHeight($cropHeight)->getSrc();
    }
}
unset($item);
