<?php

/*
 * @copyright Copyright (c) ADV/web-engineering co
 */

namespace FourPaws\ReCaptcha;

use Adv\Bitrixtools\Tools\Log\LoggerFactory;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use FourPaws\App\Application as App;

class ReCaptchaService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * ключ
     */
    private $key;
    
    /**
     * секретный ключ
     */
    private $secretKey;
    
    const SERVICE_URI = 'https://www.google.com/recaptcha/api/siteverify';
    
    /**
     * @var ClientInterface
     */
    protected $guzzle;
    
    /** @noinspection SpellCheckingInspection */
    
    /**
     * @var LoggerInterface
     */
    protected $logger;
    
    /** @noinspection SpellCheckingInspection */
    
    /**
     * CallbackConsumerBase constructor.
     *
     * @param ClientInterface $guzzle
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \FourPaws\App\Exceptions\ApplicationCreateException
     * @throws \RuntimeException
     */
    public function __construct(ClientInterface $guzzle)
    {
        $this->guzzle = $guzzle;
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->logger = LoggerFactory::create('recaptcha');
    
        list($this->key, $this->secretKey) =
            array_values(App::getInstance()->getContainer()->getParameter('recaptcha'));
    }
    
    /**
     * @param string $additionalClass
     *
     * @return string
     */
    public function getCaptcha(string $additionalClass = '') : string
    {
        $this->addJs();
        
        return '<div class="g-recaptcha' . $additionalClass . '" data-sitekey="' . $this->key . '"></div>';
    }
    
    public function addJs()
    {
        Asset::getInstance()->addJs('https://www.google.com/recaptcha/api.js');
    }
    
    /**
     * @param string $recaptcha
     *
     * @throws \RuntimeException
     * @throws SystemException
     * @throws GuzzleException
     * @return bool
     */
    public function checkCaptcha(string $recaptcha = '') : bool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $context = Application::getInstance()->getContext();
        /** отменяем првоерку если запрос был без капчи */
        if (empty($recaptcha) && !$context->getRequest()->offsetExists('g-recaptcha-response')) {
            return true;
        }
        if (empty($recaptcha)) {
            $recaptcha = (string)$context->getRequest()->get('g-recaptcha-response');
        }
        $uri = new Uri(static::SERVICE_URI);
        $uri->addParams(
            [
                'secret'   => $this->secretKey,
                'response' => $recaptcha,
                'remoteip' => $context->getServer()->get('REMOTE_ADDR'),
            ]
        );
        if (!empty($recaptcha)) {
            $res = $this->guzzle->request('get', $uri->getUri());
            if ($res->getStatusCode() === 200) {
                $data = json_decode($res->getBody()->getContents());
                if ($data->success) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
