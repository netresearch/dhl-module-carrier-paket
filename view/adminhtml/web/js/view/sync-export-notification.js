/**
 * See LICENSE.md for license details.
 */

/**
 * Keep the per-package "Electronic Export Notification" checkbox in sync with that package's
 * customs value: checked once the packed customs value reaches the 1000 EUR threshold, unchecked
 * below it. This refines the PHP per-shipment default (DHLGW-1550) so that a single shipment split
 * into multiple packages evaluates each package on its own contents.
 *
 * The checkbox stops auto-syncing as soon as the user toggles it themselves, so a deliberate manual
 * choice (e.g. a pre-declared ATLAS / IAA PLUS shipment under 1000 EUR) is never overwritten.
 */
define([
    'uiRegistry'
], function (registry) {
    'use strict';

    var THRESHOLD = 1000;
    var NOTIFICATION_INPUT = 'electronicExportNotification';
    var CUSTOMS_VALUE_INPUT = 'customsValue';

    return function (ShippingOptionInput) {
        return ShippingOptionInput.extend({
            /**
             * Wire the checkbox to its sibling customsValue once both components exist. Runs per
             * package, because the popup rebuilds these components on every package reset.
             */
            initialize: function () {
                this._super();

                if (this.inputCode !== NOTIFICATION_INPUT) {
                    return this;
                }

                var self = this;
                this.exportNotificationUserTouched = false;
                this.exportNotificationLastSynced = null;

                // A value that differs from the one we last set means the user toggled the box.
                this.value.subscribe(function (newValue) {
                    if (!self.exportNotificationUserTouched
                        && self.exportNotificationLastSynced !== null
                        && newValue !== self.exportNotificationLastSynced
                    ) {
                        self.exportNotificationUserTouched = true;
                    }
                });

                registry.get(
                    {inputCode: CUSTOMS_VALUE_INPUT, shippingOptionCode: this.shippingOptionCode},
                    function (customsValue) {
                        var sync = function (customsValueAmount) {
                            if (self.exportNotificationUserTouched) {
                                return;
                            }

                            var checked = Number(customsValueAmount) >= THRESHOLD;
                            self.exportNotificationLastSynced = checked;

                            if (self.value() !== checked) {
                                self.value(checked);
                            }
                        };

                        // customsValue.value is rate-limited (50ms notifyWhenChangesStop), so the
                        // subscription fires after the combination-rule engine has recomputed it.
                        sync(customsValue.value());
                        customsValue.value.subscribe(sync);
                    }
                );

                return this;
            }
        });
    };
});
