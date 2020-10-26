<?php

namespace Splitit\PaymentGateway\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Environment
 */
class Environment implements ArrayInterface
{
    const ENVIRONMENT_SANDBOX = 'sandbox';
    const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * Possible environment types for admin configuration
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ENVIRONMENT_SANDBOX,
                'label' => 'Sandbox',
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => 'Production'
            ]
        ];
    }
}