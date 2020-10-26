<?php

namespace Splitit\PaymentGateway\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Splitit\PaymentGateway\Gateway\Config\Config;
use Magento\Quote\Api\CartRepositoryInterface;

class AdminAvailabilityHandler extends AbstractValidator
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
     * @var CartRepositoryInterface
    */
    protected $quoteRepository;

    /**
     * Splitit AvailabilityHandler constructor
     * 
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $splititConfig,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->splititConfig = $splititConfig;
        $this->quoteRepository = $quoteRepository;
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

        $paymentDO = $validationSubject['payment'];
        $payment = $paymentDO->getPayment();
        $quoteId = $payment->getQuoteId();
        $quote = $this->quoteRepository->get($quoteId);
        $totalAmount = $quote->getGrandTotal();

        $thresholdAmount = $this->splititConfig->getSplititMinOrderAmount();

        if ($totalAmount < $thresholdAmount) {
            $isValid = false;
        }

        return $this->createResult($isValid);
    }
}
