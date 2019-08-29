<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php'; ?>
<?php $APPLICATION->SetTitle('Марки'); ?>

<div class="toys-landing">
    <div class="toys-landing__header">
        <img src="/upload/toys-landing/header.jpg" alt="" />
    </div>

    <div class="toys-landing__steps">
        <div class="b-container toys-landing__steps-container">
            <ul class="toys-landing__steps-list">
                <li class="toys-landing__steps-item">
                    <img src="/upload/toys-landing/step-1.svg" alt="" class="toys-landing__steps-image" />

                    <div>
                        <p class="toys-landing__steps-title">Копи марки</p>
                        <p class="toys-landing__steps-description">1 марка = 400 рублей</p>
                    </div>
                </li>

                <li class="toys-landing__steps-item">
                    <img src="/upload/toys-landing/step-2.svg" alt="" class="toys-landing__steps-image" />

                    <div>
                        <p class="toys-landing__steps-title">Выбирай умные игрушки</p>
                        <p class="toys-landing__steps-description">Покупай со скидкой до 50%</p>
                    </div>
                </li>

                <li class="toys-landing__steps-item">
                    <img src="/upload/toys-landing/step-3.svg" alt="" class="toys-landing__steps-image" />

                    <div>
                        <p class="toys-landing__steps-title">Занимайся с питомцем</p>
                        <p class="toys-landing__steps-description">Развивай любознательность, обучай и играй с удовольствием</p>
                    </div>
                </li>
            </ul>

            <? $APPLICATION->IncludeComponent('articul:stamps.balance', '') ?>
        </div>
    </div>

    <? $APPLICATION->IncludeComponent('articul:stamps.products', '') ?>

    <div class="b-container">
        <ul class="toys-landing-benefits">
            <li class="toys-landing-benefits__item">
                <img src="/upload/toys-landing/benefit-1.png" alt="" class="toys-landing-benefits__image" />

                <div class="toys-landing-benefits__text">
                    <strong>Развивают</strong><br />
                    интеллект, внимание<br/>
                    и&nbsp;ловкость
                </div>
            </li>
            <li class="toys-landing-benefits__item">
                <img src="/upload/toys-landing/benefit-2.png" alt="" class="toys-landing-benefits__image" />

                <div class="toys-landing-benefits__text">
                    <strong>Подходят</strong><br />
                    для питомцев <br />
                    от 3-х месяцев
                </div>
            </li>

            <li class="toys-landing-benefits__item">
                <img src="/upload/toys-landing/benefit-3.png" alt="" class="toys-landing-benefits__image" />

                <div class="toys-landing-benefits__text">
                    <strong>Продлевают</strong><br />
                    молодость и&nbsp;радуют <br />
                    питомца
                </div>
            </li>
        </ul>

        <h2 class="toys-landing__check-header">Как отслеживать баланс марок</h2>

        <div class="toys-landing-check">
            <img src="/upload/toys-landing/check.jpg" alt="" class="toys-landing-check__image" />

            <div class="toys-landing-check__sidebar">
                <ul class="toys-landing-check__sidebar-list">
                    <li>
                        <span style="text-transform: uppercase;">
                            <b>
                                делай покупки, получай марки: 1
                                <img src="/upload/toys-landing/logo.png" width="19" height="19" style="transform: translateY(2px)" />
                                = 400р
                            </b>
                        </span>
                    </li>

                    <li>
                        <span style="text-transform: uppercase;">
                            <b>отслеживай марки где удобно:</b> <br />
                            на чеке, <u><a href="/personal/" target="_blank">в личном кабинете</a></u> на сайте
                            и в приложении
                        </span>
                    </li>

                    <li>
                        <span style="text-transform: uppercase;">
                            <b>На сайте и в приложении:</b><br />
                            выбери игру и добавь в корзину
                            нажми “списать марки” <br /><br />

                            <b>при покупке офлайн</b> <br/>
                            вам спишет марки кассир
                        </span>
                    </li>

                    <li>
                        <span style="text-transform: uppercase;">
                            <b>Получи игру и развивай питомца</b>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'; ?>
