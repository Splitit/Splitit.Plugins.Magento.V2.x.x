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

    protected function getCustomerName()
    {
        $sessionBillingAddress = $this->checkoutSession->getQuote()->getBillingAddress();
        $sessionShippingAddress = $this->checkoutSession->getQuote()->getBillingAddress();
        $name = $sessionBillingAddress->getName()
            ? $sessionBillingAddress->getName()
            : $sessionShippingAddress->getName();
        return $name;
    }

    protected function getTelephone()
    {
        $sessionBillingAddress = $this->checkoutSession->getQuote()->getBillingAddress();
        $sessionShippingAddress = $this->checkoutSession->getQuote()->getBillingAddress();
        $telephone = $sessionBillingAddress->getTelephone()
            ? $sessionBillingAddress->getTelephone()
            : $sessionShippingAddress->getTelephone();
        return $telephone;
    }

    protected function getBillingAddress()
    {
        $address = new DataObject((array)$this->getRequest()->getParam('billingAddress'));

        $sessionBillingAddress = $this->checkoutSession->getQuote()->getBillingAddress();

        $street1 = $sessionBillingAddress->getStreetLine(1);

        $street2 = $sessionBillingAddress->getStreetLine(2);

        $city = $sessionBillingAddress->getCity();

        $region = $sessionBillingAddress->getRegion();

        $country = $sessionBillingAddress->getCountry();

        $postcode = $sessionBillingAddress->getPostcode();

        $billingAddress = new AddressData([
            "address_line" => $address->getData('AddressLine') ? $address->getData('AddressLine') : $street1,
            "address_line2" => $address->getData('AddressLine2') ? $address->getData('AddressLine2') : $street2,
            "city" => $address->getData('City') ? $address->getData('City') : $city,
            "state" => $address->getData('State') ? $address->getData('State') : $region,
            "country" => $address->getData('Country') ? $address->getData('Country') : $country,
            "zip" => $address->getData('Zip') ? $address->getData('Zip') : $postcode
        ]);

        return $billingAddress;
    }

    /**
     * @param $obj ConsumerData
     */
    protected function isConsumerDataValid($obj)
    {
        return $obj->getEmail() && $obj->getFullName() && $obj->getPhoneNumber();

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

            $planData = new PlanData();

            $planData->setNumberOfInstallments($request->getParam('numInstallments'));
            $planData->setAmount(new MoneyWithCurrencyCode(["value" => $amount, "currency_code" => $currencyCode]));
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
            if( ! empty($installmentRange)) {
                $paymentWizard->setRequestedNumberOfInstallments($installmentRange);
            }

            $paymentWizard->setIsOpenedInIframe(true);


            $billingAddress = $this->getBillingAddress();


            $consumer = $request->getParam('consumerModel');
            $consumerData = new ConsumerData(array(
                "full_name" => isset($consumer['FullName']) ? $consumer['FullName'] : $this->getCustomerName(),
                "email" => isset($consumer['Email']) ? $consumer['Email'] : $this->checkoutSession->getQuote()->getCustomerEmail(),
                "phone_number" => isset($consumer['PhoneNumber']) ? $consumer['PhoneNumber'] : $this->getTelephone(),
                "culture_name" => $cultureName,
                "is_locked" => false,
                "is_data_restricted" => false,
            ));

            $initiateReq->setPlanData($planData)
                ->setPaymentWizardData($paymentWizard);
            if($this->isConsumerDataValid($consumerData)) {
                $initiateReq
                    ->setConsumerData($consumerData)
                    ->setBillingAddress($billingAddress);
            }

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
