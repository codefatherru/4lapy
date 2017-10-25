<?php

namespace FourPaws\External\Manzana\Client;

use FourPaws\App\Application;
use FourPaws\External\Manzana\Exception\AuthenticationException;
use FourPaws\External\Manzana\Exception\ExecuteException;
use FourPaws\Health\HealthService;
use FourPaws\Helpers\Exception\HealthException;
use Meng\AsyncSoap\SoapClientInterface;

/**
 * Class SoapClient
 *
 * @package FourPaws\External\Manzana\Client
 */
class SoapClient
{
    const METHOD_AUTHENTICATE = 'Authenticate';
    
    const METHOD_EXECUTE      = 'Execute';
    
    protected $client;
    
    protected $healthService;
    
    protected $mlLogin;
    
    protected $mlPassword;
    
    protected $mlIp;
    
    public function __construct(SoapClientInterface $client, HealthService $healthService)
    {
        $container = Application::getInstance()->getContainer();
        $parameters = $container->getParameter('manzana');
        
        $this->client        = $client;
        $this->healthService = $healthService;
        
        $this->mlLogin    = $parameters['login'];
        $this->mlPassword = $parameters['password'];
    }
    
    /**
     * @param string $login
     *
     * @return string
     *
     * @throws \FourPaws\External\Manzana\Exception\AuthenticationException
     */
    protected function authenticate(string $login = '') : string
    {
        $arguments = [
            'login'    => $this->mlLogin,
            'password' => $this->mlPassword,
            'ip'       => $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR'],
        ];
        
        if ($login) {
            $arguments['innerLogin'] = $login;
        }
        
        try {
            $sessionId = $this->client->call(self::METHOD_AUTHENTICATE, $arguments)->AuthenticateResult->SessionId;
            
            try {
                $this->healthService->setStatus($this->healthService::SERVICE_MANZANA,
                                                $this->healthService::STATUS_AVAILABLE);
            } catch (HealthException $e) {
            }
        } catch (\Exception $e) {
            try {
                $this->healthService->setStatus($this->healthService::SERVICE_MANZANA,
                                                $this->healthService::STATUS_UNAVAILABLE);
            } catch (HealthException $e) {
            }
            
            throw new AuthenticationException(sprintf('Auth error: %s', $e->getMessage()), $e->getCode(), $e);
        }
        
        return $sessionId;
    }
    
    /**
     * @param string $contract
     * @param array  $parameters
     * @param string $login
     *
     * @return \SimpleXMLElement
     *
     * @throws \FourPaws\External\Manzana\Exception\AuthenticationException
     * @throws \FourPaws\External\Manzana\Exception\ExecuteException
     */
    public function execute(string $contract, array $parameters = [], string $login = '') : \SimpleXMLElement
    {
        $sessionId = $this->authenticate($login);
        
        try {
            $arguments = [
                'sessionId'    => $sessionId,
                'contractName' => $contract,
                'parameters'   => $parameters,
            ];
            
            $result =
                simplexml_load_string($this->client->call(self::METHOD_EXECUTE, $arguments)->ExecuteResult->Value);
        } catch (\Exception $e) {
            throw new ExecuteException(sprintf('Execute error: %s', $e->getMessage()), $e->getCode(), $e);
        }
        
        return $result;
    }
}
