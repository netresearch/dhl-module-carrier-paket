<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\ShippingCore\Api\Data\ShipmentRequest\ShipperInterface;

/**
 * Class Shipper
 *
 * @package Dhl\Paket\Model
 */
class Shipper implements ShipperInterface
{
    /**
     * @var string
     */
    private $contactPersonName;

    /**
     * @var string
     */
    private $contactPersonFirstName;

    /**
     * @var string
     */
    private $contactPersonLastName;

    /**
     * @var string
     */
    private $contactCompanyName;

    /**
     * @var string
     */
    private $contactEmail;

    /**
     * @var string
     */
    private $contactPhoneNumber;

    /**
     * @var string[]
     */
    private $street;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $postalCode;

    /**
     * @var string
     */
    private $countryCode;

    /**
     * @var string
     */
    private $streetName;

    /**
     * @var string
     */
    private $streetNumber;

    /**
     * @var string
     */
    private $addressAddition;

    /**
     * Shipper constructor.
     * @param string $contactPersonName
     * @param string $contactPersonFirstName
     * @param string $contactPersonLastName
     * @param string $contactCompanyName
     * @param string $contactEmail
     * @param string $contactPhoneNumber
     * @param string[] $street
     * @param string $city
     * @param string $state
     * @param string $postalCode
     * @param string $countryCode
     * @param string $streetName
     * @param string $streetNumber
     * @param string $addressAddition
     */
    public function __construct(
        string $contactPersonName,
        string $contactPersonFirstName,
        string $contactPersonLastName,
        string $contactCompanyName,
        string $contactEmail,
        string $contactPhoneNumber,
        array $street,
        string $city,
        string $state,
        string $postalCode,
        string $countryCode,
        string $streetName,
        string $streetNumber,
        string $addressAddition
    ) {
        $this->contactPersonName = $contactPersonName;
        $this->contactPersonFirstName = $contactPersonFirstName;
        $this->contactPersonLastName = $contactPersonLastName;
        $this->contactCompanyName = $contactCompanyName;
        $this->contactEmail = $contactEmail;
        $this->contactPhoneNumber = $contactPhoneNumber;
        $this->street = $street;
        $this->city = $city;
        $this->state = $state;
        $this->postalCode = $postalCode;
        $this->countryCode = $countryCode;
        $this->streetName = $streetName;
        $this->streetNumber = $streetNumber;
        $this->addressAddition = $addressAddition;
    }

    /**
     * Obtain shipper full name.
     *
     * @return string
     */
    public function getContactPersonName(): string
    {
        return $this->contactPersonName;
    }

    /**
     * Obtain shipper first name.
     *
     * @return string
     */
    public function getContactPersonFirstName(): string
    {
        return $this->contactPersonFirstName;
    }

    /**
     * Obtain shipper last name.
     *
     * @return string
     */
    public function getContactPersonLastName(): string
    {
        return $this->contactPersonLastName;
    }

    /**
     * Obtain shipper company name.
     *
     * @return string
     */
    public function getContactCompanyName(): string
    {
        return $this->contactCompanyName;
    }

    /**
     * Obtain shipper email.
     *
     * @return string
     */
    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    /**
     * Obtain shipper phone number.
     *
     * @return string
     */
    public function getContactPhoneNumber(): string
    {
        return $this->contactPhoneNumber;
    }

    /**
     * Obtain shipper street (1-3 street parts).
     *
     * @return string[]
     */
    public function getStreet(): array
    {
        return $this->street;
    }

    /**
     * Obtain shipper city.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Obtain shipper company state or province.
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Obtain shipper postal code.
     *
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Obtain shipper country code.
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Obtain street name split from street.
     *
     * @return string
     */
    public function getStreetName(): string
    {
        return $this->streetName;
    }

    /**
     * Obtain street number split from street.
     *
     * @return string
     */
    public function getStreetNumber(): string
    {
        return $this->streetNumber;
    }

    /**
     * Obtain address addition.
     *
     * @return string
     */
    public function getAddressAddition(): string
    {
        return $this->addressAddition;
    }
}
