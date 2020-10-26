<?php

namespace Splitit\PaymentGateway\Gateway\Login;

use SplititSdkClient\Configuration;
use SplititSdkClient\ObjectSerializer;
use SplititSdkClient\Api\LoginApi;
use SplititSdkClient\Model\LoginRequest;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

/**
 * TODO: REFACTOR - Create an interface definition for this class
 * Class LoginAuthentication
*/
class LoginAuthentication 
{
    /**
     * @var LoginApi
    */
    protected $loginApi;

    /**
     * @var Configuration
    */
    protected $envConfiguration;

    /**
     * @var LoginRequest
    */
    protected $loginRequest;

    /**
     * @var Config
    */
    protected $splititConfig;

    /**
     * @var LoggerInterface
    */
    protected $logger;

    /**
     * @param \SplititSdkClient\Api\LoginApi $loginApi
     * @param \SplititSdkClient\Configuration $envConfiguration
     * @param \SplititSdkClient\Model\LoginRequest $loginRequest
     * @param \Splitit\PaymentGateway\Gateway\Config\Config $splititConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        LoginApi $loginApi,
        Configuration $envConfiguration,
        LoginRequest $loginRequest,
        Config $splititConfig,
        LoggerInterface $logger
    ) {
        $this->loginApi = $loginApi;
        $this->envConfiguration = $envConfiguration;
        $this->loginRequest = $loginRequest;
        $this->splititConfig = $splititConfig;
        $this->logger = $logger;
    }

    /**
     * Gets session id for splitit login session.
     * @return string
    */
    public function getLoginSession()
    {
        $username = $this->splititConfig->getApiUsername();
        $password = $this->splititConfig->getApiPassword();
        $apiKey = $this->splititConfig->getApiMerchantId();
        $envSelected = $this->splititConfig->getEnvironment();

        return $this->verifyCredentials($username, $password, $apiKey, $envSelected);
    }

    /**
     * Verifies login credentials
     * returns sessionid for valid login; logs error/returns null for invalid login.
     * @param string $username
     * @param string $password
     * @param string $apiKey
     * @param string $envSelected
     * @return string
    */
    public function verifyCredentials($username, $password, $apiKey, $envSelected)
    {
        if ($envSelected == 'production') {
            $this->envConfiguration->production()->setApiKey($apiKey);
            $loginApiObj = new LoginApi(Configuration::production());
        } else {
            $this->envConfiguration->sandbox()->setApiKey($apiKey);
            $loginApiObj = new LoginApi(Configuration::sandbox());
        }

        $request = $this->loginRequest;
        $request->setUserName($username);
        $request->setPassword($password);

        try {
            $loginResponse = $loginApiObj->loginPost($request);
        
            if (!empty($loginResponse)) {
                $session_id = $loginResponse->getSessionId();
                if(!empty($session_id)) {
                    return $session_id;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }

        return null;
    }
}
