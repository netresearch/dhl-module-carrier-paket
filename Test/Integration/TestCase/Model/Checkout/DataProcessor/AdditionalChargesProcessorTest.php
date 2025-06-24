<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Checkout\DataProcessor;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\Paket\Test\Integration\TestDouble\CheckoutServiceStub;
use Dhl\Sdk\Paket\ParcelManagement\Service\ServiceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Netresearch\ShippingCore\Model\ShippingSettings\CheckoutManagement;
use Netresearch\ShippingCore\Model\ShippingSettings\Data\CarrierData;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppIsolation enabled
 */
class AdditionalChargesProcessorTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|ObjectManager
     */
    private $objectManager;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        // suppress calls to the parcel management api
        $checkoutServiceFactory = $this->createConfiguredMock(
            ServiceFactory::class,
            ['createCheckoutService' => new CheckoutServiceStub()]
        );

        $this->objectManager->addSharedInstance($checkoutServiceFactory, ServiceFactory::class);
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/preferredday 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/preferredday_charge 100.00
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @throws LocalizedException
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
        $options = $carrier->getServiceOptions();
        self::assertNotFalse(
            strpos(
                $options[Codes::SERVICE_OPTION_PREFERRED_DAY]->getInputs()['date']->getComment()->getContent(),
                '$100.00'
            )
        );
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
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/preferredday 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/preferredday_charge 100.00
     *
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     *
     * @magentoConfigFixture current_store currency/options/default EUR
     *
     * @throws LocalizedException
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
        self::assertNotFalse(
            strpos(
                $serviceOptions[Codes::SERVICE_OPTION_PREFERRED_DAY]->getInputs()['date']->getComment()->getContent(),
                ' €70.67'
            )
        );
    }
}
