<?php

namespace Splitit\PaymentGateway\Block\UpstreamMessaging;

use Splitit\PaymentGateway\Block\UpstreamMessaging;

class UpstreamContentCart extends UpstreamMessaging
{
    /**
     * Returns true/false based on admin configuration
     *
     * @return boolean
     */
    public function canDisplay()
    {
        $isPaymentActive = $this->splititConfig->isActive();
        if ($isPaymentActive) {
            $cartPageUpstreamEnabled = $this->checkIfCartPageEnabled();
            $cartTotal = $this->getCurrentCartTotal();
            $thresholdAmount = $this->getThreshold();

            if ($cartPageUpstreamEnabled && ($cartTotal > $thresholdAmount)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if Admin Config has Cart Page enabled for upstream content
     *
     * @return boolean
     */
    public function checkIfCartPageEnabled()
    {
        $upstreamContentSettings = $this->getSavedUpstreamContentSettings();
        $enabledUpstreamBlocks = explode(',', $upstreamContentSettings);
        foreach ($enabledUpstreamBlocks as $enabledBlock) {
            if ($enabledBlock == 'cart'){
                return true;
            }
        }
        return false;
    }

    /**
     * Gets current cart subtotal amount
     *
     * @return int|null
     */
    public function getCurrentCartTotal()
    {
        $subtotal = $this->cart->getQuote()->getSubtotal();
        if (!empty($subtotal)) {
            return $subtotal;
        }

        return null;
    }

    /**
     * Gets current order grand total
     *
     * @return int
     */
    public function getOrderTotalPrice()
    {
        return $this->cart->getQuote()->getGrandTotal();
    }

    /**
     * Gets installment number per admin config
     *
     * @return string
     */
    public function getInstallmentNumber()
    {

        $cartTotal =  $this->getCurrentCartTotal();

        $installmentNum = $this->splititConfig->getUpstreamDefaultInstallmentsAmount();
        if( ! $installmentNum) {
            $installmentArray =  $this->getInstallmentRangeValues();
            if(is_array($installmentArray)) {
                foreach ($installmentArray as $installmentArrayItem) {
                    if ($cartTotal >= $installmentArrayItem[0] && $cartTotal <= $installmentArrayItem[1]) {
                        $size = count($installmentArrayItem[2]);
                        if($size % 2 == 0) {
                            $installmentNum = $installmentArrayItem[2][($size/2)-2];
                        } else {
                            $installmentNum = $installmentArrayItem[2][ceil($size/2)-1];
                        }
                        break;
                    }
                }
            }
        }


        return $installmentNum;
    }

    /**
     * Gets threshold amount from config
     *
     * @return float
     */
    public function getThreshold()
    {
        return $this->splititConfig->getSplititMinOrderAmount();
    }
}
