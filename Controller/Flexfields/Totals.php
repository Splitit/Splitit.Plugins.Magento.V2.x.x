<?php

namespace Splitit\PaymentGateway\Controller\Flexfields;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\DataObject;
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

class Totals extends \Magento\Framework\App\Action\Action
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
        $request = $this->getRequest();
        $amount = $request->getParam('amount',$this->checkoutSession->getQuote()->getBaseGrandTotal());
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
            $installmentRange = [];
            if (!empty($installmentArray)) {
                foreach ($installmentArray as $installmentArrayItem) {
                    if ($amount >= $installmentArrayItem[0] && $amount <= $installmentArrayItem[1]) {
                        foreach ($installmentArrayItem[2] as $installmentNum) {
                            $installmentRange[] = $installmentNum;
                        }
                    }
                }
            }

            $installmentRange = implode(',', array_unique($installmentRange));

            $currencyCode = $this->upstreamBlock->getCurrentCurrencyCode();
            $cultureName = strtolower(str_replace('_', '-', $this->upstreamBlock->getCultureName()));
            if ($cultureName == null) {
                $cultureName = 'en-us';
            }

            $initiateReq = new InitiateInstallmentPlanRequest();

            $curInstallmentPlanNumber = $this->checkoutSession->getInstallmentPlanNumber() ?
                $this->checkoutSession->getInstallmentPlanNumber() :
                $request->getParam('installments_plan_number');

            $planData = new PlanData();
            $planData->setAmount(new MoneyWithCurrencyCode(["value" => $amount, "currency_code" => $currencyCode]));


            $paymentWizard = new PaymentWizardData();
            $successAsyncUrl = $this->_url->getUrl('splititpaymentgateway/payment/successasync');
            $paymentWizard->setSuccessAsyncUrl($successAsyncUrl);
            if( ! empty($installmentRange)) {
                $paymentWizard->setRequestedNumberOfInstallments($installmentRange);
            }

            $paymentWizard->setIsOpenedInIframe(true);

            $initiateReq->setPlanData($planData)
                ->setPaymentWizardData($paymentWizard);
            $initiateReq->setInstallmentPlanNumber($curInstallmentPlanNumber);

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
                $this->checkoutSession->setInstallmentPlanNumber($fieldData['installmentPlan']['InstallmentPlanNumber']);
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
            $log->setQuoteId($quoteId);
        }
        $log->setInstallmentPlanNumber($ipn);
        try {
            $this->logResource->save($log);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}
