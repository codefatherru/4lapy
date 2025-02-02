<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\ReCaptchaBundle\Service\ReCaptchaInterface;

/** @var string $phone
 * @var string $backUrl
 */ ?>
<div class="b-registration__content b-registration__content--create-password">
    <div class="b-registration__text-instruction b-registration__text-instruction--create-password"
         id="js-resend"
         data-url="/ajax/user/auth/forgotPassword/"
         data-phone="<?= $phone ?>"
         data-action="resendSms">Введите код
                                 подтверждения из
                                 SMS, который мы
                                 выслали вам на
                                 номер <?= $phone ?>
    </div>
    <form class="b-registration__form js-form-validation js-password-recovery-code js-recovery-form js-resend"
          data-url="/ajax/user/auth/forgotPassword/"
          method="post">
        <input type="hidden" name="action" value="get">
        <input type="hidden" name="backurl" value="<?= $backUrl?>">
        <input type="hidden" name="step" value="createNewPassword">
        <input type="hidden" name="phone" value="<?= $phone ?>">
        <input type="hidden" name="cpToken" value="<?= $hash ?>">
        <div class="b-input-line b-input-line--create-password b-input-line--recovery">
            <div class="b-input-line__label-wrapper">
                <label class="b-input-line__label" for="registration-tel-recovery-code">SMS-код</label>
            </div>
            <div class="b-input b-input--registration-form b-kek">
                <input class="b-input__input-field b-input__input-field--registration-form b-kek"
                       type="text"
                       id="registration-tel-recovery-code"
                       placeholder=""
                       name="confirmCode" />
                <div class="b-error"><span class="js-message"></span>
                </div>
            </div>
            <a class="b-link-gray js-resend-sms"
               href="javascript:void(0);"
               data-url="/ajax/user/auth/forgotPassword/"
               data-phone="<?= $phone ?>"
               data-action="resendSms"
               data-recovery-resend-a="true"
               title="Отправить снова">Отправить снова</a>
        </div>

        <div style="display: none;" data-recovery-resend-captcha="true">
            <?
            /** @var \FourPaws\ReCaptchaBundle\Service\ReCaptchaService $recaptchaService */
            $recaptchaService = App::getInstance()->getContainer()->get(ReCaptchaInterface::class);
            echo $recaptchaService->getCaptcha('b-input-line', true, 'recoveryResendSms');
            ?>
        </div>

        <br>
        <button class="b-button b-button--social b-button--full-width" data-recovery-form-send="true">Далее</button>
    </form>
</div>