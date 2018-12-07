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

class PaketTest extends TestCase
{
    /**
     * @magentoConfigFixture store carriers/dhlpaket/active 1
     * @magentoConfigFixture store carriers/dhlpaket/emulated_carrier flatrate
     * @magentoConfigFixture store carriers/flatrate/type O
     * @magentoConfigFixture store carriers/flatrate/handling_type F
     * @magentoConfigFixture store carriers/flatrate/price 5.00
     */
    public function testCollectRates()
    {
        $objectManager = Bootstrap::getObjectManager();
        /** @var Paket $subject */
        $subject = $objectManager->get(Paket::class);

        $rateRequest = new RateRequest();
        /** @var \Magento\Shipping\Model\Rate\Result $rates */
        $rates = $subject->collectRates($rateRequest);
        $this->assertCount(1, $rates->getAllRates());

        foreach ($rates->getAllRates() as $rate) {
            $this->assertEquals(Paket::CARRIER_CODE, $rate->getData('carrier'));
            $this->assertEquals(5.0, $rate->getData('price'));
        }
    }
}
