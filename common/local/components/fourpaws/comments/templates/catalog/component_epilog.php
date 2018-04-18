<?php

use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;
use FourPaws\App\Application as SymfoniApplication;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$request = Application::getInstance()->getContext()->getRequest();

$uri = new Uri($request->getRequestUri());
$fragment = $uri->getFragment();
if ($fragment === 'new-review') { ?>
    <script>
        $(function () {
            $('div.b-tab-title__list a[data-tab=reviews]').click();
            $('div.b-tab-content div[data-tab-content=reviews] button.js-add-review').click();
        });
    </script>
<?php }
$uniqueCommentString = $arParams['TYPE'] . '_' . $arParams['HL_ID'] . '_' . $arParams['OBJECT_ID'];
/** @var CCommentsComponent $component */
$arResult['AUTH'] = $component->userAuthService->isAuthorized();
if (!$arResult['AUTH']) {
    $recaptchaService = SymfoniApplication::getInstance()->getContainer()->get('recaptcha.service');?>
    <script type="text/javascript">
        if($('.js-comments-auth-block-<?=$uniqueCommentString?>').length > 0) {
            $('.js-comments-auth-block-<?=$uniqueCommentString?>').css('display', 'block');
        }
        if($('.js-comments-auth-form-<?=$uniqueCommentString?>').length > 0) {
            $('.js-comments-auth-form-<?=$uniqueCommentString?>').css('display', 'block');
        }
        if($('.js-comments-captcha-block-<?=$uniqueCommentString?>').length > 0) {
            $('.js-comments-captcha-block-<?=$uniqueCommentString?>').html('<?=$recaptchaService->getCaptcha();?>').css('display', 'block');
        }
    </script>
<?php } else { ?>
    <script type="text/javascript">
        if($('.js-comments-auth-form-<?=$uniqueCommentString?>').length > 0) {
            $('.js-comments-auth-form-<?=$uniqueCommentString?>').remove();
        }
    </script>
<?php }