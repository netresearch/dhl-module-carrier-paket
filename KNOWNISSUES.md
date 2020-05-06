# Known Issues

All notable known issues and bugs with this project will be documented in this file.

## (DHLGW-866) Configuration Setting Inheritance

### Symptom

Carrier title or unavailability message display wrong texts in checkout.

### Description

When saving the module configuration settings

* Checkout Presentation → Displayed Text
* Checkout Presentation → Custom Message

with the checkboxes "Use system value" or "Use Default" checked, then the
previous value remains configured with the checkbox unchecked. It is not
possible to reset the configured value to parent scope or module default.

The root cause is [Magento issue #26732](https://github.com/magento/magento2/issues/26732). 

### Workaround

To work around this issue, delete those `core_config_data` entries for
`carriers/dhlpaket/title` and `carriers/dhlpaket/specificerrmsg` which
should be replaced by default scope / system value and clear the cache.

## (DHLGW-808) NULL Values in Countries Configuration 

### Symptom

The carrier configuration "Allow for Specific Countries" is not correctly applied in checkout.

### Description

When saving the module configuration in website scope, then the countries
configuration field persists a NULL value. The issue occurs even if no changes
were made to the configuration. The effect is that the setting from global scope
is ignored.

### Workaround

To resolve this issue, delete all `core_config_data` entries with `NULL` values
for `carriers/dhlpaket/sallowspecific` and `carriers/dhlpaket/specificcountry`
in website scope and clear the cache.
