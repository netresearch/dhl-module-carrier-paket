<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Observer;

use Dhl\Paket\Test\Integration\Fixture\QuoteFixture;
use Dhl\Paket\Test\Integration\Fixture\QuoteServiceSelectionFixture;
use Dhl\ShippingCore\Observer\DisableCodPaymentMethods;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressDe;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressUs;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\SimpleProduct2;
use Magento\Framework\DataObject;
use Magento\Framework\Event\InvokerInterface;
use Magento\Framework\Event\Observer;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class DisableCodPaymentMethodsTest
 *
 * @package Dhl\Paket\Test\Integration\TestCase\Observer
 * @author  Sebastian Ertner <sebastian.ertner@netresearch.de>
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class DisableCodPaymentMethodsTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

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
     * @var Quote
     */
    private static $currentQuote;

    /**
     * Prepare invoker, observer and observer config.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->invoker = $this->objectManager->get(InvokerInterface::class);
        $this->observer = $this->objectManager->get(Observer::class);
        $this->observerConfig = [
            'instance' => DisableCodPaymentMethods::class,
            'name' => 'dhlgw_disable_cod_payment',
        ];
    }

    /**
     * Print error after test suite execution.
     *
     * @param string $message
     */
    private static function printError(string $message)
    {
        if (isset($_SERVER['argv']) && is_array($_SERVER['argv']) && in_array('--verbose', $_SERVER['argv'], true)) {
            $message = sprintf("Error during rollback: %s%s", $message, PHP_EOL);
            register_shutdown_function('fwrite', STDERR, $message);
        }
    }

    /**
     * Everything remains the same after observer ran through.
     *
     * @return string[][]|bool[][]
     */
    public function noChangesDataProvider()
    {
        return [
            'cod_remains_enabled' => [Cashondelivery::class, true],
            'cod_remains_disabled' => [Cashondelivery::class, false],
            'checkmo_remains_enabled' => [Checkmo::class, true],
            'checkmo_remains_disabled' => [Checkmo::class, false],
        ];
    }

    /**
     * COD gets disabled after observer ran through, others remain the same.
     *
     * @return string[][]|bool[][]
     */
    public function disableDataProvider()
    {
        return [
            'cod_gets_disabled' => [Cashondelivery::class, true, false],
            'cod_remains_disabled' => [Cashondelivery::class, false, false],
            'checkmo_remains_enabled' => [Checkmo::class, true, true],
            'checkmo_remains_disabled' => [Checkmo::class, false, false],
        ];
    }

    /**
     * @throws \Exception
     */
    public static function createDeQuoteWithNoServices()
    {
        self::$currentQuote = QuoteFixture::createQuote(new AddressDe(), [new SimpleProduct2()], 'dhlpaket_flatrate');
    }

    /**
     * Roll back fixtures.
     *
     * When database isolation is DISABLED, then created entities must be cleaned up afterwards.
     * However, when database isolation is ENABLED, Magento still calls the rollback method
     * AFTER the transaction was rolled back. The customer repository then throws an exception
     * because it tries to LOAD the (rolled back, no longer existing) customer prior to deleting.
     *
     * @see \Magento\Customer\Api\CustomerRepositoryInterface::delete
     * @see \Magento\Customer\Model\CustomerRegistry::retrieve
     */
    public static function createDeQuoteWithNoServicesRollback()
    {
        try {
            QuoteFixture::rollbackFixtureEntities();
        } catch (\Exception $exception) {
            self::printError($exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function createUsQuote()
    {
        self::$currentQuote = QuoteFixture::createQuote(new AddressUs(), [new SimpleProduct2()], 'dhlpaket_flatrate');
    }

    /**
     * @see createDeQuoteWithNoServicesRollback
     */
    public static function createUsQuoteRollback()
    {
        try {
            QuoteFixture::rollbackFixtureEntities();
        } catch (\Exception $exception) {
            self::printError($exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function createQuoteWithCompatibleServices()
    {
        $quote = QuoteFixture::createQuote(new AddressDe(), [new SimpleProduct2()], 'dhlpaket_flatrate');
        QuoteServiceSelectionFixture::createServiceSelection($quote, 'date', 'preferredDay', '2019-07-11');

        self::$currentQuote = $quote;
    }

    /**
     * @see createDeQuoteWithNoServicesRollback
     */
    public static function createQuoteWithCompatibleServicesRollback()
    {
        try {
            QuoteFixture::rollbackFixtureEntities();
        } catch (\Exception $exception) {
            self::printError($exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    public static function createQuoteWithIncompatibleServices()
    {
        $quote = QuoteFixture::createQuote(new AddressDe(), [new SimpleProduct2()], 'dhlpaket_flatrate');
        QuoteServiceSelectionFixture::createServiceSelection($quote, 'details', 'preferredLocation', 'Garage');

        self::$currentQuote = $quote;
    }

    /**
     * @see createDeQuoteWithNoServicesRollback
     */
    public static function createQuoteWithIncompatibleServicesRollback()
    {
        try {
            QuoteFixture::rollbackFixtureEntities();
        } catch (\Exception $exception) {
            self::printError($exception->getMessage());
        }
    }

    /**
     * Test behaviour if no quote is passed to the observer.
     *
     * - Observer must not crash.
     * - No changes must be made to the method availability.
     *
     * @test
     * @dataProvider noChangesDataProvider
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForUnavailableQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
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
     * @dataProvider noChangesDataProvider
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForVirtualQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['isVirtual'])
            ->disableOriginalConstructor()
            ->getMock();
        $quote->expects($this->any())->method('isVirtual')->willReturn(true);

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => $quote
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
     * @dataProvider noChangesDataProvider
     * @magentoDataFixture createDeQuoteWithNoServices
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function methodRemainsSameForBrokenQuote(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        self::$currentQuote->getShippingAddress()->setShippingMethod('');

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => self::$currentQuote
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
     * @dataProvider noChangesDataProvider
     * @magentoDataFixture createDeQuoteWithNoServices
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function codIsEnabledForNoServiceSelection(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => self::$currentQuote
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
     * @dataProvider disableDataProvider
     * @magentoDataFixture createUsQuote
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
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

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => self::$currentQuote
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
     * @dataProvider noChangesDataProvider
     * @magentoDataFixture createQuoteWithCompatibleServices
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     *
     * @param string $methodClass
     * @param bool $before
     */
    public function codIsEnabledForCompatibleServiceSelection(string $methodClass, bool $before)
    {
        $checkResult = new DataObject();
        $checkResult->setData('is_available', $before);

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => self::$currentQuote
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
     * @dataProvider disableDataProvider
     * @magentoDataFixture createQuoteWithIncompatibleServices
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlglobalwebservices/cod_methods cashondelivery
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

        $methodInstance = $this->objectManager->create($methodClass);
        $this->observer->setData(
            [
                'result' => $checkResult,
                'method_instance' => $methodInstance,
                'quote' => self::$currentQuote
            ]
        );

        $this->invoker->dispatch($this->observerConfig, $this->observer);

        self::assertSame($after, $checkResult->getData('is_available'));
    }
}
