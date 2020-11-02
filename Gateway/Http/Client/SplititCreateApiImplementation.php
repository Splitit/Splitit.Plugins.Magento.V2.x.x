<?php

namespace Splitit\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Splitit\PaymentGateway\Model\LogFactory as LogModelFactory;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Configuration;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use SplititSdkClient\Model\PlanData;
use Splitit\PaymentGateway\Gateway\Config\Config;
use SplititSdkClient\Model\UpdateInstallmentPlanRequest;
use Psr\Log\LoggerInterface;
use Splitit\PaymentGateway\Helper\TouchpointHelper;

class SplititCreateApiImplementation implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @var array
     */
    protected $results = [
        self::SUCCESS,
        self::FAILURE
    ];

    /**
     * @var Logger
     */
    protected $logger;

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
     * @var LogModelFactory
     */
    private $logModelFactory;

    /**
     * @var LogResource
     */
    private $logResource;

    /**
     * @param Logger $logger
     * @param LoginAuthentication $loginAuth
     * @param Config $splititConfig
     * @param LoggerInterface $psrLogger
     * @param TouchpointHelper $touchPointHelper
     * @param LogModelFactory $logModelFactory
     * @param LogResource $logResource
     */
    public function __construct(
        Logger $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        LoggerInterface $psrLogger,
        TouchpointHelper $touchPointHelper,
        LogModelFactory $logModelFactory,
        LogResource $logResource
    ) {
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->psrLogger = $psrLogger;
        $this->touchPointHelper = $touchPointHelper;
        $this->logModelFactory = $logModelFactory;
        $this->logResource = $logResource;
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
        $isSuccess = $data['Succeeded'];

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

        $orderRefNumber = $data['OrderRefNumber'];

        $planData = new PlanData();
        $planData->setRefOrderNumber($orderRefNumber);

        $updateRequest = new UpdateInstallmentPlanRequest();
        $updateRequest->setInstallmentPlanNumber($data['InstallmentPlanNumber']);
        $updateRequest->setPlanData($planData);

        try {
            $result = $apiInstance->installmentPlanUpdate($updateRequest);
            $this->updateLog($orderRefNumber, $data['InstallmentPlanNumber']);
        } catch (Exception $e) {
            throw new \Exception(__('Error in adding order reference number to the installment plan. Please try again.'));
            $this->psrLogger($e);
        }

        if ($isSuccess) {
            $resultCode = self::SUCCESS;
        } else {
            $resultCode = self::FAILURE;
        }
        $response = [
            'RESULT_CODE' => $resultCode,
            'TXN_ID' => $data['InstallmentPlanNumber']
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
        if ($log->getId()) {
            $log->setIncrementId($incrementId);
            $log->setIsSuccess(true);
            try {
                $this->logResource->save($log);
            } catch (\Exception $e) {
                // do nothing;
            }
        } else {
            $this->logger->debug(['There is no log record for IPN ' . $ipn]);
        }
    }
}
