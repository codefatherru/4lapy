<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Sale\Order;
use Bitrix\Main\Grid\Declension;
use FourPaws\DeliveryBundle\Helpers\DeliveryTimeHelper;
use FourPaws\StoreBundle\Entity\Store;

/**
 * @var CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 */

/** @var Order $order */
$order = $arResult['ORDER'];

$calcResult = new \Bitrix\Sale\Delivery\CalculationResult();
?>
<div class="b-container">
    <h1 class="b-title b-title--h1 b-title--order">
        Спасибо!
    </h1>
    <div class="b-order">
        <?php /*
        <div class="b-order__text-block b-order__text-block--top-line b-order__text-block--light js">
            <span class="b-icon b-icon--clock">
              <svg class="b-icon__svg" viewBox="0 0 16 16 " width="16px" height="16px">
                <use class="b-icon__use" xlink:href="icons.svg#icon-clock">
                </use>
              </svg>
            </span>Идёт подсчёт баллов ...
        </div>
         */ ?>
        <?php if (!empty($arResult['ORDER_PROPERTIES']['BONUS_COUNT'])) { ?>
            <div class="b-order__text-block b-order__text-block--top-line js">
                <p>Вы получили <?= $arResult['ORDER_PROPERTIES']['BONUS_COUNT'] ?>
                    <?= (new Declension($arResult['ORDER_PROPERTIES']['BONUS_COUNT']))->get(
                        'бонусный балл',
                        'бонусных балла',
                        'бонусных баллов'
                    ) ?>.
                    Узнать, <a class="b-link b-link--inherit b-link--orange"
                               href="/customer/bonus-program/"
                               title="как получить и потратить баллы.">
                        как получить и потратить баллы.</a>
                </p>
            </div>
        <?php } ?>
        <hr class="b-hr b-hr--order b-hr--top-line"/>
        <div class="b-order__block b-order__block--no-border">
            <div class="b-order__content b-order__content--no-border b-order__content--step-five">
                <h2 class="b-title b-title--order-heading b-title--block"><? $APPLICATION->ShowTitle() ?>
                </h2>
                <?php if ($arResult['ORDER_PROPERTIES']['EMAIL']) {
                    ?>
                    <div class="b-order__text-block">
                        <p><?= $arResult['ORDER_PROPERTIES']['NAME'] ?>, мы отправили письмо на адрес
                            <a class="b-link b-link--blue-bold"
                               href="mailto:<?= $arResult['ORDER_PROPERTIES']['EMAIL'] ?>"
                               title=""><?= $arResult['ORDER_PROPERTIES']['EMAIL'] ?>
                            </a>
                            со всеми подробностями заказа.
                        </p>
                    </div>
                    <?php
                } ?>
                <h2 class="b-title b-title--order-heading b-title--block">Как получить заказ</h2>
                <div class="b-order__text-block">
                    <?php if ($arResult['ORDER_DELIVERY']['IS_DPD_PICKUP']) {
                        ?>
                        <p>Ваш заказ вы можете получить <b>в <?= DeliveryTimeHelper::showTime(
                                    $calcResult,
                                    $arResult['ORDER_DELIVERY']['DELIVERY_DATE']
                                ) ?> </b> в пункте самовывоза по
                            адресу
                            < b><?= $arResult['ORDER_DELIVERY']['ADDRESS'] ?></b></p>
                        <?php if ($arResult['ORDER_DELIVERY']['SCHEDULE']) {
                            ?>
                            <p><b>Время работы: </b><?= $arResult['ORDER_DELIVERY']['SCHEDULE'] ?>
                            </p>
                            <?php
                        } ?>
                        <p><b>Хранение заказа: </b>Обратите внимание, что заказ будет храниться 5 дней. После этого
                            заказ
                            нужно будет делать заново на нашем сайте или по телефону.</p>
                        <?php
                    } elseif ($arResult['ORDER_DELIVERY']['IS_PICKUP']) {
                        ?>
                        <p>Ваш заказ вы можете получить <b>в <?= DeliveryTimeHelper::showTime(
                                    $calcResult,
                                    $arResult['ORDER_DELIVERY']['DELIVERY_DATE']
                                ) ?></b> в нашем магазине по
                            адресу
                            <b><?= $arResult['ORDER_DELIVERY']['ADDRESS'] ?></b></p>
                        <?php if ($arResult['ORDER_DELIVERY']['SCHEDULE']) {
                            ?>
                            <p><b>Время работы: </b><?= $arResult['ORDER_DELIVERY']['SCHEDULE'] ?>
                            </p>
                            <?php
                        } ?>
                        <p><b>Хранение заказа: </b>Обратите внимание, что заказ будет храниться 5 дней. После этого
                            заказ
                            нужно будет делать заново на нашем сайте или по телефону.</p>
                        <?php
                    } else {
                        ?>
                        <p>Ваш заказ будет доставлен <b>в <?= DeliveryTimeHelper::showTime(
                                    $calcResult,
                                    $arResult['ORDER_DELIVERY']['DELIVERY_DATE'],
                                    ['SHOW_TIME' => false, 'SHORT' => true]
                                ) ?></b> по
                            адресу
                            <b><?= $arResult['ORDER_DELIVERY']['ADDRESS'] ?></b></p>
                        <p><b>Время доставки: </b><?= $arResult['ORDER_DELIVERY']['DELIVERY_INTERVAL'] ?></p>
                        <?php
                    } ?>
                </div>
            </div>
            <?php if ($arResult['ORDER_DELIVERY']['IS_PICKUP'] &&
                $arResult['ORDER_DELIVERY']['SELECTED_SHOP'] instanceof Store
            ) {
                /** @var Store $shop */
                $shop = $arResult['ORDER_DELIVERY']['SELECTED_SHOP'];
                ?>
                <aside class="b-order__list b-order__list--map">
                    <div class="b-order__map-wrapper"
                         id="map-2"
                         data-coords="[<?= $shop->getLatitude() ?>, <?= $shop->getLongitude() ?>]">
                    </div>
                </aside>
                <?php
            } ?>
        </div>
        <div class="b-order__block b-order__block--no-border b-order__block--no-flex">
            <div class="b-order__content b-order__content--no-border b-order__content--no-padding b-order__content--no-flex">
                <?php /* @todo частичное получение заказа */ ?>
                <?php /*
                <hr class="b-hr b-hr--order b-hr--step-five"/>
                <h2 class="b-title b-title--order-heading b-title--block">Заказ №11020042 оформлен</h2>
                <div class="b-order__text-block">
                    <p>В нем находятся товары "под заказ". Мы также отправили на адрес
                        <a class="b-link b-link--blue-bold" href="mailto:ya_zakazal@gmail.com" title="">ya_zakazal@gmail.com
                        </a>письмо со всеми подробностями заказа.
                    </p>
                    <p>Условия оплаты и доставки совпадают с заказом выше.</p>
                </div>
                <h2 class="b-title b-title--order-heading b-title--block">Как получить заказ</h2>

                <div class="b-order__text-block">
                    <p>Ваш заказ вы можете получить <b>в пятницу, 15 сентября c 15:00.</b></p>
                </div>
                 */ ?>
                <?php if ($arResult['ORDER_REGISTERED']) {
                    ?>
                    <hr class="b-hr b-hr--order"/>
                    <div class="b-order__text-block">
                        <h5 class="b-order__text-list-heading">Также мы создали вам личный кабинет, где вы можете:</h5>
                        <ul class="b-order__text-list">
                            <li class="b-order__text-item">отслеживать статус заказа;</li>
                            <li class="b-order__text-item">повторять заказы в 1 клик;</li>
                            <li class="b-order__text-item">управлять адресами доставки.</li>
                        </ul>
                        <p>Перейти в
                            <a class="b-link b-link--inherit b-link--orange" href="/personal/" title="">
                                личный кабинет
                            </a>.
                        </p>
                        <p>Перейти на
                            <a class="b-link b-link--inherit b-link--orange" href="/" title="">
                                главную страницу
                            </a>.
                        </p>
                    </div>
                    <?php
                } ?>
            </div>
        </div>
    </div>
</div>
