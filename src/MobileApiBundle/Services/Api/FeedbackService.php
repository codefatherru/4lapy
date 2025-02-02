<?php

/**
 * @copyright Copyright (c) NotAgency
 */

namespace FourPaws\MobileApiBundle\Services\Api;

use DateTime;
use FourPaws\App\Application;
use FourPaws\Helpers\PhoneHelper;
use FourPaws\MobileApiBundle\Dto\Request\FeedbackRequest;
use FourPaws\MobileApiBundle\Dto\Request\ReportRequest;
use FourPaws\MobileApiBundle\Exception\RuntimeException;
use FourPaws\UserBundle\Entity\User;
use FourPaws\UserBundle\Service\UserService as AppUserService;

class FeedbackService
{
    /** @var AppUserService */
    private $appUserService;

    public function __construct(
        AppUserService $appUserService
    )
    {
        $this->appUserService = $appUserService;
    }

    public function sendFeedback(FeedbackRequest $feedbackRequest)
    {
        \CModule::IncludeModule("form");

        $normPhone = $feedbackRequest->getReview()->getPhone();

        try {
            $normPhone = PhoneHelper::formatPhone($normPhone, '+7 (%s%s%s) %s%s%s-%s%s%s%s');
        } catch (\Exception $e) {}

        $form = [];
        $formValues = [];

        switch ($feedbackRequest->getType()) {
            case 'email':
                //найдем id нужной нам формы обратного звонка, дабы не использовать волшебные числа
                $form = (new \CForm())->getBySID('feedback')->Fetch();
                // $formValues['form_dropdown_theme'] = 24; // тема = другое
                if (!(
                    $feedbackRequest->getReview()->getEmail()
                    || $normPhone
                )) {
                    throw new RuntimeException('Необходимо указать либо телефон, либо email');
                }
                break;
            case 'callback':
                //найдем id нужной нам формы обратного звонка, дабы не использовать волшебные числа
                $form = (new \CForm())->getBySID('callback')->Fetch();
                if (!$normPhone) {
                    throw new RuntimeException('Необходимо указать либо телефон');
                }
                break;
        }

        if (!$form['ID']) {
            throw new RuntimeException('Ошибка базы данных');
        }

        //получаем идентификаторы вопросов, опять же дабы не использовать волшебные числа
        $isFiltered = false;
        $res = (new \CFormField)->getList(
            $form['ID'],
            'N',
            $by='s_id',
            $order='asc',
            [],
            $isFiltered
        );
        $user = null;
        try {
            $user = $this->appUserService->getCurrentUser();
        } catch (\Exception $e) {
            // do nothing
        }
        while ($question = $res->Fetch()) {
            $isAnswersFiltered = false;
            $answerVariant = (new \CFormAnswer())->GetList(
                $question['ID'],
                $by='s_id',
                $order='asc',
                [],
                $isAnswersFiltered
            )->fetch();
            $fieldName = 'form_' . $answerVariant['FIELD_TYPE'] . '_' . $answerVariant['ID'];
            $value = null;
            switch ($question['SID']) {
                case 'name':
                    $value = $feedbackRequest->getReview()->getTitle() ?: 'Запрос из мобильного приложения пользователя ' . ($user ? $user->getId() : '');
                    break;
                case 'email':
                    $value = $feedbackRequest->getReview()->getEmail();
                    break;
                case 'phone':
                    $value = $normPhone;
                    break;
                case 'message':
                    $value = $feedbackRequest->getReview()->getSummary();
                    break;
            }
            if ($value) {
                $formValues[$fieldName] = $value;
            }
        }

        $formResult = new \CFormResult;
        if ($iResultId = $formResult->add($form['ID'], $formValues, 'N')) {
            //жесть конечно, но так отправляются письма при добавлении нового результата
            $formResult->setEvent($iResultId);
            $formResult->mail($iResultId);

            $phone = PhoneHelper::formatPhone($normPhone, PhoneHelper::FORMAT_URL);

            if ($phone && $feedbackRequest->getType() == 'callback') {
                $this->sendCallCenter($phone);
            }
        } else {
            /** $strError - глобальная переменная @see \CAllFormResult::add */
            throw new RuntimeException('Ошибка отправки сообщения. ' . $GLOBALS['strError']);
        }
    }

    /**
     * @param ReportRequest $reportRequest
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\SystemException
     */
    public function sendReport(ReportRequest $reportRequest)
    {
        $fields = [
            'EVENT_NAME' => 'MobileAppReportBug',
            'LID' => \Bitrix\Main\Application::getInstance()->getContext()->getSite(),
            'DUPLICATE' => 'N',
            'C_FIELDS' => [
                'TEXT_REPORT' => $reportRequest->getSummary(),
                'DEVICE_INFO' => ($reportRequest->getDeviceInfo() ?: 'информация отсутствует'),
            ],
        ];
        try {
            $user = $this->appUserService->getCurrentUser();
            $fields['C_FIELDS']['USER_EMAIL'] = $user->getEmail();
            $fields['C_FIELDS']['USER_PHONE'] = $user->getPersonalPhone();
            $fields['C_FIELDS']['USER_FIRST_NAME'] = $user->getName();
            $fields['C_FIELDS']['USER_LAST_NAME'] = $user->getLastName();
        } catch (\Exception $e) {
            // do nothing
        }

        \CModule::IncludeModule("form");

        $form = (new \CForm())->getBySID('feedback')->Fetch();

        $isFiltered = false;

        $res = (new \CFormField)->getList(
            $form['ID'],
            'N',
            $by='s_id',
            $order='asc',
            [],
            $isFiltered
        );

        $formValues = [];

        while ($question = $res->Fetch()) {
            $isAnswersFiltered = false;
            $answerVariant = (new \CFormAnswer())->GetList(
                $question['ID'],
                $by='s_id',
                $order='asc',
                [],
                $isAnswersFiltered
            )->fetch();
            $fieldName = 'form_' . $answerVariant['FIELD_TYPE'] . '_' . $answerVariant['ID'];
            $value = null;
            switch ($question['SID']) {
                case 'name':
                    $value = ($user ? $user->getId() : '');
                    break;
                case 'email':
                    $value = ($user ? $user->getEmail() : '');
                    break;
                case 'phone':
                    $value = ($user ? $user->getPersonalPhone() : '');
                    break;
                case 'message':
                    $value = $reportRequest->getSummary();
                    break;
            }
            if ($value) {
                $formValues[$fieldName] = $value;
            }
        }

        $formResult = new \CFormResult;
        if ($iResultId = $formResult->add($form['ID'], $formValues, 'N')) {
            $formResult->setEvent($iResultId);
            $formResult->mail($iResultId);
        } else  {
            throw new RuntimeException('Ошибка отправки сообщения. ' . $GLOBALS['strError']);
        }
    }

    private function sendCallCenter($phone)
    {
        if ($phone) {
            $callbackService = Application::getInstance()->getContainer()->get('callback.service');
            $callbackService->send(
                $phone,
                (new DateTime())->format('Y-m-d H:i:s')
            );
        }
    }
}
