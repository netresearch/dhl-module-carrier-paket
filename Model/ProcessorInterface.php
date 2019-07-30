<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model;

/**
 * Class ProcessorInterface.
 *
 * @package Dhl\Paket\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
interface ProcessorInterface
{
    /**
     * Packaging services.
     */
    const PACKAGING_SERVICE_CHECK_OF_AGE    = 'visualCheckOfAge';
    const PACKAGING_SERVICE_RETURN_SHIPMENT = 'returnShipment';
    const PACKAGING_SERVICE_INSURANCE       = 'additionalInsurance';
    const PACKAGING_SERVICE_BULKY_GOODS     = 'bulkyGoods';
    const PACKAGING_PRINT_ONLY_IF_CODEABLE  = 'printOnlyIfCodeable';

    /**
     * Checkout services.
     */
    const CHECKOUT_PARCEL_ANNOUNCEMENT         = 'parcelAnnouncement';
    const CHECKOUT_SERVICE_PREFERRED_DAY       = 'preferredDay';
    const CHECKOUT_SERVICE_PREFERRED_TIME      = 'preferredTime';
    const CHECKOUT_SERVICE_PREFERRED_NEIGHBOUR = 'preferredNeighbour';
    const CHECKOUT_SERVICE_PREFERRED_LOCATION  = 'preferredLocation';
    const CHECKOUT_DELIVERY_PARCELSTATION      = 'parcelstation';
}
