<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Sdk\Paket\ParcelManagement\Api\CheckoutServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\CarrierService;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\IntervalOption;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\TimeFrameOption;

/**
 * Class CheckoutServiceStub
 */
class CheckoutServiceStub implements CheckoutServiceInterface
{
    public function getCarrierServices(
        string $recipientZip,
        \DateTime $startDate,
        array $headers = []
    ): array {
        return [
            new CarrierService(
                Codes::CHECKOUT_SERVICE_DROPOFF_DELIVERY,
                true
            ),
            new CarrierService(
                Codes::CHECKOUT_SERVICE_NEIGHBOR_DELIVERY,
                false
            ),
            new CarrierService(
                'inCarDelivery',
                false
            ),
            new CarrierService(
                'noNeighbourDelivery',
                true
            ),
            new CarrierService(
                Codes::CHECKOUT_SERVICE_PREFERRED_DAY,
                true,
                [
                    new IntervalOption(
                        '2019-12-20T00:00:00.000+02:00',
                        '2019-12-20T23:59:59.999+02:00'
                    ),
                    new IntervalOption(
                        '2019-12-21T00:00:00.000+02:00',
                        '2019-12-21T23:59:59.999+02:00'
                    ),
                    new IntervalOption(
                        '2019-12-22T00:00:00.000+02:00',
                        '2019-12-22T23:59:59.999+02:00'
                    ),

                ]
            )
        ];
    }
}
