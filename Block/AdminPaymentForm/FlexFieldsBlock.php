<?php

namespace Splitit\PaymentGateway\Block\AdminPaymentForm;

use Magento\Framework\View\Element\Template;

class FlexFieldsBlock extends Template
{
    const FLEXFIELDS_CONTROLLER_ROUTE = 'splititpaymentgateway/flexfields/index';
    const QUOTE_CONTROLLER_ROUTE = 'splititpaymentgateway/flexfields/updatequote';

    /**
     * Return ajax url for flexfields render
     *
     * @return string
    */
    public function getAjaxUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::FLEXFIELDS_CONTROLLER_ROUTE;
    }

    /**
     * Return ajax url for quote update
     *
     * @return string
    */
    public function getQuoteUpdateAjaxUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::QUOTE_CONTROLLER_ROUTE;
    }
}
