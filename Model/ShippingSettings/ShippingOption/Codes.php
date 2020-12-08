<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingSettings\ShippingOption;

/**
 * @api
 */
class Codes
{
    /**
     * Packaging services.
     */
    const PACKAGING_SERVICE_CHECK_OF_AGE = 'visualCheckOfAge';
    const PACKAGING_SERVICE_RETURN_SHIPMENT = 'returnShipment';
    const PACKAGING_SERVICE_INSURANCE = 'additionalInsurance';
    const PACKAGING_SERVICE_BULKY_GOODS = 'bulkyGoods';
    const PACKAGING_SERVICE_PARCEL_OUTLET_ROUTING = 'parcelOutletRouting';
    const PACKAGING_PRINT_ONLY_IF_CODEABLE = 'printOnlyIfCodeable';

    const PACKAGING_INPUT_PARCEL_OUTLET_ROUTING_NOTIFICATION_EMAIL = 'emailAddress';

    /**
     * Checkout services.
     */
    const CHECKOUT_PARCEL_ANNOUNCEMENT = 'parcelAnnouncement';
    const CHECKOUT_SERVICE_PREFERRED_DAY = 'preferredDay';
    const CHECKOUT_SERVICE_NEIGHBOR_DELIVERY = 'preferredNeighbour';
    const CHECKOUT_SERVICE_DROPOFF_DELIVERY = 'preferredLocation';
    const CHECKOUT_SERVICE_PARCELSHOP_FINDER = 'parcelshopFinder';
    const CHECKOUT_SERVICE_CASH_ON_DELIVERY = 'cashOnDelivery';

    const CHECKOUT_INPUT_CUSTOMER_POSTNUMBER = 'customerPostnumber';
}
