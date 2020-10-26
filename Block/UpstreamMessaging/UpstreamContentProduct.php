<?php

namespace Splitit\PaymentGateway\Block\UpstreamMessaging;

use Splitit\PaymentGateway\Block\UpstreamMessaging;

class UpstreamContentProduct extends UpstreamMessaging
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
            $productPageUpstreamEnabled = $this->checkIfProductPageEnabled();
            if ($productPageUpstreamEnabled) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if Admin Config has Product Page enabled for upstream content
     *
     * @return boolean
     */
    public function checkIfProductPageEnabled()
    {
        $upstreamContentSettings = $this->splititConfig->getUpstreamContentSettings();
        $enabledUpstreamBlocks = explode(',', $upstreamContentSettings);
        foreach ($enabledUpstreamBlocks as $enabledBlock) {
            if ($enabledBlock == 'product page'){
                return true;
            }
        }
        return false;
    }

    /**
     * Gets Current Product Price
     *
     * @return string|null
     */
    public function getCurrentProductPrice()
    {
        $currentProduct = $this->registry->registry('current_product');
        if (isset($currentProduct)) {
            if ($currentProduct->getTypeId() == 'bundle') {
                $productPrice = $currentProduct->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue();
            } elseif ($currentProduct->getTypeId() == 'grouped') {
                $associatedProducts = $currentProduct->getTypeInstance(true)->getAssociatedProducts($currentProduct); 
                foreach ($associatedProducts as $childProduct) {  
                    $prices[] = $childProduct->getPrice(); 
                } 
                sort($prices);
                $productPrice = $prices[0];
            } else {
                $productPrice = $currentProduct->getFinalPrice();
            }
            return bcdiv($productPrice, 1, 2);
        }
        return null;
    }

    /**
     * Gets threshold amount from splitit config
     *
     * @return string|null
     */
    public function getThresholdAmount()
    {
        return $thresholdAmount = $this->splititConfig->getSplititMinOrderAmount();
    }

    /**
     * Gets installment number per admin config
     *
     * @return string
     */
    public function getInstallmentNumber()
    {
        $installmentArray =  $this->getInstallmentRangeValues();
        $productTotal =  $this->getCurrentProductPrice();
        $installmentNum = 3; //setting default value
        if (is_array($installmentArray)) {
            foreach ($installmentArray as $installmentArrayItem) {
                if ($productTotal >= $installmentArrayItem[0] && $productTotal <= $installmentArrayItem[1]) {
                    $installmentNum = $installmentArrayItem[2];
                    break;
                }
            }
        }
        return $installmentNum;
    }
}
