/*browser:true*/
/*global define*/
define(
  [
    'jquery',
    'Magento_Customer/js/customer-data'
  ],
  function ($, customerData) {
    'use strict';
    return function (config) {
      var subtotal = customerData.get('cart')().subtotalAmount;
      if (subtotal > config.threshold || config.productprice > config.threshold) {
        $('.splitit-product-block').show();
      }
      $(document).on('ready', ".product-info-price", function () {
        var priceHtml = $('.product-info-price').find('.price')[0]['firstChild']['data'];
        var newPrice = priceHtml.replace(/[^0-9.]/g, ""); //removes all special chatacters (currency, comma) except for . and numbers
        var num = Number(newPrice);
        if (subtotal > config.threshold || num > config.threshold) {
          $('.splitit-product-block').show(); //shows the block in case this is hidden from previous price change
          jQuery(".splitit-product-block").attr('data-splitit-amount', newPrice)
          splitit.ui.refresh();
        } else {
          $('.splitit-product-block').hide(); //hides the block if threshold isn't met.
        }
      });
    }
  }
);