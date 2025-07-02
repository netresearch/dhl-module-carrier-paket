<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Config;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 */
class ModuleConfigTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|ObjectManager
     */
    private $objectManager;

    /**
     * @var ModuleConfig
     */
    private $config;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->config = $this->objectManager->create(ModuleConfig::class);
    }

    /**
     * Test GoGreen Plus enabled configuration returns true when enabled.
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 1
     */
    public function testGetGoGreenPlusEnabledReturnsTrue(): void
    {
        self::assertTrue($this->config->getGoGreenPlusEnabled());
    }

    /**
     * Test GoGreen Plus enabled configuration returns false when disabled.
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 0
     */
    public function testGetGoGreenPlusEnabledReturnsFalse(): void
    {
        self::assertFalse($this->config->getGoGreenPlusEnabled());
    }

    /**
     * Test GoGreen Plus additional charge returns correct float value.
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 15.50
     */
    public function testGetGoGreenPlusAdditionalChargeReturnsFloat(): void
    {
        self::assertSame(15.5, $this->config->getGoGreenPlusAdditionalCharge());
    }

    /**
     * Test GoGreen Plus additional charge handles comma decimal separator.
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 10,25
     */
    public function testGetGoGreenPlusAdditionalChargeHandlesCommaDecimal(): void
    {
        self::assertSame(10.25, $this->config->getGoGreenPlusAdditionalCharge());
    }

    /**
     * Test GoGreen Plus additional charge returns zero for empty configuration.
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge
     */
    public function testGetGoGreenPlusAdditionalChargeReturnsZeroForEmpty(): void
    {
        self::assertSame(0.0, $this->config->getGoGreenPlusAdditionalCharge());
    }

    /**
     * Test GoGreen Plus additional charge handles negative values (discount).
     *
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge -5.00
     */
    public function testGetGoGreenPlusAdditionalChargeHandlesNegativeValues(): void
    {
        self::assertSame(-5.0, $this->config->getGoGreenPlusAdditionalCharge());
    }

    /**
     * Test GoGreen Plus configuration respects store scope.
     *
     * @magentoConfigFixture default dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus 1
     */
    public function testGetGoGreenPlusEnabledRespectsStoreScope(): void
    {
        self::assertTrue($this->config->getGoGreenPlusEnabled());
    }

    /**
     * Test GoGreen Plus charge configuration respects store scope.
     *
     * @magentoConfigFixture default dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 10.00
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/gogreen_plus_charge 20.00
     */
    public function testGetGoGreenPlusAdditionalChargeRespectsStoreScope(): void
    {
        self::assertSame(20.0, $this->config->getGoGreenPlusAdditionalCharge());
    }
}
