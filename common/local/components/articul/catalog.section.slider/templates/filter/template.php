<?
/**
 * @var CCatalogSectionSlider $component
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use FourPaws\Decorators\SvgDecorator; ?>



<section class="fashion-category">
    <div class="fashion-category-header-mobile">
        <div class="b-container">
            <div class="fashion-category-header-mobile__content">
                <div class="fashion-category-header-mobile__info">
                    <div class="fashion-category-header-mobile__title">Категории</div>
                    <div class="fashion-category-header-mobile__count-select">Выбрано (<span data-count-select-category-fashion="true"><?=(count($arResult['ELEMENTS']) > 2 ? '3' : count($arResult['ELEMENTS']))?></span>)</div>
                </div>
                <div class="fashion-category-header-mobile__open-filter" data-open-filter-category-fashion="true">
                    <span class="b-icon b-icon--open-filter">
                        <?= new SvgDecorator('icon-open-filter', 19, 14) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="fashion-category-list">
        <? foreach($arResult['ELEMENTS'] as $i => $element) { ?>
        <div class="item-category-landing <?=($i < 3) ? 'active' : ''?>" data-item-filter-category-landing="<?=$i?>" data-url="/ajax/catalog/product-info/">
            <div class="b-container">
                <a href="<?=$component->getSectionLink($element['ID']) ?>" class="item-category-landing__title" target="_blank"><?=$element['NAME']?></a>
                <div class="item-category-landing__content">
                    <div class="item-category-landing__img" style="background-image: url(<?=$arResult['IMAGES'][$element['PROPERTIES']['IMAGE']['VALUE']]?>)">
                        <a href="<?=$component->getSectionLink($element['ID']) ?>" class="item-category-landing__more" target="_blank">
                             Посмотреть все
                        </a>
                    </div>

                    <div class="item-category-landing__slider" data-slider-category-landing="true">
                        <?php
                        foreach ($element['PROPERTIES']['PRODUCTS']['VALUE'] as $xmlId){
                            $product = $component->getProduct($xmlId);
                            if($product){
                                $APPLICATION->IncludeComponent(
                                    'fourpaws:catalog.element.snippet',
                                    'fashion_slider',
                                    [
                                        'PRODUCT'               => $product,
                                        'GOOGLE_ECOMMERCE_TYPE' => sprintf('Модная коллекция - %s', $element['NAME'])
                                    ]
                                );
                            }

                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <? } ?>
    </div>


    <div class="fashion-category__filter" data-content-filter-category-fashion="true">
        <div class="b-container">
            <div class="item-category-landing__title fashion-category__filter-title">
                Все категории
            </div>

            <div class="fashion-category-filter">
                <? foreach($arResult['ELEMENTS'] as $i => $element) { ?>
                    <div class="fashion-category-filter__item <?=($i < 3) ? 'active' : ''?>" data-type-filter-category-fashion="<?=$i?>">
                        <a href="<?=$component->getSectionLink($element['ID']) ?>" class="fashion-category-filter__img-wrap" data-img-type-filter-category-fashion="true" target="_blank">
                            <div class="fashion-category-filter__img" style="background-image: url(<?=$arResult['TITLE_IMAGES'][$element['PROPERTIES']['TITLE_IMAGE']['VALUE']]?>)"></div>
                        </a>
                        <a href="<?=$component->getSectionLink($element['ID']) ?>" class="fashion-category-filter__title" data-title-type-filter-category-fashion="true" target="_blank"><?=$element['NAME']?></a>
                    </div>
                <? } ?>
            </div>
        </div>
    </div>

</section>

