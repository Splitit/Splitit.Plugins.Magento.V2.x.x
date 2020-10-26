<?php

namespace Splitit\PaymentGateway\Model\Adminhtml\Source;

use Magento\Payment\Model\MethodInterface;
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
        return [
            [
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => MethodInterface::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
