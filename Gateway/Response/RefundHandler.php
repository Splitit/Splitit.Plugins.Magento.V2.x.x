<?php

namespace Splitit\PaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundHandler implements HandlerInterface
{
    /**
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $shouldCloseParentTransaction = $this->shouldCloseParentTransaction($payment);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setIsTransactionClosed(true);

        if ($shouldCloseParentTransaction) {
            $payment->setShouldCloseParentTransaction(true);
        } else {
            $payment->setShouldCloseParentTransaction(false);
        }
    }

    public function shouldCloseParentTransaction($payment)
    {
        return !(bool)$payment->getCreditmemo()->getInvoice()->canRefund();
    }
}
