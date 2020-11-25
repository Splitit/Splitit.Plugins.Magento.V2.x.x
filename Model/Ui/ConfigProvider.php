<?php

namespace Splitit\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Splitit\PaymentGateway\Gateway\Config\Config;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'splitit_payment';

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'threshold' => $this->config->getSplititMinOrderAmount()
                ]
            ]
        ];
    }
}
