# Splitit PaymentGateway Magento 2 Module


## Installation

### From Github
To install, copy the codebase to app/code directory of your Magento website.
1. Place plugin code into magento directory. Path:
```
from 
https://github.com/Splitit/Splitit.Plugins.Magento.V2.x.x

to
%MAGENTO ROOT%/app/code/Splitit/PaymentGateway/
```
2. Run the following from your Magento root. This will install the Splitit SDK and related dependencies to support the module methods.
``` 
composer require splitit/sdk
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento static:content:deploy
```
_____
### From Magento Marketplace
To install splitit plugin from magento marketplace.
1. Make sure you have ordered plugin on magento marketplace https://marketplace.magento.com/splitit-module-payment-gateway.html
2. Run the following from your Magento root. This will install the Splitit plugin and related dependencies to support the module methods.
``` 
composer require splitit/module-payment-gateway
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento static:content:deploy
```

## Configuration

Once the module is installed successfully, Splitit configuration would appear under 
**Stores > Configuration > Sales > Payment Methods**

Please see the Installation guide for details.
