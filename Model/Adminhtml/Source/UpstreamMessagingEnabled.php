<?php

namespace Splitit\PaymentGateway\Model\Adminhtml\Source;

/**
 * Class UpstreamMessagingEnabled
 *
 * @package Splitit\PaymentGateway\Model\Config\Source
 */
class UpstreamMessagingEnabled implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [['value' => 'home page', 'label' => __('home page')],['value' => 'product page', 'label' => __('product page')],['value' => 'cart', 'label' => __('cart')],['value' => 'footer', 'label' => __('footer')]];
    }

    public function toArray()
    {
        return ['home page' => __('home page'),'product page' => __('product page'),'cart' => __('cart'),'footer' => __('footer')];
    }
}
