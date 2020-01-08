<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\ShippingCore\Api\Data\ShipmentRequest\PackageAdditionalInterface;

/**
 * Class PackageAdditional
 *
 */
class PackageAdditional implements PackageAdditionalInterface
{
    /**
     * @var float
     */
    private $additionalFee;

    /**
     * @var
     */
    private $placeOfCommittal;

    /**
     * @var string
     */
    private $permitNumber;

    /**
     * @var string
     */
    private $attestationNumber;

    /**
     * @var bool
     */
    private $electronicExportNotification;

    /**
     * PackageExtension constructor.
     *
     * @param float $additionalFee
     * @param string $placeOfCommittal
     * @param string $permitNumber
     * @param string $attestationNumber
     * @param bool $electronicExportNotification
     */
    public function __construct(
        float $additionalFee = 0,
        string $placeOfCommittal = '',
        string $permitNumber = '',
        string $attestationNumber = '',
        bool $electronicExportNotification = false
    ) {
        $this->additionalFee = $additionalFee;
        $this->placeOfCommittal = $placeOfCommittal;
        $this->permitNumber = $permitNumber;
        $this->attestationNumber = $attestationNumber;
        $this->electronicExportNotification = $electronicExportNotification;
    }

    /**
     * Obtain customs additional fee (optional).
     *
     * @return float
     */
    public function getAdditionalFee(): float
    {
        return $this->additionalFee;
    }

    /**
     * Obtain place of committal (optional).
     *
     * @return string
     */
    public function getPlaceOfCommittal(): string
    {
        return $this->placeOfCommittal;
    }

    /**
     * Obtain customs permit number (optional).
     *
     * @return string
     */
    public function getPermitNumber(): string
    {
        return $this->permitNumber;
    }

    /**
     * Obtain customs attestation number (optional).
     *
     * @return string
     */
    public function getAttestationNumber(): string
    {
        return $this->attestationNumber;
    }

    /**
     * Obtain customs electronic export notification (optional).
     *
     * @return bool
     */
    public function getElectronicExportNotification(): bool
    {
        return $this->electronicExportNotification;
    }

    /**
     * Obtain additional Paket carrier package properties.
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return get_object_vars($this);
    }
}
