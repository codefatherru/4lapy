<?php

use Bitrix\Sale\Order;
use Picqer\Barcode\BarcodeGeneratorPNG;
use FourPaws\KioskBundle\Service\KioskService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$barcodeGenerator = new BarcodeGeneratorPNG();

/** @var Order $order */
$order     = $arResult['ORDER'];
$offer     = $arResult['OFFER'];
$coupon    = $arResult['COUPON'];
$promocode = $coupon['UF_PROMO_CODE'];
?>

<div class="b-container">
    <h1 class="b-title b-title--h1 b-title--order">Спасибо, что вы творите добро вместе с нами!</h1>
    <div class="b-order">
        <div class="b-order__block b-order__block--no-border b-order__block--no-flex">
            <div class="b-order__content b-order__content--no-border b-order__content--no-padding b-order__content--no-flex">
                <hr class="b-hr b-hr--order b-hr--top-line"/>
                <h2 class="b-title b-title--order-heading b-title--block">
                    Ваш заказ №<strong><?=$order->getField('ACCOUNT_NUMBER')?></strong> оформлен
                </h2>
                <div class="b-order__text-block">и&nbsp;будет&nbsp;доставлен&nbsp;в <b><?=$arResult['SHELTER']?></b></div>
                <? if ($arResult['EXIST_COUPON'] || $arResult['AVAILABLE_COUPONS']) { ?>
                    <hr class="b-hr b-hr--order b-hr--top-line"/>
                <? } ?>
                <div data-b-dobrolap-prizes data-order-id="<?=$order->getField('ACCOUNT_NUMBER')?>" data-url="<?=$arResult['GET_COUPON_URL']?>">
                    <? /*if ($arResult['EXIST_COUPON']) { ?>
                        <div data-b-dobrolap-prizes="coupon-section"><? //FIXME Этот html практически целиком дублирует блок <div data-b-dobrolap-prizes="coupon-section"> в www/deploy/release/src/PersonalBundle/Service/PersonalOffersService.php:978 ?>
                            <div class="b-order__text-block">
                                <strong>А вот и сюрприз для Вас!</strong>
                                <br/><br/>
                                <div class="b-dobrolap-coupon" data-b-dobrolap-coupon data-coupon="<?= $promocode ?>">
                                    <div class="b-dobrolap-coupon__item b-dobrolap-coupon__item--info">
                                        <div class="b-dobrolap-coupon__discount">
                                            <span class="b-dobrolap-coupon__discount-big"><?= ($offer["PROPERTY_DISCOUNT_VALUE"] ? $offer["PROPERTY_DISCOUNT_VALUE"] . "%" : $offer["PROPERTY_DISCOUNT_CURRENCY_VALUE"] . " ₽") ?></span>
                                            <span class="b-dobrolap-coupon__discount-text b-dobrolap-coupon__discount-text--desktop"><?= $offer["PREVIEW_TEXT"] ?></span>
                                            <span class="b-dobrolap-coupon__discount-text b-dobrolap-coupon__discount-text--mobile"><?= $offer["PREVIEW_TEXT"] ?></span>
                                        </div>
                                        <div class="b-dobrolap-coupon__deadline">
                                            скидка действует по&nbsp;промо-коду до&nbsp;<?= $offer["PROPERTY_ACTIVE_TO_VALUE"] ?>
                                        </div>
                                    </div>

                                    <div class="b-dobrolap-coupon__item b-dobrolap-coupon__item--promo">
                                        <div class="b-dobrolap-coupon__code">
                                            <span class="b-dobrolap-coupon__code-text">Промо-код</span>
                                            <strong><?= $promocode ?></strong>

                                            <button class="b-button b-button--outline-white b-dobrolap-coupon__code-copy" data-b-dobrolap-coupon="copy-btn">Скопировать</button>
                                        </div>

                                        <div class="b-dobrolap-coupon__barcode">
                                            <img src="data:image/png;base64,<?= base64_encode($barcodeGenerator->getBarcode($coupon["UF_PROMO_CODE"], \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2.132310384278889,
                                                127)) ?>" alt="" class="b-dobrolap-coupon__barcode-image"/>
                                        </div>

                                        <button class="b-button b-button--outline-grey b-button--full-width b-dobrolap-coupon__email-me js-open-popup" data-popup-id="send-email-personal-offers" data-id-coupon-personal-offers="<?= $promocode ?>">
                                            Отправить мне на email
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="b-order__text-block">
                                Это ваш подарок за участие в акции. <br/>
                                Он доступен в разделе <a href="/personal/personal-offers/" class="b-link">Персональные предложения</a>.
                            </div>

                            <hr class="b-hr b-hr--order b-hr--top-line"/>

                            <div class="b-order__text-block">
                                <strong>Как использовать промо-код:</strong><br/><br/>

                                1. На сайте или в мобильном приложении положите неакционные товары в корзину и введите промо-код в специальное поле в корзине.
                                <br/>
                                2. В магазине на кассе перед оплатой неакционных товаров покажите промо-код кассиру.
                                <br/>
                                3. Промо-код можно использовать 1 раз до окончания его срока действия.
                            </div>
                        </div>
                    <? } elseif($arResult['AVAILABLE_COUPONS']) { ?>
                        <div data-b-dobrolap-prizes="choose-section">
                            <div class="b-order__text-block">
                                <strong>Мы говорим спасибо</strong>
                                <br/><br/>
                                В знак благодарности мы приготовили небольшой сюрприз — <br/> фанты «Добролап» с приятными презентами.
                                <br/><br/>
                                Также мы вложим в ваш следующий заказ подарок — памятный магнит.
                            </div>

                            <hr class="b-hr b-hr--order b-hr--top-line"/>

                            <div class="b-order__text-block">
                                <b>А сейчас выберите для себя один из шести сюрпризов, кликнув на любой из них</b>
                            </div>

                            <div class="b-dobrolap-prizes">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <input
                                            type="radio"
                                            name="prize"
                                            value="<?= $i ?>"
                                            class="b-dobrolap-prizes__radio"
                                            id="dobrolap-prize-<?= $i ?>"
                                    />

                                    <label for="dobrolap-prize-<?= $i ?>" class="b-dobrolap-prizes__label" data-b-dobrolap-prizes="choose-section-item">
                                        <img src="/static/build/images/content/dobrolap/icons/dobrolap-<?= $i ?>.png" alt="" class="b-dobrolap-prizes__label-img"/>
                                    </label>
                                <?php endfor ?>
                            </div>
                        </div>
                    <? } */ ?>
                    <div class="b-order__text-block">
                        <p>
                            Перейти в
                            <a class="b-link b-link--inherit b-link--orange <?=$arResult['IS_AUTH'] ? '' : ' js-open-popup'?>" <?=$arResult['IS_AUTH'] ? ' href="/personal/index.php"' : ' data-popup-id="authorization" href="javascript:void(0)"'?>
                               title="личный кабинет">личный
                                                      кабинет</a>.
                        </p>
                        <p>
                            Что-то забыли? Вы можете добавить товары к заказу -
                            <a class="b-link b-link--inherit b-link--orange" href="/" title="">продолжить покупки</a>.
                        </p>
                        <p>Если у вас остались вопросы, свяжитесь с нами по номеру <?=tplvar('phone_main')?></p>
                        <?php
                        if (!KioskService::isKioskMode()) {
                            $APPLICATION->IncludeFile(
                                'blocks/components/social_share.php',
                                [
                                    'shareTitle' => 'Расскажите о нас друзьям',
                                    'shareUrl'   => '/',
                                ],
                                [
                                    'SHOW_BORDER' => false,
                                    'NAME'        => 'Блок Рассказать в соцсетях',
                                    'MODE'        => 'php',
                                ]
                            );
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>