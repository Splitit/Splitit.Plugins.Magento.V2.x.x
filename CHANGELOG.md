## Changelog

All notable changes to this project will be documented in this file
-

### 2.3.0

* Compatibility with php7.0 fix
* Fix validation on admin order creation page

### 2.2.0

* environmental js & css urls adjusted to address the appropriate Splitit environment.
* “hosted” was changed to “FlexFields” in environment urls
* Update Magento/FF JS code to use FF.toggle() function
* FF related code on Magento has been updated to the latest FF implementation.
* Added client side functions to catch various errors.
* The Splitit payment method radio button is unchecked even though the payment method is selected.
* Security errors in console while loading js/css files from remote server
* A broken Splitit image is shown during checkout page loading
* The checkout (session) is intact but the Flex fields iframes are not loaded properly
* FF iframes disappear after refreshing (F5 from keyboard) the checkout page
* Error in Splitit_PaymentGateway/js/can_display_content.js ‘firstChild’ property is undefined
* Fix for Compatibility issue in file : /eqp-automation/temp/splitit/paymentmethod/splitit-paymentmethod-0.2.1/Model/Ui/ConfigProvider.php
* Addition of a json helper to support Magento 2.0 & 2.1 versions for json serialization
* Compatibility with BrainTree plugin
* Async event logic to cover for when redirection to success page fails
* Compatibility with Magento versions 2.0.0-2.0.5
* Creation of credit memo (refund) bug fix
---

### 2.1.0

* Environmental js & css urls adjusted to address the appropriate Splitit environment.
* “Hosted” was changed to “FlexFields” in environment urls
* Update Magento/FF JS code to use FF.toggle() function
---
