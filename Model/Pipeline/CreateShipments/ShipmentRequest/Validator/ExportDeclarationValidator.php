<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;

class ExportDeclarationValidator implements RequestValidatorInterface
{
    private const CUSTOMS_VALUE_THRESHOLD = 1000;

    /**
     * Validates the shipment request by checking all included packages.
     *
     * @param Request $shipmentRequest The shipment request containing package data to validate.
     * @return void
     * @throws ValidatorException If any package validation fails.
     */
    #[\Override]
    public function validate(Request $shipmentRequest): void
    {
        $packages = $shipmentRequest->getData('packages');
        foreach ($packages as $package) {
            $this->validatePackage($package);
        }
    }

    /**
     * Validates the package by checking customs value and export notification requirements.
     *
     * @param array $package The package data, including customs information.
     * @return void
     * @throws ValidatorException If the customs value exceeds the threshold and the export notification is not provided.
     */
    private function validatePackage(array $package): void
    {
        //no customs inputs - no need to validate
        if (empty($package['params']['customs'])) {
            return;
        }

        $customsValue = (float) ($package['params']['customs_value'] ?? 0);
        $exportNotification = $package['params']['customs']['electronicExportNotification'] ?? false;

        if ($customsValue >= self::CUSTOMS_VALUE_THRESHOLD && !$exportNotification) {
            throw new ValidatorException(
                __('Export Notification is required for customs value > 1000. Please see the Tooltip for more information.')
            );
        }
    }
}
