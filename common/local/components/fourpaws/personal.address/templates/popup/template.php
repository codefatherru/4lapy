<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array $arResult */ ?>
<section class="b-popup-pick-city b-popup-pick-city--add-adress js-popup-section" data-popup="edit-adress-popup">
    <a class="b-popup-pick-city__close b-popup-pick-city__close--add-adress js-close-popup"
       href="javascript:void(0);"
       title="Закрыть"></a>
    <div class="b-registration b-registration--add-adress js-title-adress">
        <header class="b-registration__header">
            <div class="b-title b-title--h1 b-title--registration">Новый адрес доставки</div>
        </header>
        <form class="b-registration__form js-form-validation js-delivery-address-query"
              method="post"
              data-url="">
            <input class="js-data-id js-no-valid" name="ID" value="" type="hidden">
            <div class="b-input-line b-input-line--popup-authorization js-name js-small-input js-regular-field-parent">
                <div class="b-input-line__label-wrapper">
                    <label class="b-input-line__label" for="name-adress">Название</label>
                    <span class="b-input-line__require">(например, дом, работа, дача)</span>
                </div>
                <div class="b-input b-input--registration-form">
                    <input class="b-input__input-field b-input__input-field--registration-form"
                           type="text"
                           id="name-adress"
                           placeholder=""
                           name="UF_NAME"/>
                    <div class="b-error"><span class="js-message"></span>
                    </div>
                </div>
            </div>
            <div class="b-input-line b-input-line--popup-authorization js-city js-regular-field-parent">
                <div class="b-input-line__label-wrapper">
                    <label class="b-input-line__label" for="city-adress">Город</label>
                    <span class="b-input-line__require">(обязательно)</span>
                </div>
                <div class="b-input b-input--registration-form">
                    <input class="b-input__input-field b-input__input-field--registration-form"
                           type="text"
                           id="city-adress"
                           placeholder=""
                           name="UF_CITY"/>
                    <div class="b-error"><span class="js-message"></span>
                    </div>
                </div>
            </div>
            <div class="b-input-line b-input-line--popup-authorization js-street js-regular-field-parent">
                <div class="b-input-line__label-wrapper">
                    <label class="b-input-line__label" for="street-adress">Улица</label>
                    <span class="b-input-line__require">(обязательно)</span>
                </div>
                <div class="b-input b-input--registration-form">
                    <input class="b-input__input-field b-input__input-field--registration-form"
                           type="text"
                           id="street-adress"
                           placeholder=""
                           name="UF_STREET"/>
                    <div class="b-error"><span class="js-message"></span>
                    </div>
                </div>
            </div>
            <div class="b-registration__wrapper-input">
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="home-adress">Дом</label>
                        <span class="b-input-line__require">(обязательно)</span>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-home js-small-input"
                               id="home-adress"
                               type="text"
                               name="UF_HOUSE"/>
                        <div class="b-error"><span class="js-message"></span>
                        </div>
                    </div>
                </div>
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="housing-adress">Корпус</label>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-housing js-no-valid"
                               id="housing-adress"
                               type="text"
                               name="UF_HOUSING"/>
                        <div class="b-error"><span class="js-message"></span>
                        </div>
                    </div>
                </div>
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="porch-adress">Подъезд</label>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-entrance js-no-valid"
                               id="porch-adress"
                               type="text"
                               name="UF_ENTRANCE"/>
                        <div class="b-error"><span class="js-message"></span>
                        </div>
                    </div>
                </div>
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="floor-adress">Этаж</label>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-floor js-no-valid"
                               id="floor-adress"
                               type="text"
                               name="UF_FLOOR"/>
                        <div class="b-error"><span class="js-message"></span></div>
                    </div>
                </div>
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="flat-adress">Квартира, офис</label>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-office js-no-valid"
                               id="flat-adress"
                               type="text"
                               name="UF_FLAT"/>
                        <div class="b-error"><span class="js-message"></span>
                        </div>
                    </div>
                </div>
                <div class="b-input-line b-input-line--popup-authorization b-input-line--popup-add-adress">
                    <div class="b-input-line__label-wrapper">
                        <label class="b-input-line__label" for="doorphone-code-adress">Код домофона</label>
                    </div>
                    <div class="b-input b-input--registration-form">
                        <input class="b-input__input-field b-input__input-field--registration-form js-regular-field js-doorphone-code js-no-valid"
                               id="doorphone-code-adress"
                               type="text"
                               name="UF_INTERCOM_CODE"/>
                        <div class="b-error"><span class="js-message"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="b-registration__wrapper-radio b-registration__wrapper-radio--adress">
                <div class="b-checkbox b-checkbox--add-adress js-primary-address">
                    <input class="b-checkbox__input js-no-valid"
                           type="checkbox"
                           name="UF_MAIN"
                           id="main-adress"
                           value="Y"/>
                    <label class="b-checkbox__name b-checkbox__name--add-adress js-primary-address"
                           for="main-adress"><span class="b-checkbox__text">Основной адрес</span>
                    </label>
                </div>
            </div>
            <input type="hidden" name="dadata" id="city-dadata" class="js-no-valid">
            <button class="b-button b-button--subscribe-delivery">Добавить</button>
        </form>
    </div>
</section>
