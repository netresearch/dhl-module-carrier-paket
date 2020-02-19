<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Util;

use Dhl\Paket\Util\TemplateParser;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Sales\OrderBuilder;
use TddWizard\Fixtures\Sales\OrderFixture;
use TddWizard\Fixtures\Sales\OrderFixtureRollback;

class TemplateParserTest extends TestCase
{
    /**
     * @var OrderInterface
     */
    private static $order;

    /**
     * create order fixture with DE recipient address
     * @throws \Exception
     */
    public static function createOrder()
    {
        self::$order = OrderBuilder::anOrder()->withShippingMethod('dhlpaket_flatrate')->build();
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
     * @test
     * @magentoDataFixture createOrder
     * @magentoConfigFixture default_store general/store_information/name NR-Test-Store
     * @magentoConfigFixture default_store general/store_information/region_id 91
     * @magentoConfigFixture default_store general/store_information/phone 000
     * @magentoConfigFixture default_store general/store_information/country_id DE
     * @magentoConfigFixture default_store general/store_information/postcode 04229
     * @magentoConfigFixture default_store general/store_information/city Leipzig
     * @magentoConfigFixture default_store general/store_information/street_line1 Nonnenstraße 11
     * @magentoConfigFixture default_store trans_email/ident_general/email admin@example.com
     *
     * @magentoConfigFixture default_store shipping/origin/country_id DE
     * @magentoConfigFixture default_store shipping/origin/region_id 91
     * @magentoConfigFixture default_store shipping/origin/postcode 04229
     * @magentoConfigFixture default_store shipping/origin/city Leipzig
     * @magentoConfigFixture default_store shipping/origin/street_line1 Nonnenstraße 11
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/checkout_settings/emulated_carrier flatrate
     */
    public function parse()
    {
        $entityId = self::$order->getEntityId();
        $incrementId = self::$order->getIncrementId();
        $firstName = self::$order->getBillingAddress()->getFirstname();
        $lastName = self::$order->getBillingAddress()->getLastname();

        $template = 'Order #{{increment_id}} ({{entity_id}}) for {{firstname}} {{lastname}} {{foo}}.';
        $expected = "Order #$incrementId ($entityId) for $firstName $lastName {{foo}}.";

        /** @var TemplateParser $parser */
        $parser = Bootstrap::getObjectManager()->get(TemplateParser::class);
        self::assertSame($expected, $parser->parse(self::$order, $template));
    }
}
