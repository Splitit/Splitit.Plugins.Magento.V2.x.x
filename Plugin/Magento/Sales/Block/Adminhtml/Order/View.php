<?php

namespace Splitit\PaymentGateway\Plugin\Magento\Sales\Block\Adminhtml\Order;

use Magento\Backend\Model\UrlInterface;

class View
{
    const CANCEL_CONTROLLER_ROUTE = 'canceladmin/index/index';

    /**
     * Constructor
     * @param UrlInterface $backendUrl
     * @return void
     */
    public function __construct(UrlInterface $backendUrl)
    {
        $this->_backendUrl = $backendUrl;
    }

    /**
     * Adds cancel without refund button to splitit_payment orders
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View $subject
     * @return void
     */
    public function beforeGetOrderId(\Magento\Sales\Block\Adminhtml\Order\View $subject)
    {
        $order = $subject->getOrder();
        $orderId = $order->getId();
        $payment = $order->getPayment();
        $methodCode = $payment->getMethodInstance()->getCode();
        $txnId = $payment->getLastTransId();
        $message ='Are you sure you want to Cancel this order without issuing refund?';
        $query = array('txnId' => $txnId, 'orderId' => $orderId);
        $url = $this->_backendUrl->getUrl(self::CANCEL_CONTROLLER_ROUTE, ['_use_rewrite' => true, '_query' => $query]);

        if ($methodCode == "splitit_payment") { 
            $subject->addButton(
                'cancelwithoutrefund',
                [
                    'label' => __('Cancel Without Refund'), 
                    'onclick' => "confirmSetLocation('{$message}', '{$url}')",
                    'class' => 'reset'],
                -1
            );
        }
        return null;
    }

}
