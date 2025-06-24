<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Util;

use Dhl\Paket\Model\Util\ShippingProducts;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use PHPUnit\Framework\TestCase;

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
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        self::$shippingProducts = Bootstrap::getObjectManager()->get(ShippingProducts::class);
        self::$euCountries = Bootstrap::getObjectManager()->get(ShippingConfigInterface::class)->getEuCountries();
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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
    public static function applicableProductsDataProvider(): array
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
     *
     *
     * @param string $originCountryCode
     * @param bool   $shouldReturnEmptyProductList
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('applicableProductsDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getApplicableProducts(string $originCountryCode, bool $shouldReturnEmptyProductList)
    {
        $applicableProducts = self::$shippingProducts->getApplicableProducts(
            $originCountryCode
        );

        self::assertTrue(\is_array($applicableProducts));
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
    public static function shippingDataProvider(): array
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
     *
     *
     * @param string $originCountryCode
     * @param string $destinationCountryCode
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('shippingDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
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
     *
     *
     * @param string $originCountryCode
     * @param string $destinationCountryCode
     * @param string $destinationRegion
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('shippingDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
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

        self::assertTrue(\is_array($shippingProducts));
        self::assertArrayHasKey($destinationRegion, $shippingProducts);
        self::assertTrue(\is_array($shippingProducts[$destinationRegion]));
    }

    /**
     * @return mixed[]
     */
    public static function applicableProceduresDataProvider(): array
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
     *
     *
     * @param string $originCountryCode
     * @param bool $shouldReturnEmptyProcedureList
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('applicableProceduresDataProvider')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function getApplicableProcedures(string $originCountryCode, bool $shouldReturnEmptyProcedureList)
    {
        $applicableProcedures = self::$shippingProducts->getApplicableProcedures(
            $originCountryCode
        );

        self::assertTrue(\is_array($applicableProcedures));
        self::assertThat(
            empty($applicableProcedures),
            $shouldReturnEmptyProcedureList ? self::isTrue() : self::isFalse(),
            'Failed asserting that origin country <' . $originCountryCode . '> returns an '
            . ($shouldReturnEmptyProcedureList ? 'empty' : 'non empty') . ' procedure list'
        );
    }
}
