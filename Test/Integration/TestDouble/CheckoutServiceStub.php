<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble;


use Dhl\Sdk\Paket\ParcelManagement\Api\CheckoutServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Api\Data\CarrierServiceInterface;
use Dhl\Sdk\Paket\ParcelManagement\Exception\AuthenticationException;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ClientException;
use Dhl\Sdk\Paket\ParcelManagement\Exception\ServerException;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\CarrierService;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\IntervalOption;
use Dhl\Sdk\Paket\ParcelManagement\Service\CheckoutService\TimeFrameOption;

class CheckoutServiceStub implements CheckoutServiceInterface
{
    public function getCarrierServices(
        string $recipientZip,
        string $startDate,
        array $headers = []
    ): array {
        return [
            new CarrierService(
                'preferredLocation',
                true
            ),
            new CarrierService(
                'preferredNeighbour',
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
                'preferredDay',
                true,
                [
                    new IntervalOption(
                        '2019-12-20T00:00:00.000+02:00',
                        '2019-12-20T23:59:59.999+02:00'
                    ),
                    new IntervalOption(
                        '2019-13-20T00:00:00.000+02:00',
                        '2019-13-20T23:59:59.999+02:00'
                    ),
                    new IntervalOption(
                        '2019-14-20T00:00:00.000+02:00',
                        '2019-14-20T23:59:59.999+02:00'
                    ),

                ]
            ),
            new CarrierService(
                'preferredTime',
                true,
                [
                    new TimeFrameOption(
                        '10:00',
                        '12:00',
                        '001'
                    ),
                    new TimeFrameOption(
                        '12:00',
                        '14:00',
                        '002'
                    ),
                    new TimeFrameOption(
                        '14:00',
                        '16:00',
                        '003'
                    )
                ]
            )
        ];
    }
}
