<?php

namespace Splitit\PaymentGateway\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class PaymentAction
 */
class PaymentAction implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        // fix for magento 2.0.0-2.0.5 versions
        if (defined('\Magento\Payment\Model\MethodInterface::ACTION_AUTHORIZE')) {
            $authorize = \Magento\Payment\Model\MethodInterface::ACTION_AUTHORIZE;
        } else {
            $authorize = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE;
        }
        if (defined('Magento\Payment\Model\MethodInterface::ACTION_AUTHORIZE_CAPTURE')) {
            $authorizeCapture = \Magento\Payment\Model\MethodInterface::ACTION_AUTHORIZE_CAPTURE;
        } else {
            $authorizeCapture = \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;
        }
        return [
            [
                'value' => $authorize,
                'label' => __('Authorize'),
            ],
            [
                'value' => $authorizeCapture,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
