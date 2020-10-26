<?php

namespace Splitit\PaymentGateway\Block;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Splitit\PaymentGateway\Block\AdminPaymentForm\FlexFieldsBlock;

class Payment extends Template
{
    /**
     * @var ConfigProviderInterface
     */
    protected $config;

    /**
     * @var FlexFieldsBlock
     */
    protected $flexfield;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigProviderInterface $config
     * @param FlexFieldsBlock $flexfield
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProviderInterface $config,
        FlexFieldsBlock $flexfield,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->flexfield = $flexfield;
    }

    /**
     * @return string
     */
    public function getPaymentConfig()
    {
        $payment = $this->config->getConfig()['payment'];
        $config = $payment[$this->getCode()];
        $config['code'] = $this->getCode();
        $config += [
            'ajaxUrl' => $this->flexfield->getAjaxUrl(),
            'quoteAjaxUrl' => $this->flexfield->getQuoteUpdateAjaxUrl()
        ];
        return json_encode($config, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return "splitit_payment";
    }
}
