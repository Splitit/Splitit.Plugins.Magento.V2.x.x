<?php

namespace Splitit\PaymentGateway\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use SplititSdkClient\Api\InstallmentPlanApi;
use SplititSdkClient\Model\StartInstallmentsRequest;
use SplititSdkClient\Configuration;
use Splitit\PaymentGateway\Gateway\Login\LoginAuthentication;
use SplititSdkClient\Model\CancelInstallmentPlanRequest;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Splitit\PaymentGateway\Helper\TouchpointHelper;

class Index extends Action
{

    /**
    * @var PageFactory
    */
    protected $resultPageFactory;

    /**
    * @var OrderManagementInterface
    */
    protected $orderManagement;

    /**
    * @var Http
    */
    protected $request;

    /**
    * @var LoginAuthentication
    */
    protected $loginAuth;

    /**
    * @var Config
    */
    protected $splititConfig;

    /**
    * @var OrderRepositoryInterface
    */
    protected $orderRepository;

    /**
     * @var TouchpointHelper
     */
    protected $touchPointHelper;

     /**
      * Constructor
      *
      * @param Context $context
      * @param PageFactory $resultPageFactory
      * @param OrderManagementInterface $orderManagement
      * @param Http $request
      * @param LoginAuthentication $loginAuth
      * @param Config $splititConfig
      * @param OrderRepositoryInterface $orderRepository
      * @param TouchpointHelper $touchPointHelper
      */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        OrderManagementInterface $orderManagement,
        Http $request,
        LoginAuthentication $loginAuth,
        Config $splititConfig,
        OrderRepositoryInterface $orderRepository,
        TouchpointHelper $touchPointHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->orderManagement = $orderManagement;
        $this->request = $request;
        $this->loginAuth = $loginAuth;
        $this->splititConfig = $splititConfig;
        $this->orderRepository = $orderRepository;
        $this->touchPointHelper = $touchPointHelper;
        parent::__construct($context);
    }

    /**
     * TODO : Inject InstallmentPlanApi, CancelInstallmentPlanRequest
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $params = $this->request->getParams();
        $resultRedirect = $this->resultRedirectFactory->create();

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
        $cancelRequest->setInstallmentPlanNumber($params['txnId']);
        $cancelRequest->setRefundUnderCancelation("NoRefunds");

        try {
            $cancelResponse = $apiInstance->installmentPlanCancel($cancelRequest);
            $this->orderManagement->cancel($params['orderId']);
            $order = $this->orderRepository->get($params['orderId']);
            $order->setState(Order::STATE_CANCELED, true);
            $order->setStatus(Order::STATE_CANCELED);
            $order->addStatusToHistory($order->getStatus(), 'Order cancelled without issuing refund.');
            $order->save();
            $this->messageManager->addSuccessMessage(__('Order has been cancelled without issuing refund'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $resultRedirect->setPath('sales/order/view', [ 'order_id' => $params['orderId']]);
    }

    /**
     * Check Permission.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
