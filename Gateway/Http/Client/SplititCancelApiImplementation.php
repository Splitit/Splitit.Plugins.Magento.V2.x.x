<?php

namespace Splitit\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Model\StartInstallmentsRequest;
use SplititSdkClient\Configuration;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use SplititSdkClient\Model\CancelInstallmentPlanRequest;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Splitit\PaymentGateway\Helper\TouchpointHelper;

class SplititCancelApiImplementation implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @var Logger
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
     * @var TouchpointHelper
     */
    protected $touchPointHelper;

    /**
     * @param Logger $logger
     * @param LoginAuthentication $loginAuth
     * @param Config $splititConfig
     * @param TouchpointHelper $touchPointHelper
     */
    public function __construct(
        Logger $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        TouchpointHelper $touchPointHelper
    ) {
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->touchPointHelper = $touchPointHelper;
    }

    /**
     * Places request to gateway. Returns result as ENV array
     *
     * @param TransferInterface $transferObject
     * @return array
     * @throws \Exception
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();

        $touchPointData = $this->touchPointHelper->getTouchPointData();
        $session_id = $this->loginAuth->getLoginSession();
        $envSelected = $this->splititConfig->getEnvironment();
        if ($envSelected == "sandbox") {
            Configuration::sandbox()->setTouchPoint($touchPointData);
            $apiInstance = new InstallmentPlanApi(
                Configuration::sandbox(),
                $session_id
            );
        } else {
            Configuration::production()->setTouchPoint($touchPointData);
            $apiInstance = new InstallmentPlanApi(
                Configuration::production(),
                $session_id
            );
        }
        
        $cancelRequest = new CancelInstallmentPlanRequest();
        $cancelRequest->setInstallmentPlanNumber($data['TXN_ID']);
        $cancelRequest->setRefundUnderCancelation($data['RefundUnderCancelation']);

        try {
            $cancelResponse = $apiInstance->installmentPlanCancel($cancelRequest);
        } catch (\Exception $e) {
            throw new \Exception(__('Error in cancelling the installment plan.'));
        }

        $isSuccess = $cancelResponse->getResponseHeader()->getSucceeded();

        if ($isSuccess) {
            $resultCode = self::SUCCESS;
        } else {
            $resultCode = self::FAILURE;
        }
        $response = [
            'RESULT_CODE' => $resultCode,
            'TXN_ID' => $data['TXN_ID']
        ];

        $this->logger->debug(
            [
                'request' => $data,
                'response' => $response
            ]
        );

        return $response;
    }
}
