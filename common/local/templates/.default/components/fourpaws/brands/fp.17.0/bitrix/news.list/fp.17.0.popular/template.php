<?if (!defined('B_PROLOG_INCLUDED')||B_PROLOG_INCLUDED!==true) {
    die();
}
/**
 * Популярные бренды
 *
 * @updated: 21.12.2017
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
    return;
}

?><h2 class="b-title b-title--h1 b-title--catalog-brands"><?=\Bitrix\Main\Localization\Loc::getMessage('POPULAR_BRANDS.TITLE')?></h2>

<div class="b-popular-brand b-popular-brand--brands"><?php
    foreach ($arResult['ITEMS'] as $arItem) {
        ?><div class="b-popular-brand-item b-popular-brand-item--brands b-popular-brand-item--no-name">
            <a class="b-popular-brand-item__link b-popular-brand-item__link--brands b-popular-brand-item__link--no-name" title="<?=$arItem['NAME']?>" href="<?=$arItem['DETAIL_PAGE_URL']?>"><?php
                if ($arItem['PRINT_PICTURE']) {
                    ?><img class="b-popular-brand-item__image js-image-wrapper" src="<?=$arItem['PRINT_PICTURE']['SRC']?>" alt="<?=$arItem['NAME']?>" title="<?=$arItem['NAME']?>"><?php
                }
            ?></a>
        </div><?php
    }
?></div>

<hr class="b-hr b-hr--brands"><?php
