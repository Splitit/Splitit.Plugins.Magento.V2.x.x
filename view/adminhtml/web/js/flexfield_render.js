/*browser:true*/
/*global define*/
define([
  "jquery",
  "uiComponent",
  "Magento_Ui/js/modal/alert",
  "Magento_Ui/js/lib/view/utils/dom-observer",
  "mage/translate",
], function ($, Class, alert, domObserver, $t) {
  "use strict";
  return Class.extend({
    defaults: {
      $selector: null,
      selector: "edit_form",
      container: "payment_form_splitit_payment",
      active: false,
      scriptLoaded: false,
      checkout: null,
      imports: {
        onActiveChange: "active",
      },
      additional_data: {},
    },

    initObservable: function () {
      var self = this;
      self.$selector = $("#" + self.selector);
      this._super().observe(["active", "scriptLoaded"]);
      self.$selector.off("changePaymentMethod." + this.code).on("changePaymentMethod." + this.code, this.changePaymentMethod.bind(this));
      domObserver.get("#" + self.container, function () {
        self.$selector.off("submit");
      });
      return this;
    },

    setPaymentDetails: function (ipn, successMsg) {
      $('#payment_form_splitit_payment').find('[name="payment[installmentPlanNum]"]').val(ipn);
      $('#payment_form_splitit_payment').find('[name="payment[succeeded]"]').val(successMsg);
    },

    changePaymentMethod: function (event, method) {
      if (method === this.code) {
        this.loadScript();
        this.active(method === this.code);
      }
      return this;
    },

    onActiveChange: function (isActive) {
      if (!isActive) {
        this.$selector.off("submitOrder.splitit_payment");
        return;
      }
      this.disableEventListeners();
      this.active('splitit_payment' === this.code);
      this.enableEventListeners();
      if ('splitit_payment' === this.code) {
        this.loadScript();
      }
    },

    loadScript: function () {
      $('body').trigger('processStart');
      var self = this;
      $.ajax({
        url: self.quoteAjaxUrl,
        method: "post",
        data: {
          quoteId: window.order.quoteId
        }
      }).done(function (data) {
        var updatedAmount = data;
        if (updatedAmount > 0.00) { //don't call initSplitit if amount is 0.
          if (updatedAmount > self.threshold) { //don't call initSplitit if threshold isn't met and show error.
            self.initSplitit(updatedAmount);
          } else {
            self.error($.mage.__('Splitit payment is not available as minimum threshold amount is not met. Please select another payment method.'));
            $('#payment_form_splitit_payment').hide();
          }
        }
      });
      $('body').trigger('processStop');
    },

    initSplitit: function (amount) {
      var self = this;
      self.amount = amount;
      var billingAddressArray = window.order.serializeData(window.order.billingAddressContainer).toObject();
      var flexFieldsInstance = Splitit.FlexFields.setup({
        fields: {
          cardholderName: {
            selector: "#splitit-card-holder-full-name",
          },
          number: {
            selector: "#splitit-card-number",
          },
          cvv: {
            selector: "#splitit-cvv",
          },
          expirationDate: {
            selector: "#splitit-expiration-date",
          },
        },
        installmentPicker: {
          selector: "#installment-picker",
        },
        termsConditions: {
          selector: "#splitit-terms-conditions",
        },
        errorBox: {
          selector: "#splitit-error-box",
        },
        paymentButton: {
          selector: "#splitit-btn-pay",
        },
      }).ready(function () {
        var splititFlexFields = this;
        $.ajax({
          url: self.ajaxUrl,
          method: "post",
          data: {
            amount: self.amount,
            numInstallments: '',
            billingAddress: {
              AddressLine: billingAddressArray["order[billing_address][street][0]"],
              AddressLine2: billingAddressArray["order[billing_address][street][1]"],
              City: billingAddressArray["order[billing_address][city]"],
              State: billingAddressArray["order[billing_address][region_id]"],
              Country: billingAddressArray["order[billing_address][country_id]"],
              Zip: billingAddressArray["order[billing_address][postcode]"],
            },
            consumerModel: {
              FullName: billingAddressArray["order[billing_address][firstname]"] + " " +
                billingAddressArray["order[billing_address][lastname]"],
              Email: $("#email").val(),
              PhoneNumber: billingAddressArray["order[billing_address][telephone]"],
              CultureName: "en-us",
            },
          },
          success: function (data) {
            var instPlanNumber = data.installmentPlan.InstallmentPlanNumber;
            splititFlexFields.setPublicToken(data.publicToken);
            splititFlexFields.setInstallmentPlanNumber(instPlanNumber);
            splititFlexFields.setTermsConditionsUrl(data.termsAndConditionsUrl);
            splititFlexFields.setPrivacyPolicyUrl(data.privacyPolicyUrl);
          },
        });
      }).onSuccess(function (result) {
        var instNum = flexFieldsInstance.getSessionParams().planNumber;
        if (typeof result.secure3dRedirectUrl !== "undefined") { //if onSuccess is being directed after 3ds check
          var successMsg = true; //if 3ds, onSuccess method is only hit when 3ds is successful.
        } else {
          var successMsg = result.data.responseHeader.succeeded;
        }
        self.setPaymentDetails(instNum, successMsg);
        if (successMsg) {
          $("#" + self.selector).trigger("realOrder");
        } else {
          self.error($.mage.__('Splitit payment is not available. Please try again later.'));
        }
      }).onError(function (err) {
        if (err !== "undefined" && err.length > 0) {
          var errMsg = err[0]['error'];
          self.error($t(errMsg + " Please try again!"));
        }
      }).on3dComplete(function (data) {
        /* This method is only triggered when 3ds is enabled.
         * On success it goes to onSucess and on Error goes to onError
         * Displaying error message/going for successful order are being handled in relevant methods.
         */
      });
    },

    enableEventListeners: function () {
      this.$selector.on("submitOrder.splitit_payment", this.submitOrder.bind(this));
    },

    disableEventListeners: function () {
      this.$selector.off("submitOrder");
      this.$selector.off("submit");
    },

    submitOrder: function () {
      $('body').trigger('processStop');
      $('#payment_form_splitit_payment').find('#splitit-btn-pay').trigger('click');
    },

    error: function (message) {
      alert({
        content: message
      });
    },
  });
});
