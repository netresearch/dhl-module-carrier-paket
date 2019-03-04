<?php
/**
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingProduct;

/**
 * Shipment products interface.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://choosealicense.com/licenses/mit/ The MIT License
 * @link    https://www.netresearch.de/
 */
interface ShippingProductsInterface
{
    /**
     * Product codes.
     */
    const CODE_NATIONAL            = 'V01PAK';
    const CODE_NATIONAL_PRIO       = 'V01PRIO';
    const CODE_NATIONAL_TAGGLEICH  = 'V06PAK';
    const CODE_INTERNATIONAL       = 'V53WPAK';
    const CODE_EUROPAKET           = 'V54EPAK';
    const CODE_CONNECT             = 'V55PAK';
    const CODE_KURIER_TAGGLEICH    = 'V06TG';
    const CODE_KURIER_WUNSCHZEIT   = 'V06WZ';
    const CODE_PAKET_AUSTRIA       = 'V86PARCEL';
    const CODE_PAKET_CONNECT       = 'V87PARCEL';
    const CODE_PAKET_INTERNATIONAL = 'V82PARCEL';

    /**
     * Procedure codes.
     */
    const PROCEDURE_NATIONAL            = '01';
    const PROCEDURE_NATIONAL_PRIO       = '01';
    const PROCEDURE_NATIONAL_TAGGLEICH  = '06';
    const PROCEDURE_INTERNATIONAL       = '53';
    const PROCEDURE_EUROPAKET           = '54';
    const PROCEDURE_CONNECT             = '55';
    const PROCEDURE_KURIER_TAGGLEICH    = '01';
    const PROCEDURE_KURIER_WUNSCHZEIT   = '01';
    const PROCEDURE_PAKET_AUSTRIA       = '86';
    const PROCEDURE_PAKET_CONNECT       = '87';
    const PROCEDURE_PAKET_INTERNATIONAL = '82';

    /**
     * Dstination regions.
     */
    const REGION_EU            = 'EURO';
    const REGION_AMERICA       = 'AMER';
    const REGION_ASIA_PACIFIC  = 'APAC';
    const REGION_INTERNATIONAL = 'INTL';

    /**
     * Destination country codes.
     */
    const COUNTRY_CODE_GERMANY        = 'DE';
    const COUNTRY_CODE_AUSTRIA        = 'AT';
    const COUNTRY_CODE_BELGIUM        = 'BE';
    const COUNTRY_CODE_LUXEMBURG      = 'LU';
    const COUNTRY_CODE_NETHERLANDS    = 'NL';
    const COUNTRY_CODE_POLAND         = 'PL';
    const COUNTRY_CODE_SLOVAKIA       = 'SK';
    const COUNTRY_CODE_CZECH_REPUBLIC = 'CZ';

    /**
     * All available origin-destination-product combinations.
     *
     * @var string[][][]
     */
    const ORIGIN_DEST_CODES = [
        self::COUNTRY_CODE_GERMANY => [
            self::COUNTRY_CODE_GERMANY => [
                self::CODE_NATIONAL,
            ],
            self::REGION_EU => [
                self::CODE_INTERNATIONAL,
            ],
            self::REGION_INTERNATIONAL => [
                self::CODE_INTERNATIONAL,
            ],
        ],
        self::COUNTRY_CODE_AUSTRIA => [
            self::COUNTRY_CODE_AUSTRIA => [
                self::CODE_PAKET_AUSTRIA,
            ],
            self::COUNTRY_CODE_GERMANY => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_BELGIUM => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_LUXEMBURG => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_NETHERLANDS => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_POLAND => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_SLOVAKIA => [
                self::CODE_PAKET_CONNECT,
            ],
            self::COUNTRY_CODE_CZECH_REPUBLIC => [
                self::CODE_PAKET_CONNECT,
            ],
            self::REGION_EU => [
                self::CODE_PAKET_INTERNATIONAL,
            ],
            self::REGION_INTERNATIONAL => [
                self::CODE_PAKET_INTERNATIONAL,
            ],
        ],
    ];

    /**
     * List of products with their corresponding human readable names.
     *
     * @var string[]
     */
    const PRODUCT_NAMES = [
        self::CODE_NATIONAL            => 'DHL Paket',
        self::CODE_NATIONAL_PRIO       => 'DHL Paket Prio',
        self::CODE_NATIONAL_TAGGLEICH  => 'DHL Paket Taggleich',
        self::CODE_INTERNATIONAL       => 'DHL Paket International',
        self::CODE_EUROPAKET           => 'DHL Europaket',
        self::CODE_CONNECT             => 'DHL Paket Connect',
        self::CODE_KURIER_TAGGLEICH    => 'DHL Kurier Taggleich',
        self::CODE_KURIER_WUNSCHZEIT   => 'DHL Kurier Wunschzeit',
        self::CODE_PAKET_AUSTRIA       => 'DHL Paket Austria',
        self::CODE_PAKET_CONNECT       => 'DHL Paket Connect',
        self::CODE_PAKET_INTERNATIONAL => 'DHL Paket International',
    ];

    /**
     * List of products with their corresponding procedure number.
     *
     * @var string[]
     */
    const PROCEDURES = [
        self::CODE_NATIONAL            => self::PROCEDURE_NATIONAL,
        self::CODE_NATIONAL_PRIO       => self::PROCEDURE_NATIONAL_PRIO,
        self::CODE_NATIONAL_TAGGLEICH  => self::PROCEDURE_NATIONAL_TAGGLEICH,
        self::CODE_INTERNATIONAL       => self::PROCEDURE_INTERNATIONAL,
        self::CODE_EUROPAKET           => self::PROCEDURE_EUROPAKET,
        self::CODE_CONNECT             => self::PROCEDURE_CONNECT,
        self::CODE_KURIER_TAGGLEICH    => self::PROCEDURE_KURIER_TAGGLEICH,
        self::CODE_KURIER_WUNSCHZEIT   => self::PROCEDURE_KURIER_WUNSCHZEIT,
        self::CODE_PAKET_AUSTRIA       => self::PROCEDURE_PAKET_AUSTRIA,
        self::CODE_PAKET_CONNECT       => self::PROCEDURE_PAKET_CONNECT,
        self::CODE_PAKET_INTERNATIONAL => self::PROCEDURE_PAKET_INTERNATIONAL,
    ];

    /**
     * Returns a list of all supported shipping products.
     *
     * @return string[]
     */
    public function getAllCodes(): array;

    /**
     * Returns the human readable name for given product code.
     *
     * @param string $productCode The product code
     *
     * @return string
     */
    public function getProductName(string $productCode): string;

    /**
     * Find all shipping products that apply to the given shipping route.
     *
     * @param string      $originCountryCode
     * @param string|null $destCountryCode
     * @param string[]    $euCountries
     *
     * @return string[]
     */
    public function getApplicableCodes(
        string $originCountryCode,
        string $destCountryCode = null,
        array $euCountries = []
    ): array;

    /**
     * Returns all shipping procedures that apply to the given shipping origin.
     *
     * @param string $originCountryCode The origin country code
     *
     * @return string[]
     */
    public function getApplicableProcedures(string $originCountryCode): array;

    /**
     * Returns the billing number a.k.a. account number based on selected product.
     *
     * @param string   $productCode    The product code
     * @param string   $ekp            The uniform customer and product number
     * @param string[] $participations The list of assigned participation numbers
     *
     * @return string
     */
    public function getBillingNumber(string $productCode, string $ekp, array $participations): string;
}
