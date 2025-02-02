<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var string $phone */ ?>
<div class="b-registration__content b-registration__content--moiety b-registration__content--step">
    <div class="b-registration__text-instruction">Пожалуйста, введите номер телефона</div>
    <form class="b-registration__form js-form-validation js-registration-form"
          data-url="/ajax/user/auth/register-r/"
          method="post">
        <input type="hidden" name="action" value="get">
        <input type="hidden" name="step" value="sendSmsCode">
        <input type="hidden" name="newAction" value="savePhone">
        <div class="b-input-line">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="mobile-number-5">Мобильный телефон</label>
            </div>
            <div class="b-input b-input--registration-form">
                <input class="b-input__input-field b-input__input-field--registration-form"
                       type="tel"
                       name="phone"
                       value="<?= $phone ?>"
                       id="mobile-number-5"
                       placeholder="" />
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
        </div>
        <button class="b-button b-button--social b-button--full-width">Отправить код</button>
    </form>
</div>
<section class="b-registration__additional-info b-registration__additional-info--step">
    <h3 class="b-registration__title-advantage">Зачем это нужно?</h3>
    <ul class="b-social-advantage">
        <li class="b-social-advantage__item">Для оперативной связи по поводу доставки</li>
        <li class="b-social-advantage__item">Для привязки бонусной карты</li>
        <li class="b-social-advantage__item">Телефон можно использовать как логин при входе</li>
    </ul>
</section>
