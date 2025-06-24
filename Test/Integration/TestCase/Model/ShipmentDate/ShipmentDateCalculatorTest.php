<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\ShipmentDate;

use Dhl\Paket\Model\ShipmentDate\Validator\ExcludeSundays;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Api\ShipmentDate\ShipmentDateCalculatorInterface;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

class ShipmentDateCalculatorTest extends TestCase
{
    /**
     * @var Order
     */
    private static $order;

    /**
     * @throws \Exception
     */
    public static function createOrder()
    {
        self::$order = OrderBuilder::anOrder()->withShippingMethod('flatrate_flatrate')->build();
    }

    /**
     * @throws \Exception
     */
    public static function createOrderRollback()
    {
        try {
            OrderFixtureRollback::create()->execute(new OrderFixture(self::$order));
        } catch (\Exception $exception) {
            $argv = $_SERVER['argv'] ?? [];
            if (in_array('--verbose', $argv, true)) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }
    }

    /**
     * @magentoDataFixture createOrder
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function calculateShipmentDate()
    {
        $sun = new \DateTimeImmutable('2019-12-22 12:00:00');
        $mon = new \DateTimeImmutable('2019-12-23 17:00:00');

        // before cut-off but on a Sunday
        $currentTime = $sun->setTime(10, 0);

        // Friday and Sunday configured but there is no DHL Paket pickup/drop-off on Sundays
        $cutOffTimes = [
            $sun->format('N') => $sun,
            $mon->format('N') => $mon,
        ];

        $timezoneMock = $this->getMockBuilder(TimezoneInterface::class)->disableOriginalConstructor()->getMock();
        $timezoneMock->method('scopeDate')->with(self::anything(), null, true)->willReturn($currentTime);

        $dayValidator = Bootstrap::getObjectManager()->create(ExcludeSundays::class);

        /** @var ShipmentDateCalculatorInterface $subject */
        $subject = Bootstrap::getObjectManager()->create(
            ShipmentDateCalculatorInterface::class,
            [
                'timezone' => $timezoneMock,
                'dayValidators' => [$dayValidator],
            ]
        );

        $shipmentDate = $subject->getDate($cutOffTimes, self::$order->getStoreId());

        self::assertEquals($mon, $shipmentDate);
    }
}
