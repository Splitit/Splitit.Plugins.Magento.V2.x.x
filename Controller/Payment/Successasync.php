<?php

namespace Splitit\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use Splitit\PaymentGateway\Helper\TouchpointHelper;
use Splitit\PaymentGateway\Helper\OrderPlace;
use Splitit\PaymentGateway\Model\LogFactory as LogModelFactory;
use Splitit\PaymentGateway\Model\ResourceModel\Log as LogResource;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Configuration;
use SplititSdkClient\Model\CancelInstallmentPlanRequest;
use SplititSdkClient\Model\GetInstallmentsPlanSearchCriteriaRequest;
use SplititSdkClient\Model\PlanData;
use SplititSdkClient\Model\RefundUnderCancelation;
use SplititSdkClient\Model\UpdateInstallmentPlanRequest;
use Magento\Framework\Data\Form\FormKey;

class Successasync extends Action
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderInterface $order
     */
    private $order;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $splititConfig;

    /**
     * @var TouchpointHelper
     */
    private $touchPointHelper;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var OrderPlace
     */
    private $orderPlace;

    /**
     * @var LoginAuthentication
     */
    private $loginAuth;

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
        ScopeConfigInterface $scopeConfig,
        JsonFactory $resultJsonFactory,
        OrderInterface $order,
        QuoteFactory $quoteFactory,
        CartRepositoryInterface $quoteRepository,
        OrderPlace $orderPlace,
        OrderSender $orderSender,
        Logger $logger,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        TouchpointHelper $touchPointHelper,
        LogModelFactory $logModelFactory,
        LogResource $logResource,
        FormKey $formKey
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->order = $order;
        $this->quoteFactory = $quoteFactory;
        $this->orderPlace = $orderPlace;
        $this->quoteRepository = $quoteRepository;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->touchPointHelper = $touchPointHelper;
        $this->logModelFactory = $logModelFactory;
        $this->logResource = $logResource;
        parent::__construct($context);
        /** validation for magento 2.3+. CsrfAwareActionInterface can be use instead */
        if (interface_exists("\Magento\Framework\App\CsrfAwareActionInterface")) {
            $request = $this->getRequest();
            if ($request instanceof RequestInterface && $request->isPost() && empty($request->getParam('form_key'))) {
                $request->setParam('form_key', $formKey->getFormKey());
            }
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $installmentPlanNumber = $params['InstallmentPlanNumber'];
        $log = $this->logResource->getByIPN($installmentPlanNumber);
        if ($log && $log->getIncrementId()) {
            return;
        }
        $touchPointData = $this->touchPointHelper->getTouchPointData();
        $session_id = $this->loginAuth->getLoginSession();

        $envSelected = $this->splititConfig->getEnvironment();
        $env = $envSelected == 'sandbox' ? 'sandbox' : 'production';
        Configuration::$env()->setTouchPoint($touchPointData);
        $apiInstance = new InstallmentPlanApi( Configuration::$env(), $session_id );

        if ($this->isNotPaid($installmentPlanNumber, $apiInstance)) {
            $this->cancelPlan($installmentPlanNumber, $apiInstance);
            return;
        }

        $request = new GetInstallmentsPlanSearchCriteriaRequest([
            'query_criteria' => [
                "InstallmentPlanNumber" => $installmentPlanNumber
            ],
        ]);
        $planDetails = $apiInstance->installmentPlanGet($request);
        $installmentPlan = $planDetails->getPlansList();
        $installmentPlan = $installmentPlan[0];
        $amount = $installmentPlan->getAmount()->getValue();
        $status = $installmentPlan->getInstallmentPlanStatus()->getCode();
        $quote = $this->quoteRepository->get($log->getQuoteId());

        $grandTotal = number_format((float)$quote->getGrandTotal(), 2, '.', '');
        $amount = number_format((float)$amount, 2, '.', '');
        $this->logger->debug([2]);
        if ($grandTotal == $amount && ($status == "PendingMerchantShipmentNotice" || $status == "InProgress")) {

            $orderId = $this->orderPlace->execute($quote, array());

            $orderObj = $this->order->load($orderId);

            $payment = $orderObj->getPayment();
            $paymentAction = $this->splititConfig->getPaymentAction();

            $payment->setTransactionId($installmentPlanNumber);
            $payment->setParentTransactionId($installmentPlanNumber);
            $payment->setInstallmentsNo($installmentPlan->getNumberOfInstallments());
            $payment->setIsTransactionClosed(0);
            $payment->setCurrencyCode($installmentPlan->getAmount()->getCurrency()->getCode());
            $payment->setCcType($installmentPlan->getActiveCard()->getCardBrand()->getCode());
            $payment->setIsTransactionApproved(true);

            $payment->registerAuthorizationNotification($grandTotal);

            $orderObj->addStatusToHistory(
                $orderObj->getStatus(), 'Payment InstallmentPlan was created with number ID: '
                . $installmentPlanNumber, false
            );
            if ($paymentAction == "authorize_capture") {

                $payment->setShouldCloseParentTransaction(true);
                $payment->setIsTransactionClosed(1);
                $payment->registerCaptureNotification($grandTotal);
                $orderObj->addStatusToHistory(
                    false, 'Payment NotifyOrderShipped was sent with number ID: ' . $installmentPlanNumber, false
                );
            }

            $this->orderSender->send($orderObj);
            $orderObj->save();
            $updateRequest = new UpdateInstallmentPlanRequest();
            $planData = new PlanData();
            $planData->setRefOrderNumber($orderObj->getIncrementId());
            $updateRequest->setPlanData($planData);

            try {
                $result = $apiInstance->installmentPlanUpdate($updateRequest);
                $this->updateLog($installmentPlanNumber, $orderObj);
            } catch (\Exception $e) {
                throw new \Exception(__('Error in adding order reference number to the installment plan. Please try again.'));
            }
        } else {
            $this->cancelPlan($installmentPlanNumber, $apiInstance);
        }
    }

    /**
     * @param string $ipn
     * @param InstallmentPlanApi $apiInstance
     */
    private function cancelPlan($ipn, $apiInstance)
    {
        $cancelRequest = new CancelInstallmentPlanRequest();
        $cancelRequest->setInstallmentPlanNumber($ipn);
        $cancelRequest->setRefundUnderCancelation(RefundUnderCancelation::ONLY_IF_A_FULL_REFUND_IS_POSSIBLE);
        $apiInstance->installmentPlanCancel($cancelRequest);
    }

    /**
     * @param string $ipn
     * @param InstallmentPlanApi $apiInstance
     * @return bool
     */
    private function isNotPaid($ipn, $apiInstance)
    {
        $paymentRequest = new \SplititSdkClient\Model\VerifyPaymentRequest();
        $paymentRequest->setInstallmentPlanNumber($ipn);
        $paymentResult = $apiInstance->installmentPlanVerifyPayment($paymentRequest);
        return !$paymentResult->getIsPaid();
    }

    /**
     * @param string $ipn
     * @param Order $orderObj
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function updateLog($ipn, $orderObj)
    {
        $log = $this->logResource->getByIPN($ipn);
        if (!$log) {
            $log = $this->logModelFactory->create();
            $log->setQuoteId($orderObj->getQuoteId());
            $log->setInstallmentPlanNumber($ipn);
            $log->setIsSuccess(true);
        }
        $log->setIncrementId($orderObj->getIncrementId());
        $log->setIsAsync(true);
        try {
            $this->logResource->save($log);
        } catch (\Exception $e) {
            // do nothing;
        }
    }
}
