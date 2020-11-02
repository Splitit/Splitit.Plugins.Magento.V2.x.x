<?php

namespace Splitit\PaymentGateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * TODO: REFACTOR - Create an interface definition for this class
 * Class Config
 */
class Config
{
    const CONFIG_ENVIRONMENT = 'payment/splitit_payment/environment';
    const CONFIG_ACTIVE = 'payment/splitit_payment/active';
    const CONFIG_MERCHANT_ID = 'payment/splitit_payment/merchant_gateway_key';
    const CONFIG_MERCHANT_USERNAME = 'payment/splitit_payment/splitit_username';
    const CONFIG_MERCHANT_PASSWORD = 'payment/splitit_payment/splitit_password';
    const CONFIG_MIN_AMOUNT = 'payment/splitit_payment/min_order_amount';
    const CONFIG_UPSTREAM_CONTENT = 'payment/splitit_payment/upstream_messaging_enabled';
    const CONFIG_INSTALLMENT_RANGE = 'payment/splitit_payment/ranges';
    const CONFIG_PAYMENT_ACTION = 'payment/splitit_payment/payment_action';
    const CONFIG_3D_SECURE = 'payment/splitit_payment/splitit_3dsecure';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        ProductMetadataInterface $productMetadata
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Splitit Get config constructor
     * @param string $config_path
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            ScopeInterface::SCOPE_STORE,
            null
        );
    }

    /**
     * Gets threshold amount for Splitit Payment Option.
     *
     * @return float
     */
    public function getSplititMinOrderAmount()
    {
        return (double) $this->getConfig(self::CONFIG_MIN_AMOUNT);
    }


    /**
     * Gets value of configured environment.
     *
     * Possible values: production or sandbox.
     *
     */
    public function getEnvironment()
    {
        return $this->getConfig(self::CONFIG_ENVIRONMENT);
    }

    /**
     * Gets API merchant ID.
     *
     * @return string
     */
    public function getApiMerchantId()
    {
        return $this->getConfig(self::CONFIG_MERCHANT_ID);
    }

    /**
     * Gets Splitit Username.
     *
     * @return string
     */
    public function getApiUsername()
    {
        return $this->getConfig(self::CONFIG_MERCHANT_USERNAME);
    }

    /**
     * Gets Splitit Password.
     *
     * @return string
     */
    public function getApiPassword()
    {
        return $this->getConfig(self::CONFIG_MERCHANT_PASSWORD);
    }


    /**
     * Gets Payment configuration status.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->getConfig(self::CONFIG_ACTIVE);
    }

    /**
     * Get enabled upstream content page settings.
     *
     * @return string
    */
    public function getUpstreamContentSettings()
    {
        return $this->getConfig(self::CONFIG_UPSTREAM_CONTENT);
    }

    /**
     * Get installment range from admin config.
     *
     * @return array
    */
    public function getInstallmentRange()
    {
        $installmentRangeValue = $this->getConfig(self::CONFIG_INSTALLMENT_RANGE);
        try {
            // Fix for 2.0.0-2.2.0
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            if (version_compare($this->productMetadata->getVersion(), '2.2.0', '<')) {
                $serializer = $objectManager->get(\Magento\Framework\Json\Helper\Data::class);
                $unserializedata = $serializer->jsonDecode($installmentRangeValue);
            } else {
                $serializer = $objectManager->get(\Magento\Framework\Serialize\Serializer\Json::class);
                $unserializedata = $serializer->unserialize($installmentRangeValue);
            }
            $instRangeArray = array();
            foreach ($unserializedata as $key => $row)
            {
                $instRangeArray[] = [
                    $row['priceFrom'],
                    $row['priceTo'],
                    $row['installment']
                ];
            }

            return $instRangeArray;
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        return null;
    }

    /**
     * Gets value of payment action.
     *
     * Possible values: authorize or authorize_capture.
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->getConfig(self::CONFIG_PAYMENT_ACTION);
    }

    /**
     * Gets value of 3D secure setting.
     *
     * @return bool
     */
    public function get3DSecure()
    {
        return $this->getConfig(self::CONFIG_3D_SECURE);
    }
}
