<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\ShipmentDate;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShipmentDate\Validator\DropOffDays;
use Dhl\ShippingCore\Model\Config\Config;
use Dhl\ShippingCore\Model\ShipmentDate\ShipmentDate;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

/**
 * Class ShipmentDateTest
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 */
class ShipmentDateTest extends TestCase
{
    /**
     * @var Order
     */
    private static $order;

    /**
     * @var Config
     */
    private $mockConfig;

    /**
     * @var ModuleConfig
     */
    private $mockModuleConfig;

    /**
     * @var TimezoneInterface|MockObject
     */
    private $mockTimezone;

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
            OrderFixtureRollback::create()->execute(new \TddWizard\Fixtures\Sales\OrderFixture(self::$order));
        } catch (\Exception $exception) {
            $argv = $_SERVER['argv'] ?? [];
            if (in_array('--verbose', $argv, true)) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->mockConfig = $this->getMockBuilder(Config::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $this->mockModuleConfig = $this->getMockBuilder(ModuleConfig::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();

        $this->mockTimezone = $this->getMockBuilder(TimezoneInterface::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();
    }

    /**
     * Test data provider.
     *
     * @return \DateTime[][]|string[][]
     */
    public function dataProvider(): array
    {
        /**
         * 2019-02-01 10:00:00 was a Friday.
         *
         * @return \DateTime
         */
        $createBaseDate = static function (): \DateTime {
            return (new \DateTime())
                ->setDate(2019, 2, 1)
                ->setTime(10, 0);
        };

        $friday = '5';
        $saturday = '6';

        return [
            'before cut-off time, current day not allowed' => [
                'excludedDropOffDays' => [
                    $friday,
                ],
                'currentTime' => $createBaseDate(),
                'cutoffTime' => $createBaseDate()->setTime(15, 0),
                'expectedDate' => $createBaseDate()->setDate(2019, 2, 2),
            ],
            'after cut-off time, next day not allowed, after next day is sunday' => [
                'excludedDropOffDays' => [
                    $saturday,
                ],
                'currentTime' => $createBaseDate(),
                'cutoffTime' => $createBaseDate()->setTime(8, 0),
                'expectedDate' => $createBaseDate()->setDate(2019, 2, 4),
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @magentoDataFixture createOrder
     *
     * @param string[][] $excludedDropOffDays
     * @param \DateTime $currentTime
     * @param \DateTime $cutOffTime
     * @param \DateTime $expectedDate
     */
    public function testGetDate(
        array $excludedDropOffDays,
        \DateTime $currentTime,
        \DateTime $cutOffTime,
        \DateTime $expectedDate
    ) {
        $this->mockTimezone->method('scopeDate')->willReturn($currentTime);
        $this->mockConfig->method('getCutOffTime')->willReturn($cutOffTime);
        $this->mockModuleConfig->method('getExcludedDropOffDays')->willReturn($excludedDropOffDays);

        /** @var ShipmentDate $subject */
        $subject = Bootstrap::getObjectManager()->create(
            ShipmentDate::class,
            [
                'timezone' => $this->mockTimezone,
                'config' => $this->mockConfig,
                'dayValidators' => [
                    Bootstrap::getObjectManager()->create(
                        DropOffDays::class,
                        [
                            'moduleConfig' => $this->mockModuleConfig,
                        ]
                    ),
                ],
            ]
        );

        $result = $subject->getDate(self::$order->getStoreId());

        self::assertEquals($expectedDate, $result);
    }
}
