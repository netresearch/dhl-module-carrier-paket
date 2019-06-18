<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Checkout;


use Dhl\Paket\Model\Checkout\DataProcessor\PreferredDayTimeOptionsProcessor;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Service\StartDate;
use Dhl\Paket\Test\Integration\TestDouble\CheckoutServiceStub;
use Dhl\Sdk\Paket\ParcelManagement\Service\ServiceFactory;
use Dhl\ShippingCore\Api\Data\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Model\Packaging\PackagingDataProvider;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct2;
use Dhl\ShippingCore\Test\Integration\Fixture\FakeReader;
use Dhl\ShippingCore\Test\Integration\Fixture\ShipmentFixture;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Magento\Framework\Exception\LocalizedException;

class PreferredDayTimeOptionsProcessorTest extends TestCase
{
    private $objectManager;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;
    /**
     * @var CheckoutServiceStub
     */
    private $checkoutService;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var StartDate
     */
    private $startDate;

    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * @var TestLogger
     */
    private $logger;

    protected function setUp()
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->logger = new TestLogger();
        $this->startDate = $this->objectManager->create(StartDate::class);
        $this->timeZone = $this->objectManager->create(TimezoneInterface::class);
        $this->config = $this->objectManager->create(ModuleConfig::class);
        $this->optionFactory = $this->objectManager->create(OptionInterfaceFactory::class);
        $this->checkoutService = new CheckoutServiceStub();
        $this->serviceFactory = $this->getMockBuilder(ServiceFactory::class)
            ->setMethods(['createCheckoutService'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceFactory->method('createCheckoutService')->willReturn($this->checkoutService);
    }

    public function dataProvider(): array
    {
        return [
            'shipment 1' => ['shipment' => ShipmentFixture::createShipment(
                new AddressDe(),
                [new SimpleProduct(), new SimpleProduct2()],
                'dhlpaket_flatrate'
            )]
        ];
    }

    /**
     * @test
     *
     * @dataProvider dataProvider
     * @throws LocalizedException
     */
    public function process()
    {
        $processor = $this->objectManager->create(
            PreferredDayTimeOptionsProcessor::class,
            [
                'optionFactory' => $this->optionFactory,
                'serviceFactory' => $this->serviceFactory,
                'moduleConfig' => $this->config,
                'startDate' => $this->startDate,
                'timeZone' => $this->timeZone,
                'logger' => $this->logger
            ]
        );

        /** @var PackagingDataProvider $packagingDataProvider */
        $packagingDataProvider = $this->objectManager->create(
            PackagingDataProvider::class,
            ['reader' => new FakeReader()]
        );
        $packagingData = $packagingDataProvider->getData($shipment);

        $t = "";
    }
}
