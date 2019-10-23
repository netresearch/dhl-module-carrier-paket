<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Util;

use Dhl\Paket\Util\ShippingProducts;
use Dhl\ShippingCore\Api\ConfigInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class ShippingProductsTest
 *
 * @package Dhl\Paket\Test
 * @link http://www.netresearch.de/
 */
class ShippingProductsTest extends TestCase
{
    /**
     * @var ShippingProducts
     */
    private static $shippingProducts;

    /**
     * @var string[]
     */
    private static $euCountries;

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass()
    {
        $objectManager = Bootstrap::getObjectManager();
        $config        = $objectManager->get(ConfigInterface::class);

        self::$shippingProducts = $objectManager->get(ShippingProducts::class);
        self::$euCountries      = $config->getEuCountries();
    }

    /**
     * @test
     */
    public function getProductName()
    {
        self::assertSame(
            'DHL Paket',
            self::$shippingProducts->getProductName(
                ShippingProducts::CODE_NATIONAL
            )
        );

        self::assertSame(
            'FAKE_PRODUCT_CODE',
            self::$shippingProducts->getProductName(
                'FAKE_PRODUCT_CODE'
            )
        );
    }

    /**
     * @test
     */
    public function getProcedure()
    {
        self::assertSame(
            ShippingProducts::PROCEDURE_NATIONAL,
            self::$shippingProducts->getProcedure(
                ShippingProducts::CODE_NATIONAL
            )
        );

        self::assertEmpty(
            self::$shippingProducts->getProcedure(
                'FAKE_PRODUCT_CODE'
            )
        );
    }

    /**
     * @return mixed[]
     */
    public function applicableProductsDataProvider(): array
    {
        return [
            ['DE', false],
            ['AT', true],
            ['ES', true],
            ['US', true],
            ['', true],
        ];
    }

    /**
     * @test
     *
     * @dataProvider applicableProductsDataProvider
     *
     * @param string $originCountryCode
     * @param bool   $shouldReturnEmptyProductList
     */
    public function getApplicableProducts(string $originCountryCode, bool $shouldReturnEmptyProductList)
    {
        $applicableProducts = self::$shippingProducts->getApplicableProducts(
            $originCountryCode
        );

        self::assertInternalType('array', $applicableProducts);
        self::assertThat(
            empty($applicableProducts),
            $shouldReturnEmptyProductList ? self::isTrue() : self::isFalse(),
            'Failed asserting that origin country <' . $originCountryCode . '> returns an '
            . ($shouldReturnEmptyProductList ? 'empty' : 'non empty') . ' product list'
        );
    }

    /**
     * @return mixed[]
     */
    public function shippingDataProvider(): array
    {
        return [
            // $originCountryCode, $destinationCountryCode, $destinationRegion
            ['DE', 'DE', 'DE'],
            ['DE', 'AT', 'EU'],
            ['DE', 'US', 'INTL'],
            ['ES', 'DE', 'EU'],
            ['US', 'ES', 'EU'],
            ['US', 'HK', 'INTL'],
            ['', '', 'INTL'],
        ];
    }

    /**
     * Verifies that "getShippingProducts" does not throws an exception due an invalid array access.
     *
     * @test
     *
     * @dataProvider shippingDataProvider
     *
     * @param string $originCountryCode
     * @param string $destinationCountryCode
     */
    public function getShippingProductsThrowsNoException(string $originCountryCode, string $destinationCountryCode)
    {
        try {
            self::$shippingProducts->getShippingProducts(
                $originCountryCode,
                $destinationCountryCode,
                self::$euCountries
            );
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @test
     *
     * @dataProvider shippingDataProvider
     *
     * @param string $originCountryCode
     * @param string $destinationCountryCode
     * @param string $destinationRegion
     */
    public function getShippingProductsReturnsArray(
        string $originCountryCode,
        string $destinationCountryCode,
        string $destinationRegion
    ) {
        $shippingProducts = self::$shippingProducts->getShippingProducts(
            $originCountryCode,
            $destinationCountryCode,
            self::$euCountries
        );

        self::assertInternalType('array', $shippingProducts);
        self::assertArrayHasKey($destinationRegion, $shippingProducts);
        self::assertInternalType('array', $shippingProducts[$destinationRegion]);
    }

    /**
     * @return mixed[]
     */
    public function applicableProceduresDataProvider(): array
    {
        return [
            // $originCountryCode, $shouldReturnEmptyProcedureList (true/false whether shipping to this country is possible or not)
            ['DE', false],
            ['AT', true],
            ['ES', true],
            ['US', true],
            ['', true],
        ];
    }

    /**
     * @test
     *
     * @dataProvider applicableProceduresDataProvider
     *
     * @param string $originCountryCode
     * @param string $shouldReturnEmptyProcedureList
     */
    public function getApplicableProcedures(string $originCountryCode, string $shouldReturnEmptyProcedureList)
    {
        $applicableProcedures = self::$shippingProducts->getApplicableProcedures(
            $originCountryCode
        );

        self::assertInternalType('array', $applicableProcedures);
        self::assertThat(
            empty($applicableProcedures),
            $shouldReturnEmptyProcedureList ? self::isTrue() : self::isFalse(),
            'Failed asserting that origin country <' . $originCountryCode . '> returns an '
            . ($shouldReturnEmptyProcedureList ? 'empty' : 'non empty') . ' procedure list'
        );
    }
}
