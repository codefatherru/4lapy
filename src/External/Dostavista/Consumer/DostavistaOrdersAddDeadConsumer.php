<?php

namespace FourPaws\External\Dostavista\Consumer;

use PhpAmqpLib\Message\AMQPMessage;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
use FourPaws\External\Dostavista\Exception\DostavistaOrdersAddConsumerException;
use FourPaws\App\Application as App;

/**
 * Class DostavistaOrdersAddDeadConsumer
 *
 * @package FourPaws\External\Dostavista\Consumer
 */
class DostavistaOrdersAddDeadConsumer extends DostavistaConsumerBase
{
    /**
     * @param AMQPMessage $message
     * @return bool
     */
    public function execute(AMQPMessage $message): bool
    {
        $res = static::MSG_REJECT;
        Application::getConnection()->queryExecute("SELECT CURRENT_TIMESTAMP");
        $body = $message->getBody();
        $data = json_decode($message->getBody(), true);
        $lastDateTryToSend = new DateTime($data['last_date_try_to_send'], static::DATE_TIME_FORMAT);
        try {
            if ($lastDateTryToSend->add('+1 minutes') >= new DateTime()) {
                $res = static::MSG_REJECT_REQUEUE;
            } else {
                /** @var DateTime $orderCreateDate */
                $orderCreateDate = new DateTime($data['order_create_date'], static::DATE_TIME_FORMAT);
                if (!$orderCreateDate instanceof DateTime) {
                    throw new DostavistaOrdersAddConsumerException(
                        self::ERRORS['order_date_create_not_found']['message'],
                        self::ERRORS['order_date_create_not_found']['code']
                    );
                }
                if ($orderCreateDate->add('+20 minutes') < new DateTime()) {
                    //время отправки вышло
                    $bitrixOrderId = $data['bitrix_order_id'];
                    if ($bitrixOrderId === null) {
                        throw new DostavistaOrdersAddConsumerException(
                            self::ERRORS['order_id_empty']['message'],
                            self::ERRORS['order_id_empty']['code']
                        );
                    }
                    $order = $this->orderService->getOrderById($bitrixOrderId);
                    /** Обновляем битриксовые свойства достависты */
                    $this->updateCommWayProperty($order, false);
                    if ($order) {
                        $this->dostavistaService->dostavistaOrderAddErrorSendEmail($order->getId(), $order->getField('ACCOUNT_NUMBER'), '', '', (new \Datetime)->format(static::DATE_TIME_FORMAT));
                    } else {
                        $this->dostavistaService->dostavistaOrderAddErrorSendEmail(0, 0, '', '', (new \Datetime)->format(static::DATE_TIME_FORMAT));
                    }
                    throw new DostavistaOrdersAddConsumerException(
                        self::ERRORS['time_to_send_has_expired']['message'],
                        self::ERRORS['time_to_send_has_expired']['code']
                    );
                }
                //пушим обратно на обработку
                /** @noinspection MissingService */
                $this->log()->error('Dostavista: return order ' . $data['bitrix_order_id'] . ' to main queue');
                $producer = App::getInstance()->getContainer()->get('old_sound_rabbit_mq.dostavista_orders_add_producer');
                $producer->publish($body);
                $res = static::MSG_ACK;
            }
        } catch (DostavistaOrdersAddConsumerException|\Exception $e) {
            $this->log()->error('Dostavista error, code: ' . $e->getCode() . ' message: ' . $e->getMessage(), is_array($data) ? $data : []);
        }
        return $res;
    }
}
