<?php

namespace Splitit\PaymentGateway\Block\Adminhtml\Creditmemo;

use Magento\Backend\Block\Template;
use Splitit\PaymentGateway\Model\Ui\ConfigProvider;

class Alert extends Template
{
    protected $coreRegistry;

    public function __construct(
        Template\Context $context,
        array $data = [],
        \Magento\Framework\Registry $registry
    )
    {
        parent::__construct($context, $data);
        $this->coreRegistry = $registry;
    }

    /**
     * Retrieve creditmemo model instance
     *
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        return $this->coreRegistry->registry('current_creditmemo');
    }

    public function isSplititMethod()
    {
        if($this->getCreditmemo()
            && $this->getCreditmemo()->getOrder()
            && $this->getCreditmemo()->getOrder()->getPayment()) {
            return $this->getCreditmemo()->getOrder()->getPayment()->getMethod() == ConfigProvider::CODE;
        }
        return false;
    }
}
