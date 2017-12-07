<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use FourPaws\Decorators\SvgDecorator;
use FourPaws\App\Application;

/** @var FourPaws\Location\LocationService $locationService */
$locationService = Application::getInstance()->getContainer()->get('location.service');
$currentCity = $locationService->getCurrentCity();
?>
<?php $APPLICATION->IncludeComponent(
        'fourpaws:city.phone',
        'footer'
) ?>
<span class="b-footer-communication__item">
    <a class="b-footer-communication__link"
       href="tel:<?= preg_replace('/[^+\d]/', '', tplvar('phone_main')) ?>"
       title="<?= tplvar('phone_main') ?>">
        <?= tplvar('phone_main') ?>
    </a>
    <?= tplinvis('phone_main') ?>
    <span class="b-footer-communication__description"><?= tplvar('phone_sign_footer', true) ?></span>
</span>
<span class="b-footer-communication__link-block">
    <span class="b-footer-communication__item">
        <a class="b-footer-communication__link" href="javascript:void(0);" title="Перезвоните мне">
            <span class="b-icon b-icon--footer">
                <?= new SvgDecorator('icon-phone-white', 10, 16) ?>
            </span>
            Перезвоните мне
        </a>
    </span>
    <span class="b-footer-communication__item">
        <a class="b-footer-communication__link" href="javascript:void(0);" title="Обратная связь">
            <span class="b-icon b-icon--footer">
                <?= new SvgDecorator('icon-feedback', 16, 11) ?>
            </span>
            Обратная связь
        </a>
    </span>
    <span class="b-footer-communication__item">
        <a class="b-footer-communication__link"
           href="javascript:void(0);" title="Чат с консультантом">
            <span class="b-icon b-icon--footer">
                <?= new SvgDecorator('icon-chat-white', 16, 16) ?>
            </span>
            Чат с консультантом
        </a>
    </span>
</span>
