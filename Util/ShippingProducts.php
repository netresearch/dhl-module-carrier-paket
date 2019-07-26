<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Util;

/**
 * ShippingProducts
 *
 * Utility to access
 * - DHL Paket shipping product codes
 * - DHL Paket shipping product names
 * - DHL Paket shipping product procedures
 * - DHL Paket shipping product routes
 *
 * @package Dhl\Shipping\Util
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ShippingProducts
{
    /**
     * Destination regions.
     */
    const REGION_EU            = 'EU';
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
     * Product codes.
     */
    const CODE_NATIONAL            = 'V01PAK';
    const CODE_NATIONAL_PRIO       = 'V01PRIO';
    const CODE_NATIONAL_TAGGLEICH  = 'V06PAK';
    const CODE_INTERNATIONAL       = 'V53WPAK';
    const CODE_EUROPAKET           = 'V54EPAK';
    const CODE_CONNECT             = 'V55PAK';
    const CODE_TAGGLEICH           = 'V06PAK';
    const CODE_KURIER_TAGGLEICH    = 'V06TG';
    const CODE_KURIER_WUNSCHZEIT   = 'V06WZ';
    const CODE_PAKET_AUSTRIA       = 'V86PARCEL';
    const CODE_PAKET_CONNECT       = 'V87PARCEL';
    const CODE_PAKET_INTERNATIONAL = 'V82PARCEL';

    /**
     * Procedure codes.
     */
    const PROCEDURE_NATIONAL                = '01';
    const PROCEDURE_NATIONAL_PRIO           = '01';
    const PROCEDURE_NATIONAL_TAGGLEICH      = '06';
    const PROCEDURE_INTERNATIONAL           = '53';
    const PROCEDURE_EUROPAKET               = '54';
    const PROCEDURE_CONNECT                 = '55';
    const PROCEDURE_KURIER_TAGGLEICH        = '01';
    const PROCEDURE_KURIER_WUNSCHZEIT       = '01';
    const PROCEDURE_PAKET_AUSTRIA           = '86';
    const PROCEDURE_PAKET_CONNECT           = '87';
    const PROCEDURE_PAKET_INTERNATIONAL     = '82';
    const PROCEDURE_RETURNSHIPMENT_NATIONAL = '07';
    const PROCEDURE_RETURNSHIPMENT_AUSTRIA  = '83';
    const PROCEDURE_RETURNSHIPMENT_CONNECT  = '85';

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
                ],
                self::REGION_EU            => [
                    self::CODE_INTERNATIONAL,
                ],
                self::REGION_INTERNATIONAL => [
                    self::CODE_INTERNATIONAL,
                ],
            ],
            'AT' => [
                self::COUNTRY_CODE_AUSTRIA        => [
                    self::CODE_PAKET_AUSTRIA,
                ],
                self::COUNTRY_CODE_GERMANY        => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_BELGIUM        => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_LUXEMBURG      => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_NETHERLANDS    => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_POLAND         => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_SLOVAKIA       => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::COUNTRY_CODE_CZECH_REPUBLIC => [
                    self::CODE_PAKET_CONNECT,
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::REGION_EU                   => [
                    self::CODE_PAKET_INTERNATIONAL,
                ],
                self::REGION_INTERNATIONAL        => [
                    self::CODE_PAKET_INTERNATIONAL,
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
            self::CODE_NATIONAL_PRIO => self::PROCEDURE_NATIONAL_PRIO,
            self::CODE_NATIONAL_TAGGLEICH => self::PROCEDURE_NATIONAL_TAGGLEICH,
            self::CODE_INTERNATIONAL => self::PROCEDURE_INTERNATIONAL,
            self::CODE_EUROPAKET => self::PROCEDURE_EUROPAKET,
            self::CODE_CONNECT => self::PROCEDURE_CONNECT,
            self::CODE_KURIER_TAGGLEICH => self::PROCEDURE_KURIER_TAGGLEICH,
            self::CODE_KURIER_WUNSCHZEIT => self::PROCEDURE_KURIER_WUNSCHZEIT,
            self::CODE_PAKET_AUSTRIA => self::PROCEDURE_PAKET_AUSTRIA,
            self::CODE_PAKET_CONNECT => self::PROCEDURE_PAKET_CONNECT,
            self::CODE_PAKET_INTERNATIONAL => self::PROCEDURE_PAKET_INTERNATIONAL,
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
            self::CODE_PAKET_AUSTRIA => self::PROCEDURE_RETURNSHIPMENT_AUSTRIA,
            self::CODE_PAKET_CONNECT => self::PROCEDURE_RETURNSHIPMENT_CONNECT,
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
            self::CODE_NATIONAL            => 'DHL Paket',
            self::CODE_EUROPAKET           => 'DHL Europaket',
            self::CODE_CONNECT             => 'DHL Paket Connect',
            self::CODE_TAGGLEICH           => 'DHL Paket Taggleich',
            self::CODE_KURIER_TAGGLEICH    => 'DHL Kurier Taggleich,',
            self::CODE_KURIER_WUNSCHZEIT   => 'DHL Kurier Wunschzeit',
            self::CODE_INTERNATIONAL       => 'DHL Paket International',
            self::CODE_PAKET_AUSTRIA       => 'DHL PAKET Austria',
            self::CODE_PAKET_CONNECT       => 'DHL PAKET Connect',
            self::CODE_PAKET_INTERNATIONAL => 'DHL PAKET International',
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
     * @return string[]
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
