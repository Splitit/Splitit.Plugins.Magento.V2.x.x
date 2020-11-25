<?php

namespace Splitit\PaymentGateway\Plugin\Magento\Payment\Model\Method;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Model\Quote;
use Magento\Payment\Model\Method\Adapter as ParentAdapter;

class Adapter
{
    private $produceMetadata;
    private $eventManager;
    private $paymentDataObjectFactory;

    public function __construct(
        ProductMetadataInterface $productMetadata,
        ManagerInterface $eventManager,
        PaymentDataObjectFactory $paymentDataObjectFactory
    ) {
        $this->produceMetadata = $productMetadata;
        $this->eventManager = $eventManager;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    public function aroundIsAvailable(ParentAdapter $subject, \Closure $proceed, Quote $quote = null)
    {
        if (version_compare($this->produceMetadata->getVersion(), '2.1.2', '>')) {
            return $proceed($quote);
        }

        if (!$subject->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);
        try {
            $infoInstance = $subject->getInfoInstance();
            if ($infoInstance !== null) {
                $validator = $subject->getValidatorPool()->get('availability');
                $result = $validator->validate(
                    [
                        'payment' => $this->paymentDataObjectFactory->create($infoInstance)
                    ]
                );

                $checkResult->setData('is_available', $result->isValid());
            }
        } catch (\Exception $e) {
            // pass
        }

        // for future use in observers
        $this->eventManager->dispatch(
            'payment_method_is_active',
            [
                'result' => $checkResult,
                'method_instance' => $this,
                'quote' => $quote
            ]
        );

        return $checkResult->getData('is_available');
    }
}
