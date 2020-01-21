<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model;

use Dhl\Paket\Model\AdditionalFee\AdditionalFeeConfiguration;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Model\ResourceModel\Quote\Address\ShippingOptionSelectionCollection;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelection;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;
use Dhl\ShippingCore\Model\ShippingSettings\Data\Selection\Selection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AdditionalFeeConfigurationTest
 *
 * @author Max Melzer <max.melzer@netresearch.de>
 */
class AdditionalFeeConfigurationTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|ObjectManager
     */
    private $objectManager;

    /**
     * @var MockObject|QuoteSelection
     */
    private $mockSelection;

    /**
     * @var MockObject|Quote
     */
    private $mockQuote;

    protected function setUp()
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();

        $mockAddress = $this->getMockBuilder(AddressInterface::class)
            ->getMock();
        $this->mockQuote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockQuote->method('getShippingAddress')->willReturn($mockAddress);

        $this->mockSelection = $this->getMockBuilder(ShippingOptionSelectionCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSelectionRepo = $this->getMockBuilder(QuoteSelectionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSelectionRepo->method('getList')->willReturn($this->mockSelection);

        $this->objectManager->addSharedInstance($mockSelectionRepo, QuoteSelectionRepository::class);
    }

    /**
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredCombinedCharge 200.00
     */
    public function testGetCombinedCharge()
    {
        $this->mockSelection->method('count')->willReturn(2);

        /** @var AdditionalFeeConfiguration $additionalFeeConfig */
        $additionalFeeConfig = $this->objectManager->create(AdditionalFeeConfiguration::class);

        self::assertSame('DHL Preferred Delivery', $additionalFeeConfig->getLabel()->render());
        self::assertSame(Paket::CARRIER_CODE, $additionalFeeConfig->getCarrierCode());
        self::assertSame(200.00, $additionalFeeConfig->getServiceCharge($this->mockQuote));
    }

    /**
     * @magentoConfigFixture current_store dhlshippingsolutions/dhlpaket/additional_services/services_group/preferredTimeCharge 11.11
     */
    public function testGetTimeCharge()
    {
        $this->mockSelection->method('count')->willReturn(1);
        $this->mockSelection->method('getFirstItem')->willReturn(
            new Selection(Codes::CHECKOUT_SERVICE_PREFERRED_TIME, 'time', '1')
        );

        /** @var AdditionalFeeConfiguration $additionalFeeConfig */
        $additionalFeeConfig = $this->objectManager->create(AdditionalFeeConfiguration::class);

        self::assertSame(11.11, $additionalFeeConfig->getServiceCharge($this->mockQuote));
    }
}
