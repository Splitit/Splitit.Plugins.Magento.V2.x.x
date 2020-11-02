# Splitit PaymentGateway Magento 2 Module


## Installation

To install, copy the codebase to app/code directory of your Magento website.
Run the following from your Magento root. This will install the Splitit SDK and related dependencies to support the module methods.
``` 
composer require splitit/module-payment-gateway"
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento static:content:deploy
```

## Configuration

Once the module is installed successfully, Splitit configuration would appear under 
**Stores > Configuration > Sales > Payment Methods**

Please see the Installation guide for details.
