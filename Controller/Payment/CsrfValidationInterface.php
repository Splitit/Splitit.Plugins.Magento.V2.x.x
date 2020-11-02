<?php

namespace Splitit\PaymentGateway\Controller\Payment;

if (interface_exists(\Magento\Framework\App\CsrfAwareActionInterface::class)) {
    interface CsrfValidationInterface extends \Magento\Framework\App\CsrfAwareActionInterface
    {}
} else {
    interface CsrfValidationInterface
    {}
}
