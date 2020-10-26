<?php

namespace Splitit\PaymentGateway\Block\UpstreamMessaging;

use Splitit\PaymentGateway\Block\UpstreamMessaging;

class UpstreamMessagingFooter extends UpstreamMessaging
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
            if ($cartPageUpstreamEnabled) {
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
            if ($enabledBlock == 'footer'){
                return true;
            }
        }
        return false;
    }
}
