/**
 * See LICENSE.md for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'mageUtils',
        'Dhl_Paket/js/model/shipping-rates-validation-rules',
        'mage/translate'
    ],
    function ($, utils, validationRules, $t) {
        'use strict';

        return {
            validationErrors: [],

            validate: function (address) {
                var self = this;

                this.validationErrors = [];
                $.each(validationRules.getRules(), function (field, rule) {
                    if (rule.required && utils.isEmpty(address[field])) {
                        var message = $t('Field %1 is required.').replace('%1', field);

                        self.validationErrors.push(message);
                    }
                });

                return !Boolean(this.validationErrors.length);
            }
        };
    }
);
