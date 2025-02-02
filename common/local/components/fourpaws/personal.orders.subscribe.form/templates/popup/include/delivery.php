<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array $arParams
 * @var array $arResult
 * @var OrderStorage $storage
 * @var DeliveryResultInterface $delivery
 * @var FourPawsOrderComponent $component
 */

use Doctrine\Common\Collections\ArrayCollection;
use FourPaws\Decorators\SvgDecorator;
use FourPaws\DeliveryBundle\Entity\CalculationResult\DeliveryResultInterface;
use FourPaws\LocationBundle\Entity\Address;
use FourPaws\SaleBundle\Entity\OrderStorage;

/** @var ArrayCollection $addresses */
//$addresses = $arResult['ADDRESSES'];
/** @var Address $address */
$address = $arResult['ADDRESS'];
if(!$address){
    $address = new Address();
}
$selectedAddressId = 0;
$showNewAddressForm = true;
$showNewAddressFormHeader = false;
?>
<script>
    window.dadataConstraintsLocations = <?= $arResult['DADATA_CONSTRAINTS'] ?>;
</script>
<div class="b-input-line b-input-line--delivery-address-current js-hide-if-address <?= $showNewAddressForm ? 'hide js-no-valid' : '' ?>"
    <?= $showNewAddressForm ? 'style="display: none"' : '' ?>>
    <div class="b-input-line__label-wrapper">
        <span class="b-input-line__label">Адрес доставки</span>
    </div>
    <?php /** @var Address $address */ ?>
    <?php
    foreach ($addresses as $address) { ?>
        <div class="b-radio b-radio--tablet-big js-item-saved-delivery-address">
            <input class="b-radio__input"
                   type="radio"
                   name="addressId"
                   id="order-address-<?= $address->getId() ?>"
                <?= ($selectedAddressId && $selectedAddressId === $address->getId()) ? 'checked="checked"' : '' ?>
                   value="<?= $address->getId() ?>"/>
            <label class="b-radio__label b-radio__label--tablet-big"
                   for="order-address-<?= $address->getId() ?>">
                <span class="b-radio__text-label">
                    <?= $address->getFullAddress() ?>
                </span>
            </label>
        </div>
        <?php } ?>
    <div class="b-radio b-radio--tablet-big js-item-saved-delivery-address">
        <input class="b-radio__input <?= $showNewAddressForm ? 'js-no-valid' : '' ?>"
               type="radio"
               name="addressId"
               id="order-address-another"
               data-radio="4"
            <?= $selectedAddressId === 0 ? 'checked="checked"' : '' ?>
               value="0">
        <label class="b-radio__label b-radio__label--tablet-big <?= $showNewAddressForm ? 'js-order-address-another' : '' ?>"
               for="order-address-another">
            <span class="b-radio__text-label">Доставить по другому адресу…</span>
        </label>
    </div>
</div>
<div class="b-radio-tab__new-address js-form-new-address <?= $selectedDelivery->getDeliveryId() !== $delivery->getDeliveryId() ? 'js-hidden-valid-fields' : 'active' ?>"
     data-address-order-delivery-update-subscribe="true"
    <?= $showNewAddressForm ? 'style="display:block"' : '' ?>>
    <div class="b-input-line b-input-line--new-address">
        <div class="b-input-line__label-wrapper b-input-line__label-wrapper--back-arrow">
            <?php if ($showNewAddressFormHeader) {
                ?>
                <span class="b-input-line__label">Новый адрес доставки</span>
                <a class="b-link b-link--back-arrow js-back-list-address"
                   href="javascript:void(0);"
                   title="Назад">
                <span class="b-icon b-icon--back-long">
                    <?= new SvgDecorator('icon-back-form', 13, 11) ?>
                </span>
                    <span class="b-link__back-word">Вернуться </span>
                    <span class="b-link__mobile-word">к списку</span>
                </a>
                <?php
            } ?>
        </div>
    </div>
    <div class="b-input-line b-input-line--street js-order-address-street">
        <div class="b-input-line__label-wrapper">
            <label class="b-input-line__label" for="order-address-street">Улица
            </label><span class="b-input-line__require">(обязательно)</span>
        </div>
        <div class="b-input b-input--registration-form">
            <input class="b-input__input-field b-input__input-field--registration-form"
                   type="text"
                   id="order-address-street"
                   placeholder=""
                   name="street"
                   data-url=""
                   value="<?= $address->getStreet() ?>"/>
            <div class="b-error"><span class="js-message"></span>
            </div>
        </div>
    </div>
    <div class="b-radio-tab__address-house">
        <div class="b-input-line b-input-line--house b-input-line--house-address js-small-input js-only-number js-order-address-house">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="order-address-house">Дом
                </label><span class="b-input-line__require">(обязательно)</span>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form"
                       type="text"
                       id="order-address-house"
                       placeholder=""
                       name="house"
                       data-url=""
                       value="<?= $address->getHouse() ?>"/>
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
        <div class="b-input-line b-input-line--house">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="order-address-part">Корпус
                </label>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-only-number js-housing js-no-valid"
                       id="order-address-part"
                       name="building"
                       type="text"
                       value="<?= $address->getHousing() ?>"/>
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
        <div class="b-input-line b-input-line--house">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="order-address-entrance">Подъезд
                </label>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-only-number js-entrance js-no-valid"
                       id="order-address-entrance"
                       name="porch"
                       value="<?= $address->getEntrance() ?>"/>
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
        <div class="b-input-line b-input-line--house">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="order-address-floor">Этаж
                </label>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-only-number js-floor js-no-valid"
                       id="order-address-floor"
                       name="floor"
                       type="text"
                       value="<?= $address->getFloor() ?>"/>
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
        <div class="b-input-line b-input-line--house">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="order-address-apart">Кв.,
                    офис
                </label>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-only-number js-office js-no-valid"
                       id="order-address-apart"
                       name="apartment"
                       type="text"
                       value="<?= $address->getFlat() ?>"/>
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
    </div>
    <div class="b-input-line b-radio-tab__address-map js-courierdelivery-map hidden">
        <div class="b-radio-tab-map b-radio-tab-map--order">
            <div class="b-radio-tab-map__label-wrapper">
                <a href="javascript:void(0);" class="b-radio-tab-map__label js-toogle-courierdelivery-map">
                <span class="b-radio-tab-map__label-inner">
                    Место доставки на карте
                </span>
                    <span class="b-icon b-icon--map">
                    <?= new SvgDecorator('icon-arrow-down', 10, 12) ?>
                </span>
                </a>
            </div>
            <div class="b-radio-tab-map__map-wrapper">
                <div class="b-radio-tab-map__map" id="map_courier_delivery"></div>
            </div>
        </div>
    </div>
</div>