/*browser:true*/
/*global define*/
/*browser:true*/
/*global define*/
define([
  "jquery",
  "Magento_Checkout/js/view/payment/default",
  "Magento_Checkout/js/model/quote",
  "Magento_Ui/js/model/messageList",
  "mage/translate",
  "Magento_Checkout/js/checkout-data"
], function ($, Component, quote, messageList, $t, checkoutData) {
  "use strict";

  return Component.extend({
    defaults: {
      template: "Splitit_PaymentGateway/payment/form",
      transactionResult: "",
      additional_data: {},
      FF: null,
      consumerData: {fullName:'',email:'',phoneNumber:''},
      billingAddress: {},
      imports: {
        customerEmail: 'checkout.steps.shipping-step.shippingAddress.customer-email:email'
      }
    },

    initialize: function() {
      this._super();
      var self = this;
      this.observe(['customerEmail']);
      this.customerEmail.subscribe(function (email) {
        if(self.consumerData.email != email) {
          self.consumerData.email = email;
        }
      });
      quote.billingAddress.subscribe(
          function(newAddress) {
            if (newAddress && window.SplititFF != undefined) {
              console.log('billing address changed');
              var billingAddress = self.prepareCustomerAddress(newAddress);
              var email = '';
              if (quote.guestEmail) {
                email = quote.guestEmail;
              } else {
                email = window.checkoutConfig.customerData.email;
              }
              self.consumerData = {
                fullName: newAddress.firstname + ' ' +  newAddress.lastname,
                email: email,
                phoneNumber: newAddress.telephone
              };

            }
          }
      );
      quote.totals.subscribe(function() {
        self.updateCalculations(false);
      });
      this.lastAmount = parseFloat(quote.getTotals()().base_grand_total);
      return this;
    },
    updateAddress : function(billingAddress) {
      if(this.billingAddress.addressLine != billingAddress.addressLine ||
          this.billingAddress.addressLine2 != billingAddress.addressLine2 ||
          this.billingAddress.city != billingAddress.city ||
          this.billingAddress.state != billingAddress.state ||
          this.billingAddress.country != billingAddress.country ||
          this.billingAddress.zip != billingAddress.zip) {
        this.billingAddress = billingAddress;
      }
    },
    initObservable: function () {
      this._super().observe(["transactionResult"]);
      return this;
    },
    updateCalculations: function (forceUpdate) {
      if (!this.isActive() && !forceUpdate) {
        return;
      }
      var newAmount = quote.getTotals()().base_grand_total;
      if (this.lastAmount === newAmount) {
        return;
      }
      this.lastAmount = newAmount;

      var data = {
        amount: quote.getTotals()().base_grand_total.toFixed(2),
        numInstallments: ''
      };

      $.ajax({
        url: '/splititpaymentgateway/flexfields/totals',
        method: 'post',
        data: data,
        success: function (response) {
          if (typeof response == 'undefined' || typeof response.publicToken == 'undefined') {
            if (typeof reportExternalError != 'undefined') {
              reportExternalError('Public Token is not defined', response);
            } else {
              console.error('Public Token is not defined');
              console.error(response);
            }
          }
          if (typeof window.SplititFF === 'undefined') {
            return;
          }
          window.SplititFF.setPublicToken(response.publicToken);
          window.SplititFF.synchronizePlan();
        },
        error: function () {
        }
      });
    },
    prepareCustomerAddress: function(billingAddress) {
      var addressLine = '';
      var addressLine2 = '';
      if (typeof billingAddress.street != 'undefined') {
        addressLine = billingAddress.street[0];
        if(billingAddress.street.length > 1 && billingAddress.street[1]) {
          addressLine2 = billingAddress.street[1];
        }
      }
      var address = {
        addressLine: addressLine,
        addressLine2: addressLine2,
        city: null,
        state: null,
        country: null,
        zip: null
      };

      if(typeof billingAddress.city != 'undefined') {
        address.city = billingAddress.city;
      }

      if(typeof billingAddress.region != 'undefined') {
        address.state = billingAddress.region;
      }

      if(typeof billingAddress.countryId != 'undefined') {
        address.country = billingAddress.countryId;
      }

      if(typeof billingAddress.postcode != 'undefined') {
        address.zip = billingAddress.postcode;
      }
      this.updateAddress(address);
      return address;

    },

    getCode: function () {
      return 'splitit_payment';
    },

    getData: function () {
      var data = {
        'method': this.getCode(),
        'additional_data': this.additional_data
      };

      return data;
    },

    isAvailable: function () {
      var minAmount = window.checkoutConfig.payment.splitit_payment.threshold;
      try {
        if (quote.getTotals()().base_grand_total.toFixed(2) < minAmount) {
          return false;
        }
      } catch (e) {
        return false;
      }
      return true;
    },
    isActive: function () {
      return this.isChecked() === this.getCode();
    },
    placeOrderClick: function () {
      this.placeOrder('parent');
    },
    splititflexfieldsAfterRender: function () {
      var thisObj = this;

      var paymentButtonConfig = {
        selector: '#splitit-btn-pay'
      };
      if(checkoutConfig.payment.splitit_payment.osc) {
        paymentButtonConfig.onClick = function (buttonInstance, flexFieldsInstance) {
          flexFieldsInstance.updateDetails(
              {consumerData: thisObj.consumerData, billingAddress: thisObj.billingAddress},
              function () {
                flexFieldsInstance.checkout();
              });
        }
      }

      var flexFieldsInstance = Splitit.FlexFields.setup({
        container: '#splitit-card-data',
        fields: {
          cardholderName: {
            selector: '#splitit-card-holder-full-name'
          },
          number: {
            selector: '#splitit-card-number'
          },
          cvv: {
            selector: '#splitit-cvv'
          },
          expirationDate: {
            selector: '#splitit-expiration-date'
          }
        },
        installmentPicker: {
          selector: '#installment-picker'
        },
        termsConditions: {
          selector: '#splitit-terms-conditions'
        },
        errorBox: {
          selector: '#splitit-error-box'
        },
        paymentButton: paymentButtonConfig
      }).ready(function () {
        if (checkoutData.getSelectedPaymentMethod() === thisObj.getCode()) {
          this.show();
        }
        var billingAddress = quote.billingAddress();
        thisObj.prepareCustomerAddress(billingAddress);
        var email = '';
        if (quote.guestEmail) {
          email = quote.guestEmail;
        } else {
          email = window.checkoutConfig.customerData.email;
        }
        thisObj.consumerData = {
          email: email,
          phoneNumber: billingAddress.telephone
        };
        if(billingAddress.firstname) {
          thisObj.consumerData.fullName = billingAddress.firstname;
        }
        if(billingAddress.lastname) {
          thisObj.consumerData.fullName += ' ' +  billingAddress.lastname;
        }
        var splititFlexFields = this;
        $.ajax({
          url: '/splititpaymentgateway/flexfields/index',
          method: 'post',
          data: {
            amount: quote.getTotals()().base_grand_total.toFixed(2),
            numInstallments: '', //passing numInstallments blank as Splitit will process this.
            billingAddress: {
              AddressLine: thisObj.billingAddress.addressLine,
              AddressLine2: thisObj.billingAddress.addressLine2,
              City: thisObj.billingAddress.city,
              State: thisObj.billingAddress.state,
              Country: thisObj.billingAddress.country,
              Zip: thisObj.billingAddress.zip
            },
            consumerModel: {
              FullName: thisObj.consumerData.fullName,
              Email: thisObj.consumerData.email,
              PhoneNumber: thisObj.consumerData.phoneNumber
            }
          },
          success: function (data) {
            if (typeof data == 'undefined' || typeof data.publicToken == 'undefined') {
              // this error alert can be replaced to reportExternalError when this function will be released
              if (typeof reportExternalError != 'undefined') {
                reportExternalError('Public Token is not defined', data);
              } else {
                console.error('Public Token is not defined');
                console.error(data);
              }
            } else {
              splititFlexFields.setPublicToken(data.publicToken);
            }
          }
        });
      }).onSuccess(function (result) {
        var instNum = flexFieldsInstance.getSessionParams().planNumber;
        if (typeof result.secure3dRedirectUrl !== "undefined") { //if onSuccess is being directed after 3ds check
          var successMsg = true; //if 3ds, onSuccess method is only hit when 3ds is successful.
        } else {
          var successMsg = result.data.responseHeader.succeeded;
        }
        thisObj.additional_data["installmentPlanNum"] = instNum;
        thisObj.additional_data["succeeded"] = successMsg;
        if (successMsg) { //only call magento place order when success
          thisObj.placeOrderClick();
        }
      }).onError(function (err) {
        if (err !== "undefined" && err.length > 0 && err.showError) {
          var errMsg = err[0]['error'];
          thisObj.showError($t(errMsg + " Please try again!"));
        }

      }).on3dComplete(function (data) {
        /* This method is only triggered when 3ds is enabled.
         * On success it goes to onSucess and on Error goes to onError
         * Displaying error message/going for successful order are being handled in relevant methods.
         */
        if (data.isSuccess) {
          thisObj.additional_data["succeeded"] = true; //set succeeded true
        }
      });
      window.SplititFF = flexFieldsInstance;
    },

    selectPaymentMethodSplitit: function () {
      this.updateCalculations(true);
      if (!window.SplititFF._isFormVisible) {
        window.SplititFF.toggle();
      }
      return this.selectPaymentMethod();
    },

    showError: function (errorMessage) {
      messageList.addErrorMessage({
        message: errorMessage,
      });
    },
  });
});
