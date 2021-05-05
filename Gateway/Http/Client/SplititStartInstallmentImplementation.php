<?php

namespace Splitit\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Model\StartInstallmentsRequest;
use SplititSdkClient\Configuration;
use SplititSdkClient\Model\PlanData;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use Splitit\PaymentGateway\Gateway\Config\Config;
use SplititSdkClient\Model\UpdateInstallmentPlanRequest;
use Psr\Log\LoggerInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Splitit\PaymentGateway\Helper\TouchpointHelper;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;
use Magento\Framework\App\RequestInterface;

class SplititStartInstallmentImplementation implements ClientInterface
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

    public $request;

    /**
     * @var Config
     */
    protected $splititConfig;

    /**
     * @var LoggerInterface
    */
    protected $psrLogger;

    /**
     * @var TouchpointHelper
     */
    protected $touchPointHelper;

    /**
     * @param Logger $logger
     * @param LoginAuthentication $loginAuth
     * @param Config $splititConfig
     * @param LoggerInterface $psrLogger
     * @param TouchpointHelper $touchPointHelper
     */
    public function __construct(
        Logger $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        LoggerInterface $psrLogger,
        TouchpointHelper $touchPointHelper,
        LogResource $logResource,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->psrLogger = $psrLogger;
        $this->touchPointHelper = $touchPointHelper;
        $this->logResource = $logResource;
        $this->request = $request;
    }

    protected function isAsyncFlow()
    {
        return $this->request->getParam('InstallmentPlanNumber') && $this->request->getActionName('syccessasync');
    }

    /**
     * Places request to gateway. Returns result as ENV array
     * TODO: Inject InstallmentPlanApi, StartInstallmentsRequest
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
        if(( ! isset($data['TXN_ID']) || ! $data['TXN_ID']) && $this->isAsyncFlow()) {
            //no need to make extra API calls here if is async flow
            return [
                'RESULT_CODE' => self::SUCCESS,
                'TXN_ID' => $this->request->getParam('InstallmentPlanNumber')
            ];
        }
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

        $paymentAction = $this->splititConfig->getPaymentAction();
        if ($paymentAction == AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            $orderRefNumber = $data['OrderRefNumber'];

            $planData = new PlanData();
            $planData->setRefOrderNumber($orderRefNumber);
            $updateRequest = new UpdateInstallmentPlanRequest();
            $updateRequest->setInstallmentPlanNumber($data['TXN_ID']);
            $updateRequest->setPlanData($planData);

            try {
                $result = $apiInstance->installmentPlanUpdate($updateRequest);
                if(isset($data['TXN_ID']) && $data['TXN_ID']) {
                    $this->updateLog($orderRefNumber, $data['TXN_ID']);
                }
            } catch (\Exception $e) {
                throw new \Exception(__('Error in adding order reference number to the installment plan. Please try again.'));
            }
        }

        $startInstallmentsRequest = new StartInstallmentsRequest();
        if(isset($data['TXN_ID']) && $data['TXN_ID']) {
            $startInstallmentsRequest->setInstallmentPlanNumber($data['TXN_ID']);
        }

        try {
            $startInstallmentsResponse = $apiInstance->installmentPlanStartInstallments($startInstallmentsRequest);
        } catch (\Exception $e) {
            throw new \Exception(__('Unable to process payment. Please try again later.'));
        }

        $isSuccess = $startInstallmentsResponse->getResponseHeader()->getSucceeded();

        if (!empty($isSuccess)) {
            $resultCode = self::SUCCESS;
        } else {
            $resultCode = self::FAILURE;
        }
        $response = [
            'RESULT_CODE' => $resultCode,
            'TXN_ID' => $data['TXN_ID'] ?? ''
        ];

        $this->logger->debug(
            [
                'request' => $data,
                'response' => $response
            ]
        );

        return $response;
    }

    public function updateLog($incrementId, $ipn)
    {
        $log = $this->logResource->getByIPN($ipn);
        if ($log && $log->getId()) {
            $log->setIncrementId($incrementId);
            $log->setIsSuccess(true);
            try {
                $this->logResource->save($log);
            } catch (\Exception $e) {
                $this->logger->debug($e->getTrace());
            }
        } else {
            $this->logger->debug(['There is no log record for IPN ' . $ipn]);
        }
    }
}
