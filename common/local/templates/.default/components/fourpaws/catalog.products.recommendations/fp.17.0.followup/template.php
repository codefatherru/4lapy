<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED')||B_PROLOG_INCLUDED!==true) {
    die();
}
/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var FourPawsCatalogProductsRecommendations $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 */
$arParams['WRAP_CONTAINER_BLOCK'] = $arParams['WRAP_CONTAINER_BLOCK'] ?? 'N';
$arParams['SHOW_TOP_LINE'] = $arParams['SHOW_TOP_LINE'] ?? 'N';

//
// Если выбрана отложенная загрузка результата, то отправляем ajax-запрос
//
if ($arResult['RESULT_TYPE'] === 'INITIAL' && $arParams['DEFERRED_LOAD'] === 'Y') {
    $signer = new \Bitrix\Main\Security\Sign\Signer();
    $signedTemplate = $signer->sign($templateName, 'catalog.products.recommendations');
    $signedParams = $signer->sign(
        base64_encode(serialize($arResult['ORIGINAL_PARAMETERS'])),
        'catalog.products.recommendations'
    );
    ?>
    <script type="text/javascript">
        new FourPawsCatalogProductsRecommendationsComponent({
            siteId: '<?=\CUtil::JSEscape(SITE_ID)?>',
            componentPath: '<?=\CUtil::JSEscape($componentPath)?>',
            bigData: <?=\CUtil::PhpToJSObject($arResult['BIG_DATA_SETTINGS'])?>,
            template: '<?=\CUtil::JSEscape($signedTemplate)?>',
            ajaxId: '<?=\CUtil::JSEscape($arParams['AJAX_ID'])?>',
            parameters: '<?=\CUtil::JSEscape($signedParams)?>',
            containerSelector: '#followup_products_cont',
            sliderSelector: '.js-popular-product'
        });
    </script>
    <?php

    echo '<div id="followup_products_cont"></div>';

    return;
}

//
// Вывод результата
//
if ($arResult['RESULT_TYPE'] === 'RESULT') {
    if ($arParams['DEFERRED_LOAD'] === 'Y') {
        ob_start();
    }

    if ($arResult['PRODUCTS']) {
        if ($arParams['WRAP_CONTAINER_BLOCK'] === 'Y') {
            echo '<div class="b-container">';
        }
    
        if ($arParams['SHOW_TOP_LINE'] === 'Y') {
            echo '<div class="b-line b-line--pet b-line--shopping-bargain"></div>';
        }

        ?><section class="b-common-section" data-url="/ajax/catalog/product-info/">
            <div class="b-common-section__title-box b-common-section__title-box--sale b-common-section__title-box--shopping-bargain">
                <h2 class="b-title b-title--sale b-title--shopping-bargain"><?=Loc::getMessage('FOLLOWUP_PRODUCTS.TITLE')?></h2><?php
            ?></div>
            <div class="b-common-section__content b-common-section__content--sale b-common-section__content--shopping-bargain js-popular-product"><?php
                $i = 0;
                foreach ($arResult['PRODUCTS'] as $product) {
                    /** @var \FourPaws\Catalog\Model\Product $product */
                    $productId = $product->getId();
                    $APPLICATION->IncludeComponent(
                        'fourpaws:catalog.element.snippet',
                        'vertical',
                        [
                            'PRODUCT' => $product,
                            'BIG_DATA' => [
                                'RCM_ID'       => $arResult['recommendationIdToProduct'][$productId] ?? '',
                                'cookiePrefix' => $arResult['BIG_DATA_SETTINGS']['js']['cookiePrefix'] ?? '',
                                'cookieDomain' => $arResult['BIG_DATA_SETTINGS']['js']['cookieDomain'] ?? '',
                                'serverTime'   => $arResult['BIG_DATA_SETTINGS']['js']['serverTime'] ?? '',
                            ],
                            'COUNTER' => $i,
                        ],
                        $component,
                        [
                            'HIDE_ICONS' => 'Y'
                        ]
                    );
                    $i++;
                }
            ?></div>
        </section><?php

        if ($arParams['WRAP_CONTAINER_BLOCK'] === 'Y') {
            echo '</div>';
        }
    }

    if ($arParams['DEFERRED_LOAD'] === 'Y') {
        // для отложенной загрузки через ajax-запрос результат отдаем в виде json
        $result = [];
        $result['HTML'] = ob_get_clean();
        $result['JS'] = \Bitrix\Main\Page\Asset::getInstance()->getJs();
        echo \Bitrix\Main\Web\Json::encode($result);
    }
}
