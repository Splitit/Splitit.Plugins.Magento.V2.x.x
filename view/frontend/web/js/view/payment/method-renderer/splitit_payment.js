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
          if(self.consumerData.fullName) {
            window.SplititFF.updateDetails({consumerData: self.consumerData});
          }
        }
      });
      quote.billingAddress.subscribe(
          function(newAddress) {
            if (newAddress && window.SplititFF != undefined) {
              console.log('billing address changed');
              var billingAddress = self.prepareBillingAddress(newAddress);
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
              if(self.billingAddress.addressLine != billingAddress.addressLine ||
                  self.billingAddress.addressLine2 != billingAddress.addressLine2 ||
                  self.billingAddress.city != billingAddress.city ||
                  self.billingAddress.state != billingAddress.state ||
                  self.billingAddress.country != billingAddress.country ||
                  self.billingAddress.zip != billingAddress.zip) {
                window.SplititFF.updateDetails({billingAddress:billingAddress,consumerData:self.consumerData});
                self.billingAddress = billingAddress;
              }
            }
          }
      );
      return this;
    },
    initObservable: function () {
      this._super().observe(["transactionResult"]);
      return this;
    },

    prepareBillingAddress: function(addressData) {
      var addressLine = '';
      var addressLine2 = '';
      if (addressData.street != undefined) {
        addressLine = addressData.street[0];
        if(addressData.street.length > 1 && addressData.street[1]) {
          addressLine2 = addressData.street[1];
        }
      }
      var billingAddress = {
        addressLine: addressLine,
        addressLine2: addressLine2,
        city: null,
        state: null,
        country: null,
        zip: null
      };

      if(addressData.city != undefined) {
        billingAddress.city = addressData.city
      }

      if(addressData.region != undefined) {
        billingAddress.state = addressData.region
      }

      if(addressData.countryId != undefined) {
        billingAddress.country = addressData.countryId
      }

      if(addressData.postcode != undefined) {
        billingAddress.zip = addressData.postcode
      }

      return billingAddress;

    },

    getCode: function () {
      return "splitit_payment";
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
      if (quote.getTotals()().base_grand_total.toFixed(2) < minAmount) {
        return false;
      }
      return true;
    },

    placeOrderClick: function () {
      this.placeOrder('parent');
    },

    splititflexfieldsAfterRender: function () {
      var thisObj = this;
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
        paymentButton: {
          selector: '#splitit-btn-pay'
        }
      }).ready(function () {
        if (checkoutData.getSelectedPaymentMethod() === 'splitit_payment') {
          this.show();
        }
        var billingAddress = quote.billingAddress();
        thisObj.prepareBillingAddress(billingAddress);
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
