DHL Paket Shipping Carrier Extension
====================================

The DHL Paket extension for Magento® 2 integrates the _DHL Business Customer Shipping_
API into the order processing workflow.

Description
-----------
This extension enables merchants to request and cancel shipping labels for incoming orders
via the [DHL Business Customer Shipping API](https://entwickler.dhl.de/en/) (DHL Geschäftskundenversand-API).

Requirements
------------
* PHP >= 7.1.3
* PHP SOAP extension

Compatibility
-------------
* Magento >= 2.3.0+

Installation Instructions
-------------------------

Install sources:

    composer require dhl/module-carrier-paket

Enable module:

    ./bin/magento module:enable Dhl_Paket
    ./bin/magento setup:upgrade

Flush cache and compile:

    ./bin/magento cache:flush
    ./bin/magento setup:di:compile

Uninstallation
--------------

To unregister the carrier module from the application, run the following command:

    ./bin/magento module:uninstall --remove-data Dhl_Paket
    composer update

This will automatically remove source files, clean up the database, update package dependencies.

Support
-------
In case of questions or problems, please have a look at the
[Support Portal (FAQ)](http://dhl.support.netresearch.de/) first.

If the issue cannot be resolved, you can contact the support team via the
[Support Portal](http://dhl.support.netresearch.de/) or by sending an email
to <dhl.support@netresearch.de>.

License
-------
[OSL - Open Software Licence 3.0](http://opensource.org/licenses/osl-3.0.php)

Copyright
---------
(c) 2021 DHL Paket GmbH
