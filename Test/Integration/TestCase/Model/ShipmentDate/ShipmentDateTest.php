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
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct2;
use Dhl\ShippingCore\Test\Integration\Fixture\OrderFixture;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ShipmentDateTest
 *
 * @package Dhl\Paket\Test\Integration\TestCase\Model
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 */
class ShipmentDateTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

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
     * @var Order
     */
    private $order;

    /**
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->mockConfig = $this->getMockBuilder(Config::class)
                                 ->disableOriginalConstructor()
                                 ->getMock();

        $this->mockModuleConfig = $this->getMockBuilder(ModuleConfig::class)
                                       ->disableOriginalConstructor()
                                       ->getMock();

        $this->mockTimezone = $this->getMockBuilder(TimezoneInterface::class)
                                   ->disableOriginalConstructor()
                                   ->getMock();

        /** @var Order $order */
        $this->order = OrderFixture::createOrder(
            new AddressDe(),
            [
                new SimpleProduct2(),
            ],
            'flatrate_flatrate'
        );
    }

    /**
     * Test data provider.
     *
     * @return array
     */
    public function getTestData(): array
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
     * @dataProvider getTestData
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
        $subject = $this->objectManager->create(
            ShipmentDate::class,
            [
                'timezone' => $this->mockTimezone,
                'config' => $this->mockConfig,
                'dayValidators' => [
                    $this->objectManager->create(
                        DropOffDays::class,
                        [
                            'moduleConfig' => $this->mockModuleConfig,
                        ]
                    ),
                ],
            ]
        );

        $result = $subject->getDate($this->order->getStoreId());

        self::assertEquals($expectedDate, $result);
    }
}
