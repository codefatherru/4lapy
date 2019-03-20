<?php

namespace FourPaws\External\Dostavista\Consumer;

use Adv\Bitrixtools\Tools\BitrixUtils;
use FourPaws\UserBundle\EventController\Event;
use PhpAmqpLib\Message\AMQPMessage;
use Bitrix\Sale\Order;

/**
 * Class DostavistaOrdersAddConsumer
 *
 * @package FourPaws\External\Dostavista\Consumer
 */
class DostavistaOrdersAddConsumer extends DostavistaConsumerBase
{

    /**
     * @param AMQPMessage $message
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(AMQPMessage $message): bool
    {
        Event::disableEvents();

        $result = static::MSG_ACK;

        try {
            $data = json_decode($message->getBody(), true);
            $bitrixOrderId = $data['bitrix_order_id'];
            unset($data['bitrix_order_id']);
            /**
             * @var Order $order
             * Получаем битриксовый заказ
             */
            if ($bitrixOrderId === null) {
                //айди битриксового заказа нет в данных от рэббита
                $result = static::MSG_REJECT;
            } else {
                $order = $this->orderService->getOrderById($bitrixOrderId);
                if (!$order) {
                    //битриксовый заказ не найден
                    $result = static::MSG_REJECT;
                } else {
                    /** Отправляем заказ в достависту */
                    $response = $this->dostavistaService->addOrder($data);
                    if ($response['connection'] === false) {
                        //Достависта недоступна
                        $result = static::MSG_REJECT;
                    } else {
                        $dostavistaOrderId = $response['order_id'];
                        if ((is_array($dostavistaOrderId) || empty($dostavistaOrderId)) || !$response['success']) {
                            //ошибка отправки сообщения в достависту
                            $dostavistaOrderId = 0;
                            $result = static::MSG_REJECT;
                        }
                    }
                    /** Обновляем битриксовые свойства достависты */
                    $deliveryId = $order->getField('DELIVERY_ID');
                    $deliveryCode = $this->deliveryService->getDeliveryCodeById($deliveryId);
                    $address = $this->orderService->compileOrderAddress($order)->setValid(true);
                    $this->orderService->setOrderPropertiesByCode(
                        $order,
                        [
                            'IS_EXPORTED_TO_DOSTAVISTA' => ($dostavistaOrderId) ? BitrixUtils::BX_BOOL_TRUE : BitrixUtils::BX_BOOL_FALSE,
                            'ORDER_ID_DOSTAVISTA' => ($dostavistaOrderId) ? $dostavistaOrderId : 0
                        ]
                    );
                    $this->orderService->updateCommWayPropertyEx($order, $deliveryCode, $address, ($dostavistaOrderId) ? true : false);
                    $order->save();
                }
            }
        } catch (\Exception $e) {
            $result = static::MSG_REJECT;
        }

        if ($result === static::MSG_REJECT) {
            if ($order) {
                $this->dostavistaService->dostavistaOrderAddErrorSendEmail($order->getId(), $order->getField('ACCOUNT_NUMBER'), $response['message'], $response['data'], (new \Datetime)->format('d.m.Y H:i:s'));
            }
        }

        Event::enableEvents();

        return $result;
    }
}
