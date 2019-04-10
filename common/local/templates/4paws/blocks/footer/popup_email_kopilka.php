<section class="b-popup-email-coupon js-popup-section" data-popup="send-email-coupon-kopilka">
    <a class="b-popup-email-coupon__close js-close-popup"
       href="javascript:void(0);"
       title="Закрыть"></a>
    <div class="b-popup-email-coupon__content">
        <header class="b-popup-email-coupon__header">
            <div class="b-title b-title--h1 b-title--email-coupon-popup">На эту почту мы отправим вам купон на&nbsp;скидку</div>
        </header>
        <form class="b-popup-email-coupon__form js-form-validation js-email-kopilka" data-url="/ajax/piggy-bank/email/send/" method="post">
            <? $token = \FourPaws\Helpers\ProtectorHelper::generateToken(\FourPaws\Helpers\ProtectorHelper::TYPE_PIGGY_BANK_EMAIL_SEND); ?>
            <input type="hidden" name="<?=$token['field']?>" value="<?=$token['token']?>">
            <div class="b-input-line">
                <div class="b-input-line__label-wrapper">
                    <label class="b-input-line__label" for="send-email-coupon-kopilka">Email</label>
                </div>
                <div class="b-input b-input--coupon-form">
                    <input class="b-input__input-field b-input__input-field--coupon-form" type="emailMask" id="send-email-coupon-kopilka" name="email" value="<?=$USER->GetEmail()?:''?>" placeholder="">
                    <div class="b-error"><span class="js-message"></span>
                    </div>
                </div>
            </div>
            <button class="b-button b-button--email-coupon">Отправить</button>
        </form>
    </div>
</section>