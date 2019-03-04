<?php
/**
 * See LICENSE for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\ShippingProduct;

/**
 * Shipment products.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://choosealicense.com/licenses/mit/ The MIT License
 * @link    https://www.netresearch.de/
 */
class ShippingProducts implements ShippingProductsInterface
{
    /**
     * @inheritDoc
     */
    public function getAllCodes(): array
    {
        $codes = [];

        /** @var array $route */
        foreach (self::ORIGIN_DEST_CODES as $route) {
            foreach ($route as $routeCodes) {
                $codes[] = $routeCodes;
            }
        }

        return array_unique(array_merge(...$codes));
    }

    /**
     * Obtain procedure number by product code.
     *
     * @param string $code The product code
     *
     * @return string
     */
    private function getProcedure(string $code): string
    {
        return self::PROCEDURES[$code] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function getProductName(string $productCode): string
    {
        return self::PRODUCT_NAMES[$productCode] ?? $productCode;
    }

    /**
     * @inheritDoc
     */
    public function getApplicableCodes(
        string $originCountryCode,
        string $destCountryCode = null,
        array $euCountries = []
    ): array {
        // No codes found for origin country, cannot ship with DHL at all
        if (!isset(self::ORIGIN_DEST_CODES[$originCountryCode])) {
            return [];
        }

        if ($destCountryCode === null) {
            return array_unique(
                array_merge(
                    ...array_values(self::ORIGIN_DEST_CODES[$originCountryCode])
                )
            );
        }

        $applicableCodes = self::ORIGIN_DEST_CODES[$originCountryCode];

        // Exact match
        if (isset($applicableCodes[$destCountryCode])) {
            return $applicableCodes[$destCountryCode];
        }

        if (isset($applicableCodes[self::REGION_EU])
            && \in_array($destCountryCode, $euCountries, true)
        ) {
            // Match by region EU
            return $applicableCodes[self::REGION_EU];
        }

        return $applicableCodes[self::REGION_INTERNATIONAL] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getApplicableProcedures(string $originCountryCode): array
    {
        $procedures = [];
        $products   = $this->getApplicableCodes($originCountryCode);

        foreach ($products as $code) {
            $procedures[] = $this->getProcedure($code);
        }

        return array_unique($procedures);
    }

    /**
     * @inheritDoc
     */
    public function getBillingNumber(string $productCode, string $ekp, array $participations): string
    {
        $procedure     = $this->getProcedure($productCode);
        $participation = $participations[$procedure] ?? '';

        return $ekp . $procedure . $participation;
    }
}
