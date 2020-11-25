<?php

namespace Splitit\PaymentGateway\Controller\Flexfields;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use Splitit\PaymentGateway\Model\Log as LogModel;
use Splitit\PaymentGateway\Model\LogFactory as LogModelFactory;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;
use SplititSdkClient\Configuration;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Model\PlanData;
use SplititSdkClient\Model\ConsumerData;
use SplititSdkClient\Model\AddressData;
use SplititSdkClient\Model\PaymentWizardData;
use SplititSdkClient\Model\MoneyWithCurrencyCode;
use SplititSdkClient\Model\InitiateInstallmentPlanRequest;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Splitit\PaymentGateway\Block\UpstreamMessaging;
use Splitit\PaymentGateway\Helper\TouchpointHelper;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Data
     */
    protected $jsonHelper;

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
     * @var UpstreamMessaging
     */
    protected $upstreamBlock;

    /**
     * @var TouchpointHelper
     */
    protected $touchPointHelper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var LogModelFactory
     */
    private $logModelFactory;

    /**
     * @var LogResource
     */
    private $logResource;

    public function __construct(
        Context $context,
        Data $jsonHelper,
        LoggerInterface $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        UpstreamMessaging $upstreamBlock,
        TouchpointHelper $touchPointHelper,
        Session $checkoutSession,
        LogModelFactory $logModelFactory,
        LogResource $logResource
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->upstreamBlock = $upstreamBlock;
        $this->touchPointHelper = $touchPointHelper;
        $this->checkoutSession = $checkoutSession;
        $this->logModelFactory = $logModelFactory;
        $this->logResource = $logResource;
        parent::__construct($context);
    }

    /**
     * TODO : Inject InitiateInstallmentPlanRequest, PlanData, PaymentWizardData, AddressData, CustomerData
     * TODO : Abstract MoneyWithCurrencyCode, InstallmentPlanApi . Return the object using abstracted model.
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $postData = $this->_request->getParams();

        $touchPointData = $this->touchPointHelper->getTouchPointData();

        try {
            $sessionId = $this->loginAuth->getLoginSession();
            //TODO: Inject InstallmentPlanApi.
            $envSelected = $this->splititConfig->getEnvironment();
            if ($envSelected == "sandbox") {
                Configuration::sandbox()->setTouchPoint($touchPointData);
                $installmentPlanApi = new InstallmentPlanApi(
                    Configuration::sandbox(),
                    $sessionId
                );
            } else {
                Configuration::production()->setTouchPoint($touchPointData);
                $installmentPlanApi = new InstallmentPlanApi(
                    Configuration::production(),
                    $sessionId
                );
            }

            $installmentArray = $this->splititConfig->getInstallmentRange();
            $installmentRange = "2,3,4,5,6,12"; //setting default value
            if (!empty($installmentArray)) {
                foreach ($installmentArray as $installmentArrayItem) {
                    if ($postData['amount'] >= $installmentArrayItem[0] && $postData['amount'] <= $installmentArrayItem[1]) {
                        $installmentNum = $installmentArrayItem[2];
                        $instArrray = [];
                        for ($i=2; $i<=$installmentNum; $i++)
                        {
                            $instArrray[] = $i;
                        }
                        $installmentRange = implode(',', $instArrray);
                    }
                }
            }

            $currencyCode = $this->upstreamBlock->getCurrentCurrencyCode();
            $cultureName = strtolower(str_replace('_', '-', $this->upstreamBlock->getCultureName()));
            if ($cultureName == null) {
                $cultureName = 'en-us';
            }

            $initiateReq = new InitiateInstallmentPlanRequest();

            $planData = new PlanData();

            $planData->setNumberOfInstallments($postData['numInstallments']);
            $planData->setAmount(new MoneyWithCurrencyCode(["value" => $postData['amount'], "currency_code" => $currencyCode]));
            $planData->setAutoCapture(false);
            $planData->setRefOrderNumber($this->checkoutSession->getQuoteId());
            $is3dSecureEnabled = $this->splititConfig->get3DSecure();
            if ($is3dSecureEnabled) {
                $planData->setAttempt3DSecure(true);
            } else {
                $planData->setAttempt3DSecure(false);
            }

            $paymentWizard = new PaymentWizardData();
            $successAsyncUrl = $this->_url->getUrl('splititpaymentgateway/payment/successasync');
            $paymentWizard->setSuccessAsyncUrl($successAsyncUrl);
            $paymentWizard->setRequestedNumberOfInstallments($installmentRange);
            $paymentWizard->setIsOpenedInIframe(true);

            $billingAddress = new AddressData(array(
                "address_line" => $postData['billingAddress']['AddressLine'],
                "address_line2" => $postData['billingAddress']['AddressLine2'],
                "city" => $postData['billingAddress']['City'],
                "state" => $postData['billingAddress']['State'],
                "country" => $postData['billingAddress']['Country'],
                "zip" => $postData['billingAddress']['Zip']
            ));

            $consumerData = new ConsumerData(array(
                "full_name" => $postData['consumerModel']['FullName'],
                "email" => $postData['consumerModel']['Email'],
                "phone_number" => $postData['consumerModel']['PhoneNumber'],
                "culture_name" => $cultureName,
                "is_locked" => false,
                "is_data_restricted" => false,
            ));

            $initiateReq->setPlanData($planData)
                ->setBillingAddress($billingAddress)
                ->setConsumerData($consumerData)
                ->setPaymentWizardData($paymentWizard);

            $initResp = $installmentPlanApi->installmentPlanInitiate($initiateReq);

            $success = $initResp->getResponseHeader()->getSucceeded();

            if ($success) {
                $fieldData = [
                    "installmentPlan"        => $this->jsonHelper->jsonDecode($initResp->getInstallmentPlan()),
                    "privacyPolicyUrl"       => $initResp->getPrivacyPolicyUrl(),
                    "termsAndConditionsUrl"  => $initResp->getTermsAndConditionsUrl(),
                    "approvalUrl"            => $initResp->getApprovalUrl(),
                    "publicToken"            => $initResp->getPublicToken(),
                    "checkoutUrl"            => $initResp->getCheckoutUrl(),
                    "installmentPlanInfoUrl" => $initResp->getInstallmentPlanInfoUrl(),
                ];

                $this->createOrUpdateLog($fieldData['installmentPlan']['InstallmentPlanNumber']);

                return $this->jsonResponse($fieldData);
            } else {
                $this->messageManager->addErrorMessage("Splitit Payment method not available currently. Please try again later.");
            }
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }

    private function createOrUpdateLog($ipn)
    {
        $quoteId = $this->checkoutSession->getQuoteId();
        /** @var LogModel $log */
        $log = $this->logResource->getByQuote($quoteId);
        if (!$log) {
            $log = $this->logModelFactory->create();
            $log->setQuoteId($this->checkoutSession->getQuoteId());
        }
        $log->setInstallmentPlanNumber($ipn);
        try {
            $this->logResource->save($log);
        } catch (\Exception $e) {
            // do nothing;
        }
    }
}
