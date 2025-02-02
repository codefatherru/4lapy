<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 */

/*if ($arResult['CAN_ACCESS'] !== 'Y') {
    ShowError('При обработке запроса произошла ошибка: отказано в доступе');
    return;
}*/

/*if ($arResult['IS_AVATAR_AUTHORIZED'] === 'Y') {
    echo '<br><p>Вы уже находитесь в режиме "аватар". <a href="'.$arParams['LOGOUT_URL'].'">Выйти из режима</a>.</p>';
    return;
}*/

if ($arResult['IS_AJAX_REQUEST'] !== 'Y') {
    echo '<div id="refreshingBlockContainer">';
}

// форма
include __DIR__ . '/inc.form.php';

if ($arResult['IS_AJAX_REQUEST'] !== 'Y') {
    echo '</div>';
}

if ($arResult['USE_AJAX'] === 'Y' && $arResult['IS_AJAX_REQUEST'] !== 'Y') {
    ?>
    <script data-name="front_office_fest_reg" type="text/javascript">
        var festRegComponent = new FourPawsFrontOfficeFestRegComponent(
            {
                siteId: '<?=\CUtil::JSEscape(SITE_ID)?>',
                siteTemplateId: '<?=\CUtil::JSEscape(SITE_TEMPLATE_ID)?>',
                componentPath: '<?=\CUtil::JSEscape($componentPath)?>',
                template: '<?=\CUtil::JSEscape($arResult['JS']['signedTemplate'])?>',
                parameters: '<?=\CUtil::JSEscape($arResult['JS']['signedParams'])?>',
                sessid: '<?=\CUtil::JSEscape(bitrix_sessid())?>',
                containerSelector: '#refreshingBlockContainer'
            }
        );

        festRegComponent.limitNumberLength();
        
        $(document).ready(
            function () {
                function isJson(str) {
                    try {
                        JSON.parse(str);
                    } catch (e) {
                        return false;
                    }
                    return true;
                }

                // поиск участника
                $(festRegComponent.containerSelector).on(
                    'click',
                    '#ajaxSubmitButton',
                    function (event) {
                        event.preventDefault();
    
                        var submitButton = $(this);
                        var submitForm = submitButton.closest('form');
                        submitButton.attr('disabled', true);
                        submitForm.find('.form-page__submit-wrap').addClass('loading');
    
                        var formData = submitForm.serializeArray();
                        var sendData = {};
                        $.each(
                            formData,
                            function (i, field) {
                                sendData[field.name] = field.value;
                            }
                        );
    
                        festRegComponent.sendRequest(
                            sendData,
                            {
                                callbackComplete: function (jqXHR, textStatus, component) {
                                    if (isJson(jqXHR.responseText)) {
                                        var json = JSON.parse(jqXHR.responseText);
                                        if (json.success === 'Y') {
                                            $('.js-update-result-message').remove();
                                            submitForm.find('.form-page__submit-wrap').before('<div class="form-page__message js-update-result-message"><span class="text-h4 text-icon">' + json.message + '</span></div>');
                                        } else {
                                            $('.js-update-result-message').remove();
                                            if (!json.message) {
                                                json.message = 'Произошла ошибка при попытке регистрации';
                                            }
                                            submitForm.find('.form-page__submit-wrap').before('<div class="form-page__message b-icon js-update-result-message"><i class="icon icon-warning"></i><span class="text-h4 text-icon">' + json.message + '</span></div>');
                                        }
                                        submitButton.removeAttr('disabled');
                                        submitForm.find('.form-page__submit-wrap').removeClass('loading');
                                    } else {
	                                    if (textStatus === 'success') {
	                                        $(component.containerSelector).html(jqXHR.responseText);
	                                        //submitButton.removeAttr('disabled');
	                                        //submitForm.find('.form-page__submit-wrap').removeClass('loading');
	                                    }
                                    }

                                    festRegComponent.limitNumberLength();
                                }
                            }
                        );
                    }
                );
            }
        );
    </script>
    <?php
}
