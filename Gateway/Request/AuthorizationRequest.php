<?php

namespace Splitit\PaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AuthorizationRequest implements BuilderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();
        $succeeded = $payment->getAdditionalInformation('succeeded');
        $installmentPlanNumber = $payment->getAdditionalInformation('installmentPlanNum');
        $order = $paymentDO->getOrder();
        $orderNumber = $order->getOrderIncrementId();
        $address = $order->getBillingAddress();

        return [
            'TXN_TYPE' => 'A',
            'INVOICE' => $order->getOrderIncrementId(),
            'CURRENCY' => $order->getCurrencyCode(),
            'Email' => $address->getEmail(),
            'ApiKey' => $this->config->getValue(
                'merchant_gateway_key',
                $order->getStoreId()
            ),
            'Amount' => $order->getGrandTotalAmount(),
            'InstallmentPlanNumber' => $installmentPlanNumber,
            'OrderRefNumber' => $orderNumber,
            'Succeeded' => $succeeded
        ];
    }
}
