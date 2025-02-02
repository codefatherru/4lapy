<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var \CBitrixComponentTemplate $this
 *
 * @var array $arParams
 * @var array $arResult
 * @var array $templateData
 *
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 *
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 */

use FourPaws\Decorators\SvgDecorator;
use FourPaws\Helpers\WordHelper;

if (empty($arResult)) {
    return;
}

?>
<div class="b-header__wrapper-for-popover">
    <a class="b-combobox b-combobox--delivery b-combobox--header js-open-popover"
       href="/payment-and-delivery/"
       title="<?= $arResult['LOCATION']['NAME'] ?>">
        <span class="b-icon b-icon--delivery-header">
            <?= new SvgDecorator('icon-delivery', 20, 16) ?>
        </span>
        <?php if ($arResult['DELIVERY']['FREE_FROM']) { ?>
            Бесплатная доставка
        <?php } else { ?>
            Доставка от <?= WordHelper::numberFormat($arResult['DELIVERY']['PRICE'], 0) ?> ₽
        <?php } ?>
        <span class="b-icon b-icon--delivery-arrow">
            <?= new SvgDecorator('icon-arrow-down', 20, 16) ?>
        </span>
    </a>
    <div class="b-popover b-popover--blue-arrow js-popover">
        <?php if ($arResult['DELIVERY']['FREE_FROM']) { ?>
            <p class="b-popover__text">Бесплатно при заказе
                от <?= WordHelper::numberFormat($arResult['DELIVERY']['FREE_FROM'], 0) ?> ₽</p>
        <?php } ?>
        <p class="b-popover__text<?php if ($arResult['DELIVERY']['FREE_FROM']) { ?> b-popover__text--last<?php } ?>">
            <?= $arResult['DELIVERY']['FREE_FROM'] ? 'Или доставка' : 'Доставка'?>
            от <?= WordHelper::numberFormat($arResult['DELIVERY']['PRICE'], 0) ?> ₽
        </p>
    </div>
</div>
