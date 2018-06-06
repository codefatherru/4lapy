<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\External\Manzana\Consumer;

use FourPaws\App\Exceptions\ApplicationCreateException;
use FourPaws\External\Exception\ManzanaServiceContactSearchMoreOneException;
use FourPaws\External\Exception\ManzanaServiceContactSearchNullException;
use FourPaws\External\Exception\ManzanaServiceException;
use FourPaws\External\Manzana\Exception\ContactUpdateException;
use FourPaws\External\Manzana\Model\Client;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class ManzanaContactConsumer
 *
 * @package FourPaws\External\Manzana\Consumer
 */
class ManzanaContactConsumer extends ManzanaConsumerBase
{
    /**
     * @inheritdoc
     */
    public function execute(AMQPMessage $message): bool
    {
        try {
            /** @var Client $contact */
            $contact = $this->serializer->deserialize($message->getBody(), Client::class, 'json');

            if (null === $contact || (empty($contact->phone) && empty($contact->contactId))) {
                throw new ContactUpdateException('Неожиданное сообщение');
            }

            if (empty($contact->contactId)) {
                try {
                    if(!empty($contact->phone)) {
                        $contact->contactId = $this->manzanaService->getContactIdByPhone($contact->phone);
                    } else {
                        throw new ContactUpdateException('Неожиданное сообщение');
                    }
                    /** иначе создание пользователя */
                } catch (ManzanaServiceContactSearchNullException $e) {
                    /**
                     * Создание пользователя
                     */
                }
            }

            $contact = $this->manzanaService->updateContact($contact);
            /** скипаем если нет телефона - ибо не найден пользователя для привзяки,
             * так же скипаем если нет маназановского id
             */
            if(!empty($contact->phone) && !empty($contact->contactId)) {
                $this->manzanaService->updateUserCardByClient($contact);
            }
        } catch (ContactUpdateException $e) {
            $this->log()->error(sprintf(
                'Contact update error: %s',
                $e->getMessage()
            ));
        } catch (ManzanaServiceContactSearchMoreOneException $e) {
            $this->log()->error(sprintf(
                'Too many user`s found: %s',
                $e->getMessage()
            ));
            /** не перезапускаем очередь */
        } catch (ManzanaServiceException $e) {
            $this->log()->error(sprintf(
                'Manzana contact consumer error: %s, message: %s',
                $e->getMessage(),
                $message->getBody()
            ));

            sleep(30);
            try {
                $this->manzanaService->updateContactAsync($contact);
            } catch (ApplicationCreateException|ServiceNotFoundException|ServiceCircularReferenceException $e) {
                $this->log()->error(sprintf(
                    'Manzana contact consumer /service/ error: %s, message: %s',
                    $e->getMessage(),
                    $message->getBody()
                ));
            }
        }

        return true;
    }
}
