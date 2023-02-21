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
    // package customs
    public const PACKAGE_INPUT_TERMS_OF_TRADE = 'termsOfTrade';

    // packaging services
    public const SERVICE_OPTION_CHECK_OF_AGE = 'visualCheckOfAge';
    public const SERVICE_OPTION_NAMED_PERSON_ONLY= 'namedPersonOnly';
    public const SERVICE_OPTION_RETURN_SHIPMENT = 'returnShipment';
    public const SERVICE_OPTION_ENDORSEMENT = 'endorsement';
    public const SERVICE_OPTION_INSURANCE = 'additionalInsurance';
    public const SERVICE_OPTION_BULKY_GOODS = 'bulkyGoods';
    public const SERVICE_OPTION_PRINT_ONLY_IF_CODEABLE = 'printOnlyIfCodeable';

    public const SERVICE_OPTION_PARCEL_OUTLET_ROUTING = 'parcelOutletRouting';
    public const SERVICE_INPUT_PARCEL_OUTLET_ROUTING_NOTIFICATION_EMAIL = 'emailAddress';

    // checkout services
    public const SERVICE_OPTION_PARCEL_ANNOUNCEMENT = 'parcelAnnouncement';
    public const SERVICE_OPTION_PREFERRED_DAY = 'preferredDay';
    public const SERVICE_OPTION_NEIGHBOR_DELIVERY = 'preferredNeighbour';
    public const SERVICE_OPTION_DROPOFF_DELIVERY = 'preferredLocation';
    public const SERVICE_OPTION_PREMIUM = 'premium';
    public const SERVICE_OPTION_NO_NEIGHBOR_DELIVERY = 'noNeighbourDelivery';

    public const SERVICE_INPUT_DELIVERY_LOCATION_ACCOUNT_NUMBER = 'customerPostnumber';
}
