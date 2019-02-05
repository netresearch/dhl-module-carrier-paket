<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Model\Carrier;

use Dhl\Paket\Model\Carrier\Paket;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class RatesTest
 *
 * @package Dhl\Paket\Test
 * @copyright 2018 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class RatesTest extends TestCase
{
    /**
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store carriers/dhlpaket/dhl_paket_checkout_settings/emulated_carrier flatrate
     * @magentoConfigFixture current_store carriers/flatrate/type O
     * @magentoConfigFixture current_store carriers/flatrate/handling_type F
     * @magentoConfigFixture current_store carriers/flatrate/price 5.00
     */
    public function testCollectRates()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Paket $subject */
        $subject = $objectManager->get(Paket::class);

        $rateRequest = new RateRequest();
        $rateRequest->setPackageQty(1);
        /** @var \Magento\Shipping\Model\Rate\Result $rates */
        $rates = $subject->collectRates($rateRequest);
        $this->assertCount(1, $rates->getAllRates());

        foreach ($rates->getAllRates() as $rate) {
            $this->assertEquals(Paket::CARRIER_CODE, $rate->getData('carrier'));
            $this->assertEquals(5.0, $rate->getData('price'));
        }
    }
}
