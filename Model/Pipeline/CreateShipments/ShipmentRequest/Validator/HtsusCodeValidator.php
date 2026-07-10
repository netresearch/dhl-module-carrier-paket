<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Dhl\Paket\Model\Util\UsCustomsTerritory;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;

/**
 * Validate that all items shipped to the US customs territory carry a 10-digit HTSUS code.
 *
 * The US CBP requires a 10-digit, US-specific HTSUS code per goods item for postal shipments
 * to the USA and Puerto Rico. The DHL API accepts shorter codes without error, but such
 * shipments risk import rejection or return in the US — so label creation must be blocked here.
 */
class HtsusCodeValidator implements RequestValidatorInterface
{
    /**
     * Validates the shipment request by checking all included packages.
     *
     * @param Request $shipmentRequest The shipment request containing package data to validate.
     * @return void
     * @throws ValidatorException If any item lacks a 10-digit HTSUS code.
     */
    #[\Override]
    public function validate(Request $shipmentRequest): void
    {
        $countryCode = (string) $shipmentRequest->getData('recipient_address_country_code');
        if (!in_array($countryCode, UsCustomsTerritory::COUNTRY_CODES, true)) {
            return;
        }

        $packages = $shipmentRequest->getData('packages') ?? [];
        foreach ($packages as $package) {
            $this->validatePackage($package);
        }
    }

    /**
     * Validates all items of a single package.
     *
     * @param array $package The package data, including customs information.
     * @return void
     * @throws ValidatorException If an item's HTSUS code is not exactly 10 digits.
     */
    private function validatePackage(array $package): void
    {
        //no customs inputs - no need to validate
        if (empty($package['params']['customs'])) {
            return;
        }

        foreach ($package['items'] ?? [] as $item) {
            $hsCode = (string) ($item['customs']['hsCode'] ?? '');
            if (!preg_match('/^\d{10}$/', $hsCode)) {
                throw new ValidatorException(
                    __('Shipments to the USA and Puerto Rico require a 10-digit HTSUS customs tariff number for every item.')
                );
            }
        }
    }
}
