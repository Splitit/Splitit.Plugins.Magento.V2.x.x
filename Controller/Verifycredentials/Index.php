<?php

namespace Splitit\PaymentGateway\Controller\Verifycredentials;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Framework\App\Action\Action 
{
    /**
     * @var LoggerInterface
    */
    protected $logger;

    /**
     * @var LoginAuthentication
    */
    protected $loginAuth;

    /**
     * @var Config
    */
    protected $splititConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Splitit\PaymentGateway\Gateway\Login\LoginAuthentication $loginAuth
     * @param \Splitit\PaymentGateway\Gateway\Config\Config $splititConfig
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig
    ) {
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        parent::__construct($context);
    }

    /**
     *
     * @return \Magento\Framework\Controller\ResultInterface
    */
    public function execute() 
    {
        $response = [
            "status" => false,
            "errorMessage" => "",
            "successMessage" => "",
        ];
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $username = $this->splititConfig->getApiUsername();
        $password = $this->splititConfig->getApiPassword();
        $apiKey = $this->splititConfig->getApiMerchantId();
        $envSelected = $this->splititConfig->getEnvironment();

        if (!empty($username) && !empty($password) && !empty($apiKey) && !empty($envSelected)) {
            $sessionId = $this->loginAuth->verifyCredentials($username, $password, $apiKey, $envSelected);
            if (!empty($sessionId)) {   
                $response["successMessage"] = "Credentials are valid!";
                $response["status"] = true;
            } else {
                $response['errorMessage'] = "Invalid Credentials. Please enter valid credentials and try again!"; 
            }
        } else {
            $response['errorMessage'] = "Please enter the credentials (api key, username, password). Save configuration and try again!";
        }

        $resultJson->setData($response);

        return $resultJson;
    }
}
