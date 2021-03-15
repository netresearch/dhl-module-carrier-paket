<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Observer;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event\InvokerInterface;
use Magento\Framework\Event\Observer;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelection;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;
use Netresearch\ShippingCore\Observer\DisableCodPaymentMethods;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;
use TddWizard\Fixtures\Catalog\ProductFixtureRollback;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;
use TddWizard\Fixtures\Customer\CustomerFixtureRollback;

/**
 * @magentoAppArea frontend
 */
class DisableCodPaymentMethodsTest extends TestCase
{
    /**
     * @var ProductFixture
     */
    private static $productFixture;

    /**
     * @var CustomerFixture
     */
    private static $customerFixture;

    /**
     * @var Cart
     */
    private static $cart;

    /**
     * @var QuoteSelection
     */
    private static $serviceSelection;

    /**
     * @var InvokerInterface
     */
    private $invoker;

    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var string[]
     */
    private $observerConfig;

    /**
     * Prepare invoker, observer and observer config.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->invoker = Bootstrap::getObjectManager()->get(InvokerInterface::class);
        $this->observer = Bootstrap::getObjectManager()->get(Observer::class);
        $this->observerConfig = [
            'instance' => DisableCodPaymentMethods::class,
            'name' => 'dhlgw_disable_cod_payment',
        ];
    }

    /**
     * COD gets disabled after observer ran through, others remain the same.
     *
     * @return string[][]|bool[][]
     */
    public function dataProvider()
    {
        return [
            'cod_gets_disabled' => [Cashondelivery::class, true, false],
            'cod_remains_disabled' => [Cashondelivery::class, false, false],
            'checkmo_remains_enabled' => [Checkmo::class, true, true],
            'checkmo_remains_disabled' => [Checkmo::class, false, false],
        ];
    }

    /**
     * Set up data fixture.
     *
     * @param string $locale
     * @param QuoteSelection|null $serviceSelection
     * @throws \Exception
     */
    private static function quoteFixture($locale = 'de_DE', QuoteSelection $serviceSelection = null)
    {
        /** @var AddressRepositoryInterface $customerAddressRepository */
        $customerAddressRepository = Bootstrap::getObjectManager()->get(AddressRepositoryInterface::class);
        $shippingMethod = 'dhlpaket_flatrate';

        // prepare checkout
        self::$productFixture = new ProductFixture(ProductBuilder::aSimpleProduct()->build());
        self::$customerFixture = new CustomerFixture(
            CustomerBuilder::aCustomer()
                ->withAddresses(AddressBuilder::anAddress($locale)->asDefaultBilling()->asDefaultShipping())
                ->build()
        );
        self::$customerFixture->login();

        self::$cart = CartBuilder::forCurrentSession()->withSimpleProduct(self::$productFixture->getSku())->build();

        // select customer's default shipping address in shipping step
        $customerAddressId = self::$cart->getCustomerSession()->getCustomer()->getDefaultShippingAddress()->getId();
        $shippingAddress = self::$cart->getQuote()->getShippingAddress();
        $shippingAddress->importCustomerAddressData($customerAddressRepository->getById($customerAddressId));
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($shippingMethod);
        $shippingAddress->save();

        if ($serviceSelection !== null) {
            /** @var QuoteSelectionRepository $repository */
            $repository = Bootstrap::getObjectManager()->get(QuoteSelectionRepository::class);
            $serviceSelection->setData(QuoteSelection::PARENT_ID, (int) $shippingAddress->getId());
            self::$serviceSelection = $repository->save($serviceSelection);
        }
    }

    /**
     * @throws \Exception
     */
    public static function deQuoteFixture()
    {
        self::quoteFixture('de_DE');
    }

    /**
     * @throws \Exception
     */
    public static function usQuoteFixture()
    {
        self::quoteFixture('en_US');
    }

    /**
     * @throws \Exception
     */
    public static function deQuoteFixtureWithCodCompatibleService()
    {
        $serviceSelection = Bootstrap::getObjectManager()->create(QuoteSelection::class);
        $serviceSelection->setData([
            AssignedSelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_PREFERRED_DAY,
            AssignedSelectionInterface::INPUT_CODE => 'date',
            AssignedSelectionInterface::INPUT_VALUE => '2019-07-11'
        ]);

        self::quoteFixture('de_DE', $serviceSelection);
    }

    /**
     * @throws \Exception
     */
    public static function deQuoteFixtureWithCodIncompatibleService()
    {
        $serviceSelection = Bootstrap::getObjectManager()->create(QuoteSelection::class);
        $serviceSelection->setData([
            AssignedSelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_DROPOFF_DELIVERY,
            AssignedSelectionInterface::INPUT_CODE => 'details',
            AssignedSelectionInterface::INPUT_VALUE => 'Garage'
        ]);

        self::quoteFixture('de_DE', $serviceSelection);
    }

    public static function quoteFixtureRollback()
    {
        try {
            /** @var Session $session */
            $session = Bootstrap::getObjectManager()->get(Session::class);
            $session->logout();

            CustomerFixtureRollback::create()->execute(self::$customerFixture);
            ProductFixtureRollback::create()->execute(self::$productFixture);
            self::$cart->getQuote()->delete();

            if (self::$serviceSelection !== null) {
                /** @var QuoteSelectionRepository $repository */
                $repository = Bootstrap::getObjectManager()->get(QuoteSelectionRepository::class);
                $repository->delete(self::$serviceSelection);
            }
        } catch (\Exception $exception) {
            if (isset($_SERVER['argv'])
                && is_array($_SERVER['argv'])
                && in_array('--verbose', $_SERVER['argv'], true)
            ) {
                $message = sprintf("Error during rollback: %s%s", $exception->getMessage(), PHP_EOL);
                register_shutdown_function('fwrite', STDERR, $message);
            }
        }
    }

    public static function deQuoteFixtureRollback()
    {
        self::quoteFixtureRollback();
    }

    public static function usQuoteFixtureRollback()
    {
        self::quoteFixtureRollback();
    }

    public static function deQuoteFixtureWithCodCompatibleServiceRollback()
    {
        self::quoteFixtureRollback();
    }

    public static function deQuoteFixtureWithCodIncompatibleServiceRollback()
    {
        self::quoteFixtureRollback();
    }

    /**
     * Test behaviour if no quote is passed to the observer.
     *
     * - Observer must not crash.
     * - No changes must be made to the method availability.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForUnavailableQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => null
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($before, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour if virtual quote is passed to the observer.
     *
     * - No changes must be made to the method availability. Order will not be shipped with DHL Paket.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForVirtualQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => $this->createConfiguredMock(Quote::class, ['isVirtual' => true])
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($before, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour if quote is broken and has no shipping method although not virtual.
     *
     * - Observer must not crash.
     * - No changes must be made to the method availability. Order will not be shipped with DHL Paket.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoDataFixture deQuoteFixture
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForBrokenQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        self::$cart->getQuote()->getShippingAddress()->setShippingMethod('');

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => self::$cart->getQuote(),
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($before, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour with domestic shipping and no value-added services.
     *
     * - If COD method is selected and it was available before, then it must remain enabled.
     * - If COD method is selected and it was unavailable before, then it must remain disabled.
     * - If no COD method is selected and it was available before, then it must remain enabled.
     * - If no COD method is selected and it was unavailable before, then it must remain disabled.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoDataFixture deQuoteFixture
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function codIsEnabledForNoServiceSelection(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => self::$cart->getQuote(),
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($before, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour with cross-border quote.
     *
     * - If COD method is selected and it was available before, then it must get disabled.
     * - If COD method is selected and it was unavailable before, then it must remain disabled.
     * - If no COD method is selected and it was available before, then it must remain enabled.
     * - If no COD method is selected and it was unavailable before, then it must remain disabled.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoDataFixture usQuoteFixture
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     * @param bool $after
     */
    public function codIsDisabledForCrossBorderQuote(string $methodClass, bool $before, bool $after)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => self::$cart->getQuote(),
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($after, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour with domestic shipping and COD compatible value-added services.
     *
     * - If COD method is selected and it was available before, then it must remain enabled.
     * - If COD method is selected and it was unavailable before, then it must remain disabled.
     * - If no COD method is selected and it was available before, then it must remain enabled.
     * - If no COD method is selected and it was unavailable before, then it must remain disabled.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoDataFixture deQuoteFixtureWithCodCompatibleService
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function codIsEnabledForCompatibleServiceSelection(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => self::$cart->getQuote(),
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($before, $checkResult->getData('is_available'));
    }

    /**
     * Test behaviour with domestic shipping and COD incompatible value-added services.
     *
     * - If COD method is selected and it was available before, then it must get disabled.
     * - If COD method is selected and it was unavailable before, then it must remain disabled.
     * - If no COD method is selected and it was available before, then it must remain enabled.
     * - If no COD method is selected and it was unavailable before, then it must remain disabled.
     *
     * @test
     * @dataProvider dataProvider
     * @magentoDataFixture deQuoteFixtureWithCodIncompatibleService
     * @magentoConfigFixture current_store shipping/parcel_processing/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     * @param bool $after
     */
    public function codIsDisabledForIncompatibleServiceSelection(string $methodClass, bool $before, bool $after)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => Bootstrap::getObjectManager()->create($methodClass),
                'quote' => self::$cart->getQuote(),
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($after, $checkResult->getData('is_available'));
    }
}
