define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {

        $.widget('mage.SwatchRenderer', widget, {
            _UpdatePrice: function () {
                var prices = this._getNewPrices();
                var newPrice = {detail: prices.finalPrice.amount};
                var oldPrice = window.splitit_product_price;
                if (oldPrice != undefined && oldPrice != newPrice.detail) {
                    var installments = (newPrice.detail/parseFloat(window.splitit_installments)).toFixed(2);
                    var newValue = window.splitit_currency + installments;
                    $('.splitit-product-block').find('.-splitit--text-price').html(newValue);
                    window.splitit_product_price = newPrice.detail;
                }
                return this._super();
            }
        });

        return $.mage.SwatchRenderer;
    }
});
