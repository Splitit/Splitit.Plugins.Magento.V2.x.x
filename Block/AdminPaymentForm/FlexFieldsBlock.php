<?php

namespace Splitit\PaymentGateway\Block\AdminPaymentForm;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class FlexFieldsBlock extends Template
{
    const FLEXFIELDS_CONTROLLER_ROUTE = 'splititflexfields/flexfields/index';
    const QUOTE_CONTROLLER_ROUTE = 'adminquote/flexfields/updatequote';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Return ajax url for flexfields render
     *
     * @return string
    */
    public function getAjaxUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::FLEXFIELDS_CONTROLLER_ROUTE;
    }

    /**
     * Return ajax url for quote update
     *
     * @return string
    */
    public function getQuoteUpdateAjaxUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::QUOTE_CONTROLLER_ROUTE;
    }
}
