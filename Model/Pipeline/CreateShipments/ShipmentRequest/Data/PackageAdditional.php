<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data;

use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageAdditionalInterface;

class PackageAdditional implements PackageAdditionalInterface
{
    /**
     * @var string
     */
    private $termsOfTrade;

    /**
     * @var float
     */
    private $customsFees;

    /**
     * @var string
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
     * @var string
     */
    private $sendersCustomsReference;

    /**
     * @var string
     */
    private $addresseesCustomsReference;

    public function __construct(
        string $termsOfTrade = '',
        float $customsFees = 0,
        string $placeOfCommittal = '',
        string $permitNumber = '',
        string $attestationNumber = '',
        bool $electronicExportNotification = false,
        string $sendersCustomsReference = '',
        string $addresseesCustomsReference = ''
    ) {
        $this->termsOfTrade = $termsOfTrade;
        $this->customsFees = $customsFees;
        $this->placeOfCommittal = $placeOfCommittal;
        $this->permitNumber = $permitNumber;
        $this->attestationNumber = $attestationNumber;
        $this->electronicExportNotification = $electronicExportNotification;
        $this->sendersCustomsReference = $sendersCustomsReference;
        $this->addresseesCustomsReference = $addresseesCustomsReference;
    }

    /**
     * Obtain customs terms of trade (optional).
     *
     * @return string
     */
    public function getTermsOfTrade(): string
    {
        return $this->termsOfTrade;
    }

    /**
     * Obtain customs additional fee (optional).
     *
     * @return float
     */
    public function getCustomsFees(): float
    {
        return $this->customsFees;
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
     * Obtain customs sender's customer reference (optional).
     *
     * @return string
     */
    public function getSendersCustomsReference(): string
    {
        return $this->sendersCustomsReference;
    }

    /**
     * Obtain addressee's sender's customer reference (optional).
     *
     * @return string
     */
    public function getAddresseesCustomsReference(): string
    {
        return $this->addresseesCustomsReference;
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
