<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\TestDoubles\CheckoutServiceStub;
use Dhl\Sdk\Paket\ParcelManagement\Service\ServiceFactory;
use Dhl\ShippingCore\Model\Checkout\CarrierData;
use Dhl\ShippingCore\Model\Webapi\CheckoutManagement;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class AdditionalChargesProcessorTest
 *
 * @package Dhl\Paket\Test\Integration\TestCase\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class AdditionalChargesProcessorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|ObjectManager
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredday 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredtime 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredTimeCharge 50.00
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredDayCharge 100.00
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredCombinedCharge 200.00
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testAddAdditionalChargesWithBaseCurrency()
    {
        /** @var CheckoutManagement $checkoutManagement */
        $checkoutManagement = $this->objectManager->create(CheckoutManagement::class);
        $checkoutData = $checkoutManagement->getCheckoutData('DE', '04229');

        $carriers = $checkoutData->getCarriers();
        self::assertArrayHasKey(Paket::CARRIER_CODE, $carriers);

        /** @var CarrierData $carrier */
        $carrier = $carriers[Paket::CARRIER_CODE];
        $serviceOptions = $carrier->getServiceOptions();
        self::assertContains('$100.00', $serviceOptions['preferredDay']->getInputs()['date']->getComment()->getContent());
        self::assertContains('$50.00', $serviceOptions['preferredTime']->getInputs()['time']->getComment()->getContent());
    }

    /**
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredday 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredtime 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredTimeCharge 50.00
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredDayCharge 100.00
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredCombinedCharge 200.00
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @magentoConfigFixture current_store currency/options/default EUR
     */
    public function testAddAdditionalChargesWithDifferentDisplayCurrency()
    {
        /** @var CheckoutManagement $checkoutManagement */
        $checkoutManagement = $this->objectManager->create(CheckoutManagement::class);
        $checkoutData = $checkoutManagement->getCheckoutData('DE', '04229');

        $carriers = $checkoutData->getCarriers();
        self::assertArrayHasKey(Paket::CARRIER_CODE, $carriers);

        /** @var CarrierData $carrier */
        $carrier = $carriers[Paket::CARRIER_CODE];
        $serviceOptions = $carrier->getServiceOptions();
        self::assertContains(
            ' €70.67',
            $serviceOptions['preferredDay']->getInputs()['date']->getComment()->getContent()
        );
        self::assertContains(
            '€35.34',
            $serviceOptions['preferredTime']->getInputs()['time']->getComment()->getContent()
        );
    }
}