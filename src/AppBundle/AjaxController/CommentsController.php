<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\AppBundle\AjaxController;

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use CBitrixComponent;
use CCommentsComponent;
use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\App\Response\JsonResponse;
use FourPaws\App\Response\JsonSuccessResponse;
use FourPaws\AppBundle\Exception\CaptchaErrorException;
use FourPaws\AppBundle\Exception\EmptyUserDataComments;
use FourPaws\AppBundle\Exception\ErrorAddComment;
use FourPaws\AppBundle\Exception\UserNotFoundAddCommentException;
use FourPaws\AppBundle\Service\AjaxMess;
use FourPaws\FormBundle\Exception\FileCountException;
use FourPaws\FormBundle\Exception\FileSaveException;
use FourPaws\FormBundle\Exception\FileSizeException;
use FourPaws\FormBundle\Exception\FileTypeException;
use FourPaws\Helpers\Exception\WrongPhoneNumberException;
use FourPaws\UserBundle\Exception\WrongEmailException;
use FourPaws\UserBundle\Exception\WrongPasswordException;
use Psr\Cache\InvalidArgumentException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class CommentsController
 *
 * @package FourPaws\AppBundle\AjaxController
 * @Route("/comments")
 */
class CommentsController extends Controller
{
    /** @var AjaxMess */
    private $ajaxMess;

    public function __construct()
    {
        try {
            $container = App::getInstance()->getContainer();
            $this->ajaxMess = $container->get('ajax.mess');
        } catch (ApplicationCreateException|ServiceNotFoundException|ServiceCircularReferenceException $e) {
            $logger = LoggerFactory::create('system');
            $logger->critical('Ошибка загрузки сервисов - ' . $e->getMessage());
        }
    }

    /**
     * @return JsonResponse
     */
    public function addAction(): JsonResponse
    {
        CBitrixComponent::includeComponentClass('fourpaws:comments');

        /** перегрузка капчи на ошибках */
        $this->ajaxMess->setReloadRecaptcha(true);
        $json = $this->ajaxMess->getSystemError();
        try {
            $res = \CCommentsComponent::addComment();
            if ($res) {
                $json =
                    JsonSuccessResponse::create('Ваш комментарий успешно отправлен, он появится здесь после проверки');
            } else {
                $json = $this->ajaxMess->getAddError();
            }
        } catch (CaptchaErrorException $e) {
            $json = $this->ajaxMess->getFailCaptchaCheckError();
        } catch (WrongPhoneNumberException $e) {
            $json = $this->ajaxMess->getWrongPhoneNumberException();
        } catch (WrongPasswordException $e) {
            $json = $this->ajaxMess->getWrongPasswordError();
        } catch (UserNotFoundAddCommentException $e) {
            $json = $this->ajaxMess->getUsernameNotFoundException();
        } catch (WrongEmailException $e) {
            $json = $this->ajaxMess->getWrongEmailError();
        } catch (EmptyUserDataComments $e) {
            $json = $this->ajaxMess->getEmptyDataError();
        } catch (ErrorAddComment $e) {
            $json = $this->ajaxMess->getAddError();
        } catch (LoaderException|SystemException|ApplicationCreateException|ServiceCircularReferenceException|\LogicException|\RuntimeException|\Exception $e) {
            $logger = LoggerFactory::create('system');
            $logger->critical('Ошибка загрузки сервисов - ' . $e->getMessage());
        }

        return $json;
    }

    /**
     * @return JsonResponse
     */
    public function addByCatalogAction(): JsonResponse
    {
        CBitrixComponent::includeComponentClass('fourpaws:comments');

        $json = $this->ajaxMess->getSystemError();
        try {
            $res = \CCommentsComponent::addComment(true);
            if ($res) {
                $json =
                    JsonSuccessResponse::create('Ваш комментарий успешно отправлен, он появится здесь после проверки');
            } else {
                $json = $this->ajaxMess->getAddError();
            }
        } catch (CaptchaErrorException $e) {
            $json = $this->ajaxMess->getFailCaptchaCheckError();
        } catch (WrongPhoneNumberException $e) {
            $json = $this->ajaxMess->getWrongPhoneNumberException();
        } catch (WrongPasswordException $e) {
            $json = $this->ajaxMess->getWrongPasswordError();
        } catch (UserNotFoundAddCommentException $e) {
            $json = $this->ajaxMess->getUsernameNotFoundException();
        } catch (WrongEmailException $e) {
            $json = $this->ajaxMess->getWrongEmailError();
        } catch (EmptyUserDataComments $e) {
            $json = $this->ajaxMess->getEmptyDataError();
        } catch (ErrorAddComment $e) {
            $json = $this->ajaxMess->getAddError();
        } catch (FileCountException $e) {
            $json = $this->ajaxMess->getFileCountError(5);
        } catch (FileSizeException $e) {
            $json = $this->ajaxMess->getFileSizeError(5);
        } catch (FileTypeException $e) {
            $json = $this->ajaxMess->getFileTypeError(['jpeg', 'jpg', 'png']);
        } catch (FileSaveException $e) {
            $json = $this->ajaxMess->getFileSaveError();
        } catch (LoaderException|SystemException|ApplicationCreateException|ServiceCircularReferenceException|\LogicException|\RuntimeException|\Exception $e) {
            $logger = LoggerFactory::create('system');
            $logger->critical('Ошибка загрузки сервисов - ' . $e->getMessage());
        }

        return $json;
    }

    /**
     * @throws \LogicException
     * @return JsonResponse
     */
    public function nextAction(): JsonResponse
    {
        CBitrixComponent::includeComponentClass('fourpaws:comments');

        $json = $this->ajaxMess->getSystemError();
        try {
            $items = CCommentsComponent::getNextItems();

            return JsonSuccessResponse::createWithData('Элементы подгружены', ['items' => $items]);
        } catch (ArgumentException|InvalidArgumentException $e) {
            $logger = LoggerFactory::create('params');
            $logger->critical('Ошибка параметров - ' . $e->getMessage());
        } catch (LoaderException|SystemException|ApplicationCreateException|ServiceCircularReferenceException|\LogicException|\RuntimeException $e) {
            $logger = LoggerFactory::create('system');
            $logger->critical('Ошибка загрузки сервисов - ' . $e->getMessage());
        }

        return $json;
    }
}
