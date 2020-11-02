<?php

namespace Splitit\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Model\StartInstallmentsRequest;
use SplititSdkClient\Configuration;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use SplititSdkClient\Model\RefundPlanRequest;
use Splitit\PaymentGateway\Gateway\Config\Config;
use SplititSdkClient\Model\MoneyWithCurrencyCode;
use Splitit\PaymentGateway\Helper\TouchpointHelper;

class SplititRefundApiImplementation implements ClientInterface
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
     * TODO: Inject InstallmentPlanApi, RefundPlanRequest
     * @param TransferInterface $transferObject
     * @return array
     * @throws \Exception
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $data = $transferObject->getBody();
        if (isset($data['TXN_ID']) && strpos($data['TXN_ID'], '-refund') !== false) {
            $data['TXN_ID'] = str_replace('-refund', '', $data['TXN_ID']);
        }
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

        $refundRequest = new RefundPlanRequest();
        $refundRequest->setInstallmentPlanNumber($data['TXN_ID']);
        $refundRequest->setAmount(new MoneyWithCurrencyCode(["value" => $data['Amount'], "currency_code" => "USD"]));
        $refundRequest->setRefundStrategy("FutureInstallmentsFirst");

        try {
            $refundResponse = $apiInstance->installmentPlanRefund($refundRequest);
        } catch (\Exception $e) {
            throw new \Exception(__('Error in creating refund for installment plan.'));
        }

        $isSuccess = $refundResponse->getResponseHeader()->getSucceeded();

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
