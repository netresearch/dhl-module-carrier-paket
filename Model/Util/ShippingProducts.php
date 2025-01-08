<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Util;

use Dhl\Paket\Model\Config\ModuleConfig;

/**
 * Shipping products definition
 *
 * Utility to access
 * - DHL Paket shipping product codes
 * - DHL Paket shipping product names
 * - DHL Paket shipping product procedures
 * - DHL Paket shipping product routes
 */
class ShippingProducts
{
    /**
     * Destination regions.
     */
    public const REGION_EU            = 'EU';
    public const REGION_INTERNATIONAL = 'INTL';

    /**
     * Destination country codes.
     */
    public const COUNTRY_CODE_GERMANY        = 'DE';
    public const COUNTRY_CODE_AUSTRIA        = 'AT';
    public const COUNTRY_CODE_BELGIUM        = 'BE';
    public const COUNTRY_CODE_BULGARIA       = 'BG';
    public const COUNTRY_CODE_CZECH_REPUBLIC = 'CZ';
    public const COUNTRY_CODE_DENMARK        = 'DK';
    public const COUNTRY_CODE_ESTONIA        = 'EE';
    public const COUNTRY_CODE_FINLAND        = 'FI';
    public const COUNTRY_CODE_FRANCE         = 'FR';
    public const COUNTRY_CODE_GREECE         = 'GR';
    public const COUNTRY_CODE_GREAT_BRITAIN  = 'GB';
    public const COUNTRY_CODE_HUNGARY        = 'HU';
    public const COUNTRY_CODE_IRELAND        = 'IR';
    public const COUNTRY_CODE_ITALY          = 'IT';
    public const COUNTRY_CODE_LATVIA         = 'LV';
    public const COUNTRY_CODE_LITHUANIA      = 'LT';
    public const COUNTRY_CODE_LUXEMBURG      = 'LU';
    public const COUNTRY_CODE_NETHERLANDS    = 'NL';
    public const COUNTRY_CODE_NORWAY         = 'NO';
    public const COUNTRY_CODE_POLAND         = 'PL';
    public const COUNTRY_CODE_PORTUGAL       = 'PT';
    public const COUNTRY_CODE_ROMANIA        = 'RO';
    public const COUNTRY_CODE_SLOVAKIA       = 'SK';
    public const COUNTRY_CODE_SPAIN          = 'ES';
    public const COUNTRY_CODE_SWEDEN         = 'SE';
    public const COUNTRY_CODE_SWITZERLAND    = 'CH';

    /**
     * Product codes.
     */
    public const CODE_NATIONAL                = 'V01PAK';
    public const CODE_KLEINPAKET              = 'V62KP';
    public const CODE_NATIONAL_PRIO           = 'V01PRIO';
    public const CODE_NATIONAL_TAGGLEICH      = 'V06PAK';
    public const CODE_INTERNATIONAL           = 'V53WPAK';
    public const CODE_WARENPOST_INTERNATIONAL = 'V66WPI';
    public const CODE_EUROPAKET               = 'V54EPAK';
    public const CODE_TAGGLEICH               = 'V06PAK';
    public const CODE_KURIER_TAGGLEICH        = 'V06TG';
    public const CODE_KURIER_WUNSCHZEIT       = 'V06WZ';

    /**
     * Procedure codes.
     */
    public const PROCEDURE_NATIONAL                = '01';
    public const PROCEDURE_KLEINPAKET              = '62';
    public const PROCEDURE_NATIONAL_PRIO           = '01';
    public const PROCEDURE_NATIONAL_TAGGLEICH      = '06';
    public const PROCEDURE_INTERNATIONAL           = '53';
    public const PROCEDURE_WARENPOST_INTERNATIONAL = '66';
    public const PROCEDURE_EUROPAKET               = '54';
    public const PROCEDURE_KURIER_TAGGLEICH        = '01';
    public const PROCEDURE_KURIER_WUNSCHZEIT       = '01';
    public const PROCEDURE_RETURNSHIPMENT_NATIONAL = '07';

    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(ModuleConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Obtain all origin-destination-products combinations.
     *
     * @return string[][][]
     */
    private function getProducts(): array
    {
        return [
            'DE' => [
                self::COUNTRY_CODE_GERMANY => [
                    self::CODE_NATIONAL,
                    self::CODE_KLEINPAKET
                ],
                self::REGION_EU => [
                    self::CODE_INTERNATIONAL,
                    self::CODE_WARENPOST_INTERNATIONAL,
                    self::CODE_EUROPAKET,
                ],
                self::REGION_INTERNATIONAL => [
                    self::CODE_INTERNATIONAL,
                    self::CODE_WARENPOST_INTERNATIONAL,
                ],
            ],
        ];
    }

    /**
     * Obtain all product-procedure combinations.
     *
     * @return string[]
     */
    private function getProcedures(): array
    {
        return [
            self::CODE_NATIONAL => self::PROCEDURE_NATIONAL,
            self::CODE_KLEINPAKET => self::PROCEDURE_KLEINPAKET,
            self::CODE_NATIONAL_PRIO => self::PROCEDURE_NATIONAL_PRIO,
            self::CODE_NATIONAL_TAGGLEICH => self::PROCEDURE_NATIONAL_TAGGLEICH,
            self::CODE_INTERNATIONAL => self::PROCEDURE_INTERNATIONAL,
            self::CODE_WARENPOST_INTERNATIONAL => self::PROCEDURE_WARENPOST_INTERNATIONAL,
            self::CODE_EUROPAKET => self::PROCEDURE_EUROPAKET,
            self::CODE_KURIER_TAGGLEICH => self::PROCEDURE_KURIER_TAGGLEICH,
            self::CODE_KURIER_WUNSCHZEIT => self::PROCEDURE_KURIER_WUNSCHZEIT,
        ];
    }

    /**
     * Obtain all product-return-procedure combinations.
     *
     * @return string[]
     */
    private function getReturnProcedures(): array
    {
        return [
            self::CODE_NATIONAL => self::PROCEDURE_RETURNSHIPMENT_NATIONAL,
            self::CODE_KLEINPAKET => self::PROCEDURE_RETURNSHIPMENT_NATIONAL
        ];
    }

    /**
     * Obtain human readable name for given product code.
     *
     * @param string $productCode
     *
     * @return string
     */
    public function getProductName(string $productCode): string
    {
        $names = [
            self::CODE_NATIONAL                => 'DHL Paket',
            self::CODE_KLEINPAKET              => 'DHL Kleinpaket',
            self::CODE_EUROPAKET               => 'DHL Europaket',
            self::CODE_TAGGLEICH               => 'DHL Paket Taggleich',
            self::CODE_KURIER_TAGGLEICH        => 'DHL Kurier Taggleich,',
            self::CODE_KURIER_WUNSCHZEIT       => 'DHL Kurier Wunschzeit',
            self::CODE_INTERNATIONAL           => 'DHL Paket International',
            self::CODE_WARENPOST_INTERNATIONAL => 'DHL Warenpost International',
        ];

        if (!isset($names[$productCode])) {
            return $productCode;
        }

        return $names[$productCode];
    }

    /**
     * Obtain procedure number by product code.
     *
     * @param string $productCode
     * @return string
     */
    public function getProcedure(string $productCode): string
    {
        $procedures = $this->getProcedures();
        if (!isset($procedures[$productCode])) {
            return '';
        }

        return $procedures[$productCode];
    }

    /**
     * Obtain return procedure number by product code.
     *
     * @param string $productCode
     * @return string
     */
    public function getReturnProcedure(string $productCode): string
    {
        $procedures = $this->getReturnProcedures();
        if (!isset($procedures[$productCode])) {
            return '';
        }

        return $procedures[$productCode];
    }

    /**
     * For every available destination region, obtain the default product.
     *
     * Fall back to first available product if no default is configured
     * for the shipping origin.
     *
     * @param string $originCountryCode
     * @return string[]
     */
    public function getDefaultProducts(string $originCountryCode): array
    {
        $products = $this->config->getDefaultProducts();
        if (array_key_exists($originCountryCode, $products)) {
            return $products[$originCountryCode];
        }

        $products = $this->getProducts();
        if (array_key_exists($originCountryCode, $products)) {
            return array_map('array_shift', $products[$originCountryCode]);
        }

        return [];
    }

    /**
     * Get shipping product codes for given shipping origin.
     *
     * Returns an array of [$destination => $codes]. Destinations may be identified by country code, "EU" or "INTL".
     *
     * @param string $originCountryCode
     * @return string[][]
     */
    public function getApplicableProducts(string $originCountryCode): array
    {
        $products = $this->getProducts();
        if (array_key_exists($originCountryCode, $products)) {
            return $products[$originCountryCode];
        }

        return [];
    }

    /**
     * Get shipping product codes for given shipping origin and destination.
     *
     * Returns an array of [$destination => $codes]. Destinations may be identified by country code, "EU" or "INTL".
     *
     * @param string $originCountryCode
     * @param string $destinationCountryCode
     * @param string[] $euCountries
     * @return string[][]
     */
    public function getShippingProducts(
        string $originCountryCode,
        string $destinationCountryCode,
        array $euCountries
    ): array {
        // load product codes applicable to the given origin
        $applicableProducts = $this->getApplicableProducts($originCountryCode);

        // reduce to product codes applicable to the given destination
        if (isset($applicableProducts[$destinationCountryCode])) {
            $destinationRegion = $destinationCountryCode;
        } elseif (in_array($destinationCountryCode, $euCountries, true)) {
            $destinationRegion = self::REGION_EU;
        } else {
            $destinationRegion = self::REGION_INTERNATIONAL;
        }

        return [$destinationRegion => $applicableProducts[$destinationRegion] ?? []];
    }

    /**
     * Get procedures for given shipping origin.
     *
     * @param string $originCountryCode
     * @return string[]
     */
    public function getApplicableProcedures(string $originCountryCode): array
    {
        $productCodes = array_reduce(
            $this->getApplicableProducts($originCountryCode),
            static function (array $allProducts, $regionProducts) {
                // add keys for index access
                $regionProducts = array_combine($regionProducts, $regionProducts);
                $allProducts = array_merge($allProducts, $regionProducts);
                return $allProducts;
            },
            []
        );

        return array_merge(
            array_values(array_intersect_key($this->getProcedures(), $productCodes)),
            array_values(array_intersect_key($this->getReturnProcedures(), $productCodes))
        );
    }
}
