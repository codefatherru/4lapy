<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\Form;

use FourPaws\Form\Exception\FileSaveException;
use FourPaws\Form\Exception\FileSizeException;
use FourPaws\Form\Exception\FileTypeException;

/**
 * Class FormService
 *
 * @package FourPaws\Form
 */
class FormService
{
    /**
     * @param array $fields
     * @param array $requireFields
     *
     * @return bool
     */
    public function checkRequiredFields(array $fields, array $requireFields = []) : bool
    {
        foreach ($requireFields as $requiredField) {
            if (empty($fields[$requiredField])) {
                return false;
                break;
            }
        }
        
        return true;
    }
    
    /**
     * @param $email
     *
     * @return bool
     */
    public function validEmail(string $email) : bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * @param $data
     *
     * @return bool
     */
    public function addResult(array $data) : bool
    {
        if (isset($data['MAX_FILE_SIZE'])) {
            unset($data['MAX_FILE_SIZE']);
        }
        
        $webFormId = (int)$data['WEB_FORM_ID'];
        
        if (isset($data['g-recaptcha-response'])) {
            unset($data['g-recaptcha-response']);
        }
        global $USER;
        $userID = 0;
        if ($USER->IsAuthorized()) {
            $userID = (int)$USER->GetID();
        }
        unset($data['web_form_submit'], $data['WEB_FORM_ID']);
        
        $formResult = new \CFormResult();
        $res        = $formResult->Add($webFormId, $data, 'N', $userID > 0 ? $userID : false);
        
        return (int)$res > 0;
    }
    
    /**
     * @param $fileCode
     * @param $fileSizeMb
     * @param $valid_types
     *
     * @throws FileSaveException
     * @throws FileSizeException
     * @throws FileTypeException
     * @return array
     */
    public function saveFile(string $fileCode, int $fileSizeMb, array $valid_types) : array
    {
        if (!empty($_FILES[$fileCode])) {
            $max_file_size = $fileSizeMb * 1024 * 1024;
            
            $file = $_FILES[$fileCode];
            if (is_uploaded_file($file['tmp_name'])) {
                $filename = $file['tmp_name'];
                /** @noinspection PassingByReferenceCorrectnessInspection */
                $ext = end(explode('.', $file['name']));
                if (filesize($filename) > $max_file_size) {
                    throw new FileSizeException('Файл не должен быть больше ' . $fileSizeMb . 'Мб');
                }
                if (!\in_array($ext, $valid_types, true)) {
                    throw new FileTypeException(
                        'Разрешено загружать файлы только с расширениями ' . implode(' ,', $valid_types)
                    );
                }
                return $file;
            }
            
            throw new FileSaveException('Произошла ошибка при сохранении файла, попробуйте позже');
        }
        
        return [];
    }
    
    /**
     * @param $form
     */
    public function addForm(array $form): void
    {
        $questions = [];
        if (isset($form['QUESTIONS'])) {
            $questions = $form['QUESTIONS'];
            unset($form['QUESTIONS']);
        }
        $createEmail = 'N';
        if (isset($form['CREATE_EMAIL'])) {
            $createEmail = $form['CREATE_EMAIL'];
            unset($form['CREATE_EMAIL']);
        }
        $statuses = [];
        if (isset($form['STATUSES'])) {
            $statuses = $form['STATUSES'];
            unset($form['STATUSES']);
        }
        $formId = (int)\CForm::Set($form);
        
        if ($formId > 0) {
            if (!empty($statuses)) {
                $this->addStatuses($formId, $statuses);
            }
            if (!empty($questions)) {
                $this->addQuestions($formId, $questions);
            }
            if ($createEmail === 'Y') {
                $this->addMailTemplate($formId, $createEmail);
            }
        }
    }
    
    /**
     * @param int   $formId
     * @param array $statuses
     */
    public function addStatuses(int $formId, array $statuses): void
    {
        if ($formId > 0 && !empty($statuses)) {
            $obFormStatus = new \CFormStatus();
            foreach ($statuses as $status) {
                $status['FORM_ID'] = $formId;
                $obFormStatus->Set($status);
            }
        }
    }
    
    /**
     * @param int   $formId
     * @param array $questions
     */
    public function addQuestions(int $formId, array $questions): void
    {
        if ($formId > 0 && !empty($questions)) {
            $obFormField = new \CFormField();
            foreach ($questions as $question) {
                $answers = [];
                if (isset($question['ANSWERS'])) {
                    $answers = $question['ANSWERS'];
                    unset($question['ANSWERS']);
                }
                $question['FORM_ID'] = $formId;
                $questionId          = (int)$obFormField->Set($question);
                if ($questionId > 0 && !empty($answers)) {
                    $this->addAnswers($questionId, $answers);
                }
            }
        }
    }
    
    /**
     * @param array $answers
     * @param int   $questionId
     */
    public function addAnswers(int $questionId, array $answers): void
    {
        if ($questionId > 0 && !empty($answers)) {
            $obFormAnswer = new \CFormAnswer();
            foreach ($answers as $answer) {
                $answer['FIELD_ID'] = $questionId;
                $obFormAnswer->Set($answer);
            }
        }
    }
    
    /**
     * @param int    $formId
     * @param string $createEmail
     */
    public function addMailTemplate(int $formId, string $createEmail = 'N'): void
    {
        if ($createEmail === 'Y') {
            $arTemplates = \CForm::SetMailTemplate($formId, 'Y');
            \CForm::Set(['arMAIL_TEMPLATE' => $arTemplates], $formId);
        }
    }
    
    /**
     * @param $sid
     */
    public function deleteForm(string $sid): void
    {
        $by    = 'ID';
        $order = 'ASC';
        $res   = \CForm::GetList($by, $order, ['SID' => $sid]);
        while ($item = $res->Fetch()) {
            \CForm::Delete($item['ID']);
        }
    }
    
    /**
     * @param int   $formId
     * @param array $fields
     *
     * @return array
     */
    public function getRealNamesFields(int $formId, array $fields = []) : array
    {
        $params = [
            'formId' => $formId
        ];
        if(!empty($fields)){
            $params['filter'] = ['SID' => $fields];
        }
        $items         = $this->getQuestions($params);
        $originalNames = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                if (!empty($fields) && \in_array($item['SID'], $fields, true)) {
                    switch ($item['FIELD_TYPE']) {
                        case 'radio':
                        case 'dropdown':
                            $postfix = $item['SID'];
                            break;
                        case 'checkbox':
                        case 'multiselect':
                            $postfix = $item['SID'] . '[]';
                            break;
                        default:
                            $postfix = $item['ANSWER_ID'];
                    }
                    $originalNames[$item['SID']] = 'form_' . $item['FIELD_TYPE'] . '_' . $postfix;
                }
                elseif (empty($fields)){
                    switch ($item['FIELD_TYPE']) {
                        case 'radio':
                        case 'dropdown':
                            $postfix = $item['SID'];
                            break;
                        case 'checkbox':
                        case 'multiselect':
                            $postfix = $item['SID'] . '[]';
                            break;
                        default:
                            $postfix = $item['ANSWER_ID'];
                    }
                    $originalNames[$item['SID']] = 'form_' . $item['FIELD_TYPE'] . '_' . $postfix;
                }
            }
        }
        
        return $originalNames;
    }
    
    /**
     * @param array $params
     *
     * @return array
     */
    public function getQuestions(array $params) : array
    {
        if ((int)$params['formId'] === 0) {
            return [];
        }
        $formId = $params['formId'];
        $by     = 's_id';
        $order  = 'asc';
        if (!empty($params['order'])) {
            $by    = key($params['order']);
            $order = $params['order'][$by];
        }
        $filter = [];
        if (!empty($params['filter'])) {
            $filter = $params['filter'];
        }
        $type = 'ALL';
        if (!empty($params['type'])) {
            $filter = $params['type'];
        }
        $obFormField = new \CFormField();
        $res         = $obFormField->GetList($formId, $type, $by, $order, $filter);
        $items       = [];
        $obAnswer    = new \CFormAnswer();
        while ($item = $res->Fetch()) {
            $resAnswer = $obAnswer->GetList($item['ID'], $by, $order, ['ACTIVE' => 'Y']);
            while ($itemAnswer = $resAnswer->Fetch()) {
                foreach ($itemAnswer as $key => $val) {
                    if ($key === 'ID') {
                        $item['ANSWER_ID'] = $val;
                    }
                    if (!empty($val) && empty($item[$key])) {
                        $item[$key] = $val;
                    }
                }
            }
            $items[] = $item;
        }
        
        return $items;
    }
}
