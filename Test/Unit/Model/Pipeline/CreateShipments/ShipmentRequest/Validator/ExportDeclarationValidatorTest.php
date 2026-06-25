<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\Pipeline\CreateShipments\ShipmentRequest\Validator;

use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Validator\ExportDeclarationValidator;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;
use PHPUnit\Framework\TestCase;

/**
 * Per-package backstop for the export-notification threshold (the multi-package boundary).
 *
 * ExportNotificationInputsProcessor derives a single checkbox default from the whole-shipment customs
 * value, and the packaging popup applies that one default to every package the user creates. So when a
 * single popup is split into multiple packages, a sub-1000 package inherits the shipment-level default
 * rather than being re-evaluated per package - the documented limitation of the PHP-only fix.
 *
 * This validator is the server-side guarantee that makes that boundary safe: it checks EACH package on
 * its own and rejects any package whose customs value reaches the threshold without the export
 * notification flag, regardless of how the shipment was split.
 */
class ExportDeclarationValidatorTest extends TestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    private function customsPackage(float $customsValue, bool $exportNotification): array
    {
        return [
            'params' => [
                'customs_value' => $customsValue,
                'customs' => ['electronicExportNotification' => $exportNotification],
            ],
        ];
    }

    private function requestWithPackages(array ...$packages): Request
    {
        $request = new Request();
        $request->setData('packages', $packages);

        return $request;
    }

    /**
     * A shipment split into a sub-1000 package and an above-1000 package: the above-1000 package left
     * unchecked must be rejected, even though it inherited the same shipment-level default as the other.
     */
    public function testRejectsAboveThresholdPackageLeftUncheckedWhenSplit(): void
    {
        $request = $this->requestWithPackages(
            $this->customsPackage(250.0, false),
            $this->customsPackage(1250.0, false)
        );

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage('Export Notification is required for customs value > 1000.');

        (new ExportDeclarationValidator())->validate($request);
    }

    /**
     * Splitting is accepted when each package is compliant on its own: the sub-1000 package may stay
     * unchecked, and the above-1000 package carries the flag.
     */
    public function testAllowsSplitWhenEachPackageIsCompliant(): void
    {
        $request = $this->requestWithPackages(
            $this->customsPackage(250.0, false),
            $this->customsPackage(1250.0, true)
        );

        (new ExportDeclarationValidator())->validate($request);

        $this->addToAssertionCount(1); // reaching here means every package validated successfully
    }

    /**
     * Packages without customs data (e.g. domestic parcels) are not subject to the export-notification
     * threshold and are skipped regardless of value.
     */
    public function testSkipsPackagesWithoutCustomsData(): void
    {
        $request = $this->requestWithPackages(
            ['params' => ['customs_value' => 2000.0, 'customs' => []]]
        );

        (new ExportDeclarationValidator())->validate($request);

        $this->addToAssertionCount(1);
    }
}
