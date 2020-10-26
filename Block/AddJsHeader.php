<?php

namespace Splitit\PaymentGateway\Block;

use Magento\Framework\View\Element\Template;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Splitit\PaymentGateway\Model\Adminhtml\Source\Environment;

class AddJsHeader extends Template
{
    private $config;

    public function __construct(
        Config $config,
        Template\Context $context,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function toHtml()
    {
        $version = floor(time() / 60); // version should be new for each minute
        if ($this->config->getEnvironment() == Environment::ENVIRONMENT_PRODUCTION) {
            $html = '<link  rel="stylesheet" type="text/css"  media="all" href="https://flex-fields.production.splitit.com/css/splitit.flex-fields.min.css" />' . "\n";
            $html .= '<script type="text/javascript" src=" https://flex-fields.production.splitit.com/js/dist/splitit.flex-fields.sdk.js?v=' . $version . '"></script>';
        } else {
            $html = '<link  rel="stylesheet" type="text/css"  media="all" href="https://flex-fields.sandbox.splitit.com/css/splitit.flex-fields.min.css" />' . "\n";
            $html .= '<script type="text/javascript" src=" https://flex-fields.sandbox.splitit.com/js/dist/splitit.flex-fields.sdk.js?v=' . $version . '"></script>';
        }
        return $html;
    }
}
