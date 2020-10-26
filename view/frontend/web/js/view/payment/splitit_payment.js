/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'splitit_payment',
            component: 'Splitit_PaymentGateway/js/view/payment/method-renderer/splitit_payment'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);