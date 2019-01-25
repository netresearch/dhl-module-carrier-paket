<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Model\Carrier;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\Mock\TestLabelServiceFactory;
use Dhl\Paket\Test\Integration\Mock\TestSoapClientFactory;
use Dhl\Sdk\Bcs\Model\CreateShipmentOrderResponse;
use Dhl\Sdk\Bcs\Model\Response\CreateShipmentOrder\LabelData;
use Dhl\Sdk\Bcs\Webservice\Exception\AuthenticationException;
use Dhl\Sdk\Bcs\Webservice\Exception\GeneralErrorException;
use Dhl\Sdk\Bcs\Webservice\Exception\HardValidationException;
use Dhl\Sdk\Bcs\Webservice\ServiceFactory;
use Dhl\Sdk\Bcs\Webservice\Soap\Service\CreateShipmentOrderService;
use Dhl\Sdk\Bcs\Webservice\Soap\SoapClientInterface;
use Dhl\Sdk\Bcs\Webservice\SoapClientFactory;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\Request as ShipmentRequest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class LabelTest extends TestCase
{

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CreateShipmentOrderService|MockObject
     */
    private $labelService;

    public function setUp()
    {
        parent::setUp();
        $this->objectManager = Bootstrap::getObjectManager();

        /** @var SoapClientInterface| MockObject $soapClientMock */
        $soapClientMock = $this->getMockBuilder(SoapClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $soapClientFactory = new TestSoapClientFactory($soapClientMock);
        $this->objectManager->addSharedInstance($soapClientFactory, SoapClientFactory::class);

        $this->labelService = $this->getMockBuilder(CreateShipmentOrderService::class)
            ->disableOriginalConstructor()
            ->setMethods(['performRequest'])
            ->getMock();

        $labelServiceFactory = new TestLabelServiceFactory($this->labelService);
        $this->objectManager->addSharedInstance($labelServiceFactory, ServiceFactory::class);
    }

    /**
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store shipping/origin/city Berlin
     * @magentoConfigFixture current_store shipping/origin/street_line1 kurfürstendamm 12
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 91
     * @magentoConfigFixture current_store shipping/origin/postcode 10719
     *
     * @magentoConfigFixture current_store general/store_information/name SER-Local-23
     * @magentoConfigFixture current_store general/store_information/phone 234324234
     * @magentoConfigFixture current_store general/store_information/country_id DE
     * @magentoConfigFixture current_store general/store_information/region_id 91
     * @magentoConfigFixture current_store general/store_information/postcode 04229
     * @magentoConfigFixture current_store general/store_information/city Leipzig
     * @magentoConfigFixture current_store general/store_information/street_line1 Nonnenstraße 11d
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/shipment_settings/product V01PAK
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testRequestToShipment()
    {
        $labelData = new LabelData('123', 'some url', 'my label');
        $this->labelService
            ->expects($this->once())
            ->method('performRequest')
            ->willReturn(new CreateShipmentOrderResponse(true, [$labelData]));

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($this->getRequest());

        $info = current($result->getData('info'));

        self::assertEquals('123', $info['tracking_number']);
        self::assertEquals('my label', $info['label_content']);
    }

    /**
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store shipping/origin/city Berlin
     * @magentoConfigFixture current_store shipping/origin/street_line1 kurfürstendamm 12
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 91
     * @magentoConfigFixture current_store shipping/origin/postcode 10719
     *
     * @magentoConfigFixture current_store general/store_information/name SER-Local-23
     * @magentoConfigFixture current_store general/store_information/phone 234324234
     * @magentoConfigFixture current_store general/store_information/country_id DE
     * @magentoConfigFixture current_store general/store_information/region_id 91
     * @magentoConfigFixture current_store general/store_information/postcode 04229
     * @magentoConfigFixture current_store general/store_information/city Leipzig
     * @magentoConfigFixture current_store general/store_information/street_line1 Nonnenstraße 11d
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/shipment_settings/product V01PAK
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testRequestToShipmentThrowsAuthenticationException()
    {
        $this->labelService
            ->expects($this->once())
            ->method('performRequest')
            ->willThrowException(new AuthenticationException('auth failed'));

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($this->getRequest());

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertEquals('auth failed', $result->getData('errors'));
    }

    /**
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store shipping/origin/city Berlin
     * @magentoConfigFixture current_store shipping/origin/street_line1 kurfürstendamm 12
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 91
     * @magentoConfigFixture current_store shipping/origin/postcode 10719
     *
     * @magentoConfigFixture current_store general/store_information/name SER-Local-23
     * @magentoConfigFixture current_store general/store_information/phone 234324234
     * @magentoConfigFixture current_store general/store_information/country_id DE
     * @magentoConfigFixture current_store general/store_information/region_id 91
     * @magentoConfigFixture current_store general/store_information/postcode 04229
     * @magentoConfigFixture current_store general/store_information/city Leipzig
     * @magentoConfigFixture current_store general/store_information/street_line1 Nonnenstraße 11d
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/shipment_settings/product V01PAK
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testRequestToShipmentThrowsGeneralErrorException()
    {
        $this->labelService
            ->expects($this->once())
            ->method('performRequest')
            ->willThrowException(new GeneralErrorException('general error'));

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($this->getRequest());

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertEquals('general error', $result->getData('errors'));
    }

    /**
     * @magentoConfigFixture current_store carriers/dhlpaket/active 1
     * @magentoConfigFixture current_store shipping/origin/city Berlin
     * @magentoConfigFixture current_store shipping/origin/street_line1 kurfürstendamm 12
     * @magentoConfigFixture current_store shipping/origin/country_id DE
     * @magentoConfigFixture current_store shipping/origin/region_id 91
     * @magentoConfigFixture current_store shipping/origin/postcode 10719
     *
     * @magentoConfigFixture current_store general/store_information/name SER-Local-23
     * @magentoConfigFixture current_store general/store_information/phone 234324234
     * @magentoConfigFixture current_store general/store_information/country_id DE
     * @magentoConfigFixture current_store general/store_information/region_id 91
     * @magentoConfigFixture current_store general/store_information/postcode 04229
     * @magentoConfigFixture current_store general/store_information/city Leipzig
     * @magentoConfigFixture current_store general/store_information/street_line1 Nonnenstraße 11d
     *
     * @magentoConfigFixture current_store carriers/dhlpaket/shipment_settings/product V01PAK
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testRequestToShipmentThrowsHardValidationException()
    {
        $this->labelService
            ->expects($this->once())
            ->method('performRequest')
            ->willThrowException(
                new HardValidationException(
                    'Hard validation error',
                    1101,
                    ['statusMessage']
                )
            );

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($this->getRequest());

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertEquals('Failed to create shipment label: statusMessage', $result->getData('errors'));
    }

    /**
     * @return ShipmentRequest
     */
    private function getRequest(): ShipmentRequest
    {
        $packageId = 1;
        /** @var \Magento\Sales\Model\Order $order */
        $orderId = 1;
        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->expects($this->any())
            ->method('getId')
            ->willReturn($orderId);
        $order->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn(new DataObject(['carrier_code' => 'dhlshipping']));
        $order->method('getIsVirtual')
            ->willReturn(1);
        $shipment = $this->objectManager->create(DataObject::class, ['data' => [
            'order' => $order,
        ]]);
        $package = [
            'params' => [
                'container' => 'foo',
                'weight' => 42
            ],
            'items' => [],
        ];

        $request = new ShipmentRequest();
        $request->setData('packages', [$packageId => $package]);
        $request->setOrderShipment($shipment);
        $request->setShipperContactPersonName('Hans Mueller');
        $request->setShipperAddressStreet('MusterStreet 12');
        $request->setShipperAddressCity('Berlin');
        $request->setShipperAddressPostalCode('01234');
        $request->setShipperAddressCountryCode('DE');

        $request->setRecipientContactPersonName('Elfriede Bloed');
        $request->setRecipientAddressStreet('Nonnenstraße 11d');
        $request->setRecipientAddressCity('Leipzig');
        $request->setRecipientAddressPostalCode('04229');
        $request->setRecipientAddressCountryCode('DE');

        return $request;
    }
}
