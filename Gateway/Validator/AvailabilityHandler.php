<?php

namespace Splitit\PaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Splitit\PaymentGateway\Gateway\Config\Config;

class AvailabilityHandler extends AbstractValidator
{
    /**
     * @var ResultInterface
    */
    protected $resultFactory;

    /**
     * @var Config
    */
    protected $splititConfig;

    /**
     * @var Cart
    */
    protected $cart;

    /**
     * Splitit AvailabilityHandler constructor
     * 
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     * @param Cart $cart
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $splititConfig,
        Cart $cart
    ) {
        $this->splititConfig = $splititConfig;
        $this->cart = $cart;
        parent::__construct($resultFactory);
    }

    /**
     * Performs validation of payment method availability
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $isValid = true;

        $thresholdAmount = $this->splititConfig->getSplititMinOrderAmount();
        $cartTotal = $this->cart->getQuote()->getGrandTotal();

        if ($cartTotal < $thresholdAmount) {
            $isValid = false;
        }

        return $this->createResult($isValid);
    }
}
