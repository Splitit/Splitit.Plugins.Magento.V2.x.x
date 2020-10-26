<?php

namespace Splitit\PaymentGateway\Test\Unit\Model\Ui;

use Splitit\PaymentGateway\Gateway\Http\Client\SplititCreateApiImplementation;
use Splitit\PaymentGateway\Model\Ui\ConfigProvider;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfig()
    {
        $configProvider = new ConfigProvider();

        static::assertEquals(
            [
                'payment' => [
                    ConfigProvider::CODE => [
                        'transactionResults' => [
                            SplititCreateApiImplementation::SUCCESS => __('Success'),
                            SplititCreateApiImplementation::FAILURE => __('Fraud')
                        ]
                    ]
                ]
            ],
            $configProvider->getConfig()
        );
    }
}
