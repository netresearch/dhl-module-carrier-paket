<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShipmentRequest;

use Dhl\ShippingCore\Api\Data\ShipmentRequest\RecipientInterface;

/**
 * Class Recipient
 *
 * @package Dhl\ShippingCore\Model
 */
class Recipient implements RecipientInterface
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
    private $regionCode;

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
     * Recipient constructor.
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
     * @param string $regionCode
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
        string $regionCode,
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
        $this->regionCode = $regionCode;
        $this->streetName = $streetName;
        $this->streetNumber = $streetNumber;
        $this->addressAddition = $addressAddition;
    }

    /**
     * Obtain recipient full name.
     *
     * @return string
     */
    public function getContactPersonName(): string
    {
        return $this->contactPersonName;
    }

    /**
     * Obtain recipient first name.
     *
     * @return string
     */
    public function getContactPersonFirstName(): string
    {
        return $this->contactPersonFirstName;
    }

    /**
     * Obtain recipient last name.
     *
     * @return string
     */
    public function getContactPersonLastName(): string
    {
        return $this->contactPersonLastName;
    }

    /**
     * Obtain recipient email.
     *
     * @return string
     */
    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    /**
     * Obtain recipient company name.
     *
     * @return string
     */
    public function getContactCompanyName(): string
    {
        return $this->contactCompanyName;
    }

    /**
     * Obtain recipient phone number.
     *
     * @return string
     */
    public function getContactPhoneNumber(): string
    {
        return $this->contactPhoneNumber;
    }

    /**
     * Obtain recipient street (1-3 street parts).
     *
     * @return string[]
     */
    public function getStreet(): array
    {
        return $this->street;
    }

    /**
     * Obtain recipient city.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Obtain recipient company state or province.
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Obtain recipient postal code.
     *
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Obtain recipient country code.
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Obtain recipient region code.
     *
     * @return string
     */
    public function getRegionCode(): string
    {
        return $this->regionCode;
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
