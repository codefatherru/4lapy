<?php

use Bitrix\Sale\Basket;
use FourPaws\App\Application;
use FourPaws\DeliveryBundle\Entity\CalculationResult\CalculationResultInterface;
use FourPaws\DeliveryBundle\Entity\CalculationResult\PickupResultInterface;
use FourPaws\DeliveryBundle\Helpers\DeliveryTimeHelper;
use FourPaws\DeliveryBundle\Service\DeliveryService;
use FourPaws\Helpers\CurrencyHelper;
use FourPaws\LocationBundle\LocationService;
use FourPaws\PersonalBundle\Entity\OrderSubscribe;
use FourPaws\SaleBundle\Entity\OrderStorage;
use FourPaws\StoreBundle\Entity\Store;

/**
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var FourPawsPersonalCabinetOrdersSubscribeFormComponent $component
 */

/** @var CalculationResultInterface $delivery */
$delivery = $arResult['DELIVERY'];
/** @var CalculationResultInterface $deliveryDostavista */
$deliveryDostavista = $arResult['DELIVERY_DOSTAVISTA'];
/** @var PickupResultInterface $pickup */
$pickup = $arResult['PICKUP'];
/** @var CalculationResultInterface $selectedDelivery */
$selectedDelivery = $arResult['SELECTED_DELIVERY'];

$deliveryService = $component->getDeliveryService();

/** @var Store $selectedShop */
$selectedShop = $arResult['SELECTED_SHOP'];

/** @var Basket $basket */
$basket = $arResult['BASKET'];

$subscribeIntervals = $component->getOrderSubscribeService()->getFrequencies();

$daysOfWeek = ["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];

// выбранный магазин
$selectedShopCode = '';
if ($pickup && $selectedDelivery->getDeliveryId() === $pickup->getDeliveryId()) {
    $selectedShopCode = $arResult['SELECTED_SHOP']->getXmlId();
}

if ($arResult['ECOMMERCE_VIEW_SCRIPT']) {
    echo $arResult['ECOMMERCE_VIEW_SCRIPT'];
}

$orderSubscribe = $component->getOrderSubscribe();
if($orderSubscribe){
    $payWithBonus = $orderSubscribe->isPayWithbonus();
}
?>

<div class="b-popup-subscribe-delivery__inner js-step2-inner-subscribe-delivery">
    <div class="b-tab-list">
        <ul class="b-tab-list__list js-scroll-tabs-subscribe-delivery">
            <li class="b-tab-list__item active-link" data-step-subscribe-delivery="1" data-show-step1-subscribe-delivery="true">
                <span class="b-tab-list__step">Шаг </span>1. Товары в подписке
            </li>
            <li class="b-tab-list__item active" data-step-subscribe-delivery="2">
                <span class="b-tab-list__step">Шаг </span>2. Доставка и оплата
            </li>
        </ul>
    </div>
    <div class="b-product-subscribe-delivery b-order">

        <div class="b-order__block b-order__block--step-two">
            <div class="b-order__content js-order-content-block">
                <article class="b-order-contacts">
                    <header class="b-order-contacts__header">
                        <h2 class="b-title b-title--order-tab">Удобный для вас способ получения в</h2>
                        <a class="b-link b-link--select b-link--order-step js-open-popup"
                           href="javascript:void(0);"
                           title="<?= $arResult['SELECTED_CITY']['NAME'] ?>"
                           data-popup-id="pick-city">
                            <?= $arResult['SELECTED_CITY']['TYPE'] === LocationService::TYPE_CITY ? 'г. ' : '' ?><?= $arResult['SELECTED_CITY']['NAME'] ?>
                        </a>
                    </header>
                    <form class="b-order-contacts__form b-order-contacts__form--choose-delivery js-form-validation"
                          data-url="<?= $arResult['URL']['DELIVERY_VALIDATION'] ?>"
                          data-form-step2-subscribe="true"
                          method="post"
                          id="order-step">
                        <input type="hidden" name="shopId" class="js-no-valid"
                               value="<?= /** @noinspection PhpUnhandledExceptionInspection */
                               $pickup ? $pickup->getSelectedShop()->getXmlId() : '' ?>">
                        <input type="hidden" name="delyveryType"
                               value="<?= (!empty($arResult['SPLIT_RESULT'])) ? 'twoDeliveries' : 'oneDelivery' ?>"
                               class="js-no-valid">
                        <input type="hidden" name="deliveryTypeId"
                               value="<?
                               if($selectedDelivery){
                                   echo $selectedDelivery->getDeliveryId();
                               }
                               else if($delivery){
                                   echo $delivery->getDeliveryId();
                               }
                               else if($pickup){
                                   echo $pickup->getDeliveryId();
                               }
                               ?>"
                               class="js-no-valid">
                        <input type="hidden" name="deliveryCoords" value="">
                        <div class="b-choice-recovery b-choice-recovery--order-step">
                            <?php if ($delivery) { ?>
                                <?
                                $selectedDel = ($selectedDelivery->getDeliveryCode() == DeliveryService::DELIVERY_DOSTAVISTA_CODE || $selectedDelivery->getDeliveryCode() == DeliveryService::INNER_DELIVERY_CODE) ? $delivery : $selectedDelivery;
                                ?>
                                <input <?= $deliveryService->isDelivery($selectedDel) ? 'checked="checked"' : '' ?>
                                        class="b-choice-recovery__input js-recovery-telephone js-delivery"
                                        data-set-delivery-type="<?= $delivery->getDeliveryId() ?>"
                                        id="order-delivery-address"
                                        type="radio"
                                        name="deliveryId"
                                        data-text="Доставка курьером"
                                        value="<?= $delivery->getDeliveryId() ?>"
                                        data-delivery="<?= $delivery->getPrice() ?>"
                                        data-full="<?= $delivery->getStockResult()->getOrderable()->getPrice() ?>"
                                        data-check="js-list-orders-static"/>
                                <label class="b-choice-recovery__label b-choice-recovery__label--left b-choice-recovery__label--order-step"
                                       for="order-delivery-address">
                                    <span class="b-choice-recovery__main-text">
                                        <span class="b-choice-recovery__main-text">
                                            <span class="b-choice-recovery__first">Доставка</span>
                                            <span class="b-choice-recovery__second">курьером</span>
                                        </span>
                                    </span>
                                    <span class="b-choice-recovery__addition-text js-cur-pickup">
                                        <?= /** @noinspection PhpUnhandledExceptionInspection */
                                        DeliveryTimeHelper::showTime($delivery) ?>,
                                        <span class="js-delivery--price"><?= $delivery->getPrice() ?></span>₽
                                    </span>
                                    <span class="b-choice-recovery__addition-text b-choice-recovery__addition-text--mobile js-cur-pickup-mobile">
                                        <?= /** @noinspection PhpUnhandledExceptionInspection */
                                        DeliveryTimeHelper::showTime($delivery, ['SHORT' => true]) ?>,
                                        <span class="js-delivery--price"><?= $delivery->getPrice() ?></span>₽
                                    </span>
                                </label>
                            <?php }
                            if ($pickup) {
                                $available = $arResult['PICKUP_STOCKS_AVAILABLE'];
                                if ($arResult['PARTIAL_PICKUP_AVAILABLE']) {
                                    $price = $available->getPrice();
                                } else {
                                    $price = $pickup->getStockResult()->getPrice();
                                } ?>
                                <input <?= $deliveryService->isPickup($selectedDelivery) ? 'checked="checked"' : '' ?>
                                        class="b-choice-recovery__input js-recovery-email js-myself-shop js-delivery"
                                        data-set-delivery-type="<?= $pickup->getDeliveryId()?>"
                                        id="order-delivery-pick-up"
                                        type="radio"
                                        name="deliveryId"
                                        data-text="Самовывоз"
                                        value="<?= $pickup->getDeliveryId() ?>"
                                        data-delivery="<?= $pickup->getPrice() ?>"
                                        data-full="<?= $price ?>"
                                        data-check="js-list-orders-cont"/>
                                <label class="b-choice-recovery__label b-choice-recovery__label--right b-choice-recovery__label--order-step js-open-popup"
                                       for="order-delivery-pick-up"
                                       data-popup-id="popup-order-stores">
                                    <span class="b-choice-recovery__main-text">Самовывоз</span>
                                    <span class="b-choice-recovery__addition-text js-my-pickup js-pickup-tab">
                                        <?= /** @noinspection PhpUnhandledExceptionInspection */
                                        DeliveryTimeHelper::showTime(
                                            $pickup,
                                            [
                                                'SHOW_TIME' => !$deliveryService->isDpdPickup(
                                                    $pickup
                                                ),
                                            ]
                                        ) ?>, <?= mb_strtolower(CurrencyHelper::formatPrice($pickup->getPrice(),
                                                    true)) ?>
                                    </span>
                                    <span class="b-choice-recovery__addition-text b-choice-recovery__addition-text--mobile js-my-pickup js-pickup-tab">
                                        <?= /** @noinspection PhpUnhandledExceptionInspection */
                                        DeliveryTimeHelper::showTime(
                                            $pickup,
                                            [
                                                'SHORT' => true,
                                                'SHOW_TIME' => !$deliveryService->isDpdPickup(
                                                    $pickup
                                                ),
                                            ]
                                        ) ?>, <?= CurrencyHelper::formatPrice($pickup->getPrice(), false) ?>
                                    </span>
                                </label>
                                <?php
                            } ?>
                        </div>
                        <ul class="b-radio-tab js-myself-shop">
                            <?php if ($delivery) {
                                ?>
                                <li class="b-radio-tab__tab js-telephone-recovery"
                                    <?= $selectedDelivery->getDeliveryId() !== $delivery->getDeliveryId() ? 'style="display:none"' : '' ?>>
                                    <?php
                                        $currentDelivery = $delivery;
                                        include 'include/delivery.php';
                                        include 'include/delivery_first_date.php';
                                    ?>
                                </li>
                                <?php
                            } ?>
                            <?php if ($pickup) {
                                ?>
                                <li class="b-radio-tab__tab js-email-recovery"
                                    <?= $selectedDelivery->getDeliveryId() !== $pickup->getDeliveryId() ? 'style="display:none"' : '' ?>>
                                    <?php
                                        $currentDelivery = $pickup;
                                        include 'include/pickup.php';
                                        include 'include/delivery_first_date.php';
                                    ?>
                                </li>
                                <?php
                            } ?>
                        </ul>
                        <div class="b-checkbox b-checkbox--withdraw-bonuses-order">
                            <input class="b-checkbox__input js-no-valid" type="checkbox" name="subscribeBonus" id="withdraw_bonuses" value="1" required="required" <?=(!$orderSubscribe || $payWithBonus) ? 'checked' : ''?> />
                            <span class="b-error"><span class="js-message"></span></span>
                            <label class="b-checkbox__name" for="withdraw_bonuses">
                                Списывать все доступные баллы на&nbsp;заказы по&nbsp;подписке
                            </label>
                        </div>

                        <input type="hidden" name="changeNextDelivery" value="<?=($arResult['IS_SINGLE_SUBSCRIBE']) ? 1 : 0?>">
                    </form>
                </article>
            </div>
        </div>

        <?php
        /*$currentShopInfo = $pickup ? $component->getShopListService()->toArray(
            $component->getShopListService()->getOneShopInfo($pickup->getSelectedShop()->getXmlId(), $storage, $pickup)
        ) : [];*/
        ?>
        <script>
            window.fullBasket = <?= CUtil::PhpToJSObject(array_values($component->getBasketItemData($component->getBasket()))) ?>;
            // window.currentShop = <?= CUtil::PhpToJSObject($currentShopInfo) ?>;
            window.currentShop = <?= CUtil::PhpToJSObject([]) ?>;
        </script>
    </div>
    <div class="b-popup-subscribe-delivery__btns">
        <a href="javascript:void(0);" class="b-button b-button--back-subscribe-delivery" data-show-step1-subscribe-delivery="true" title="Назад">
            Назад
        </a>
        <a href="javascript:void(0);" class="b-button b-button--next-subscribe-delivery js-valid-dynamic-out-sub" data-submit-add-subscribe-delivery="true" title="Далее">
            Сохранить изменения
        </a>
        <a href="javascript:void(0);"
           class="b-button b-button--cancel-subscribe-delivery"
           data-close-subscribe-delivery-popup="true"
           title="Отменить">
            Отменить
        </a>
    </div>
</div>
