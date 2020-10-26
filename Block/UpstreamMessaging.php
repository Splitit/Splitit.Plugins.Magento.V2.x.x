<?php
namespace Splitit\PaymentGateway\Block;

use Magento\Framework\View\Element\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Locale\Resolver;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Registry;
use Splitit\PaymentGateway\Gateway\Config\Config;

class UpstreamMessaging extends Template
{
    /**
     * @var Config
    */
    protected $splititConfig;

    /**
     * @var CurrencyFactory
    */
    protected $currencyFactory;

    /**
     * @var Resolver
    */
    protected $locale;

    /**
     * @var Registry
    */
    protected $registry;

    /**
     * @var Cart
    */
    protected $cart;

    /**
     * UpstreamMessaging constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Splitit\PaymentGateway\Gateway\Config $splititConfig
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Framework\Locale\Resolver $locale
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Cart $cart
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $splititConfig,
        CurrencyFactory $currencyFactory,
        Resolver $locale,
        Registry $registry,
        Cart $cart,
        array $data = []
    )
    {
        $this->splititConfig = $splititConfig;
        $this->currencyFactory = $currencyFactory->create();
        $this->locale = $locale;
        $this->registry = $registry;
        $this->cart = $cart;
        parent::__construct($context, $data);
    }

    /**
     * Gets merchant id from configuration.
     * 
     * @return string
    */
    public function getMerchantId()
    {
        return $this->splititConfig->getApiMerchantId();
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    } 

    /**
     * Get currency symbol for current locale and currency code
     *
     * @return string
     */    
    public function getCurrentCurrencySymbol()
    {
        $currencyCode = $this->getCurrentCurrencyCode();
        $currency = $this->currencyFactory->load($currencyCode);
        $currencySymbol = $currency->getCurrencySymbol();
        return $currencySymbol;
    }  

    /**
     * Get current store language code.
     * 
     * @return string
    */
    public function getCurrentStoreLanguage()
    {
        $currentLocaleCode = $this->locale->getLocale();
        $language = strstr($currentLocaleCode, '_', true);
        return $language;
    }

    /**
     * Get enabled upstream content page settings.
     * 
     * @return string
    */
    public function getSavedUpstreamContentSettings()
    {
        return $this->splititConfig->getUpstreamContentSettings();
    }

    /**
     * Get installment range values.
     * 
     * @return array
    */
    public function getInstallmentRangeValues()
    {
        return $this->splititConfig->getInstallmentRange();
    }

    /**
     * Get locale name for splitit culture.
     * 
     * @return string
    */
    public function getCultureName()
    {
        return $this->locale->getLocale();
    }
}
