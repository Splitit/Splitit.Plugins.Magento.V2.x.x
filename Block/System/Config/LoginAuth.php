<?php

namespace Splitit\PaymentGateway\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class LoginAuth extends Field
{
    const TEST_CREDS_ROUTE = 'splititpaymentgateway/verifycredentials/index';

    /**
     * @var string
     */
    protected $_template = 'Splitit_PaymentGateway::system/config/loginauth-button.phtml';

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for collect button
     *
     * @return string
     */
    public function getAjaxUrl()
    {
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        return $baseUrl . self::TEST_CREDS_ROUTE;
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'login_auth_button',
                'label' => __('Test Credentials'),
            ]
        );

        return $button->toHtml();
    }
}
