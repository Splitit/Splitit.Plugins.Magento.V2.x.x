<?php

namespace Splitit\PaymentGateway\Block\UpstreamMessaging;

use Splitit\PaymentGateway\Block\UpstreamMessaging;
use Splitit\PaymentGateway\Model\Adminhtml\Source\Environment;
class UpstreamLogo extends UpstreamMessaging
{
    /**
     * Gets threshold amount from config
     *
     * @return float
     */
    public function getTemplateName()
    {
        if($this->splititConfig->getEnvironment() == Environment::ENVIRONMENT_PRODUCTION) {
            return 'upstream-messaging';
        }
        return 'upstream-messaging-sandbox';
    }
}
