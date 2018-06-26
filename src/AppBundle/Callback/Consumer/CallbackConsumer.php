<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\AppBundle\Callback\Consumer;

use FourPaws\App\Application as App;
use FourPaws\App\Exceptions\ApplicationCreateException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Class CallbackConsumer
 *
 * @package FourPaws\Callback\Consumer
 */
class CallbackConsumer extends CallbackConsumerBase
{
    const SUCCESS = 4;
    
    /**
     * @param AMQPMessage $msg The message
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws ApplicationCreateException
     * @throws GuzzleException
     * @throws \RuntimeException
     * @throws \LogicException
     * @return bool
     */
    public function execute(AMQPMessage $msg) : bool
    {
        $href = $msg->getBody();
        $res  = $this->guzzle->send(new Request('get', $href));
        $data = json_decode($res->getBody()->getContents());
        
        if ((int)$data->result !== static::SUCCESS || $res->getStatusCode() !== 200) {
            $this->log()->critical('Сервис обартного звонка ответил ошибкой на ссылку '.$href, (array)$data);
            $callBackProducer = App::getInstance()->getContainer()->get('old_sound_rabbit_mq.callback_set_producer');
            /** @var Producer $callBackProducer */
            $callBackProducer->publish($href);
        }
        
        return true;
    }
}
