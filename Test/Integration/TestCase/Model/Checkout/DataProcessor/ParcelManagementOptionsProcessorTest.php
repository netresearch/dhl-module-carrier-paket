<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Checkout;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\TestDouble\CheckoutServiceStub;
use Dhl\Sdk\Paket\ParcelManagement\Service\ServiceFactory;
use Dhl\ShippingCore\Model\Checkout\CarrierData;
use Dhl\ShippingCore\Model\ShippingOption\Input;
use Dhl\ShippingCore\Model\ShippingOption\Option;
use Dhl\ShippingCore\Model\ShippingOption\ShippingOption;
use Dhl\ShippingCore\Model\Webapi\CheckoutManagement;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class ParcelManagementOptionsProcessorTest
 *
 * @package Dhl\Paket\Test\Integration\TestCase\Model\Checkout
 * @author   Andreas Müller <andreas.mueller@netresearch.de>
 */
class ParcelManagementOptionsProcessorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|\Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var CheckoutServiceStub
     */
    private $checkoutService;

    protected function setUp()
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();

        $this->checkoutService = new CheckoutServiceStub();
        $this->serviceFactory = $this->getMockBuilder(ServiceFactory::class)
            ->setMethods(['createCheckoutService'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceFactory->method('createCheckoutService')->willReturn($this->checkoutService);
        $this->objectManager->addSharedInstance($this->serviceFactory, ServiceFactory::class);
    }

    /**
     * @test
     *
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredlocation 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredneighbour 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredday 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredtime 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/parcelannouncement 0
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/shipment_defaults/print_only_if_codeable 0
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function processShippingOptions()
    {
        $expectedDayValues = ['', '2019-12-19', '2019-12-20', '2019-12-21'];
        $expectedTimeValues = ['', '10001200', '12001400', '14001600'];

        /** @var CheckoutManagement $checkoutManagement */
        $checkoutManagement = $this->objectManager->create(CheckoutManagement::class);
        $checkoutData = $checkoutManagement->getCheckoutData('DE', '04229');

        $carriers = $checkoutData->getCarriers();
        self::assertArrayHasKey(Paket::CARRIER_CODE, $carriers);

        /** @var CarrierData $carrier */
        $carrier = $carriers[Paket::CARRIER_CODE];
        $serviceOptions = $carrier->getServiceOptions();

        self::assertArrayHasKey('preferredDay', $serviceOptions);
        self::assertArrayHasKey('preferredTime', $serviceOptions);
        self::assertArrayNotHasKey('preferredLocation', $serviceOptions);
        self::assertArrayNotHasKey('preferredNeighbour', $serviceOptions);
        self::assertArrayNotHasKey('parcelAnnouncement', $serviceOptions);

        /** @var ShippingOption $serviceOption */
        foreach ($serviceOptions as $serviceOption) {
            $inputs = $serviceOption->getInputs();

            /** @var Input $input */
            foreach ($inputs as $input) {
                if ($input->getCode() === 'date') {
                    $options = $input->getOptions();
                    $values = [];
                    /** @var Option $option */
                    foreach ($options as $option) {
                        $values[] = $option->getValue();
                    }
                    self::assertEquals($expectedDayValues, $values);
                }

                if ($input->getCode() === 'time') {
                    $options = $input->getOptions();
                    $values = [];
                    /** @var Option $option */
                    foreach ($options as $option) {
                        $values[] = $option->getValue();
                    }
                    self::assertEquals($expectedTimeValues, $values);
                }
            }
        }
    }
}
