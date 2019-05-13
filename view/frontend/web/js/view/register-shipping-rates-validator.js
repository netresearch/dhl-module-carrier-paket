/**
 * See LICENSE.md for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Dhl_Paket/js/model/shipping-rates-validator',
        'Dhl_Paket/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';

        defaultShippingRatesValidator.registerValidator('dhlpaket', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('dhlpaket', shippingRatesValidationRules);

        return Component;
    }
);
