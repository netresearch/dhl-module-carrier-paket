<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Model\Carrier;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Test\Integration\Fake\CreateShipmentOrderService;
use Dhl\Paket\Test\Integration\Fake\TestLabelServiceFactory;
use Dhl\Paket\Test\Integration\Provider\MagentoShipmentRequestProvider;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Api\ShipmentRequestBuilderInterface;
use Dhl\Sdk\Bcs\Model\CreateShipmentOrderResponse;
use Dhl\Sdk\Bcs\Model\Response\CreateShipmentOrder\LabelData;
use Dhl\Sdk\Bcs\Webservice\ServiceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Shipment\Request as ShipmentRequest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Class LabelTest
 *
 * @package Dhl\Paket\Test\Integration\Model\Carrier
 * @author    Max Melzer <max.melzer@netresearch.de>
 * @copyright 2019 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class LabelTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @return ShipmentRequest[][]
     */
    public function getMagentoShipmentRequestProvider(): array
    {
        return [
            ['request 1' => MagentoShipmentRequestProvider::getRequest()]
        ];
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
     * @dataProvider getMagentoShipmentRequestProvider
     * @magentoAppIsolation enabled
     *
     * @param ShipmentRequest $magentoRequest
     * @throws LocalizedException
     *
     */
    public function testRequestToShipment(ShipmentRequest $magentoRequest)
    {
        $labelService = new CreateShipmentOrderService();

        $labelData = new LabelData('123', 'some url', 'my label');
        $labelServiceResponse = new CreateShipmentOrderResponse(true, [$labelData]);
        $labelService->setResponse($labelServiceResponse);

        $labelServiceFactory = new TestLabelServiceFactory($labelService);
        $this->objectManager->addSharedInstance($labelServiceFactory, ServiceFactory::class);

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($magentoRequest);

        $lastRequest = $labelService->getLastRequest();

        $this->assertMagentoAndSdkRequestMatch($magentoRequest, $lastRequest);

        self::assertEquals(
            $lastRequest->getShipmentOrder()->getLabelResponseType(),
            ShipmentRequestBuilderInterface::LABEL_RESPONSE_TYPE_B64
        );

        $info = $result->getData('info')[0];
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
     * @dataProvider getMagentoShipmentRequestProvider
     * @magentoAppIsolation enabled
     *
     * @param ShipmentRequest $magentoRequest
     * @throws LocalizedException
     *
     */
    public function testRequestToShipmentThrowsAuthenticationException(ShipmentRequest $magentoRequest)
    {
        $exceptionMessage = 'Auth failed';

        $labelService = new CreateShipmentOrderService();
        $labelService->setExpectAuthenticationException($exceptionMessage);
        $labelServiceFactory = new TestLabelServiceFactory($labelService);
        $this->objectManager->addSharedInstance($labelServiceFactory, ServiceFactory::class);

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($magentoRequest);

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertEquals($exceptionMessage, $result->getData('errors'));
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
     * @dataProvider getMagentoShipmentRequestProvider
     * @magentoAppIsolation enabled
     *
     * @param ShipmentRequest $magentoRequest
     * @throws LocalizedException
     *
     */
    public function testRequestToShipmentThrowsGeneralErrorException(ShipmentRequest $magentoRequest)
    {
        $exceptionMessage = 'General error';
        $labelService = new CreateShipmentOrderService();
        $labelService->setExpectGeneralErrorException($exceptionMessage);
        $labelServiceFactory = new TestLabelServiceFactory($labelService);
        $this->objectManager->addSharedInstance($labelServiceFactory, ServiceFactory::class);

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($magentoRequest);

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertEquals($exceptionMessage, $result->getData('errors'));
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
     * @dataProvider getMagentoShipmentRequestProvider
     * @magentoAppIsolation enabled
     *
     * @param ShipmentRequest $magentoRequest
     * @throws LocalizedException
     *
     */
    public function testRequestToShipmentThrowsHardValidationException(ShipmentRequest $magentoRequest)
    {
        $exceptionMessage = 'Hard validation error';
        $labelService = new CreateShipmentOrderService();
        $labelService->setExpectHardValidationException($exceptionMessage);
        $labelServiceFactory = new TestLabelServiceFactory($labelService);
        $this->objectManager->addSharedInstance($labelServiceFactory, ServiceFactory::class);

        /** @var Paket $subject */
        $subject = $this->objectManager->get(Paket::class);
        $result = $subject->requestToShipment($magentoRequest);

        $info = current($result->getData('info'));

        self::assertFalse($info);
        self::assertContains($exceptionMessage, $result->getData('errors'));
    }

    /**
     * @param ShipmentRequest $mageRequest
     * @param ShipmentRequestInterface $sdkRequest
     */
    private function assertMagentoAndSdkRequestMatch(
        ShipmentRequest $mageRequest,
        ShipmentRequestInterface $sdkRequest
    ) {
        /** Verify Receiver */
        $sdkReceiver = $sdkRequest->getReceiver();
        self::assertEquals($mageRequest->getRecipientAddressCity(), $sdkRequest->getReceiver()->getCity());
        self::assertEquals($mageRequest->getRecipientAddressCountryCode(), $sdkReceiver->getCountryCode());
        self::assertEquals($mageRequest->getRecipientAddressPostalCode(), $sdkReceiver->getPostalCode());
        self::assertEquals(
            $mageRequest->getRecipientAddressStreet(),
            implode(' ', [$sdkReceiver->getStreetName(), $sdkReceiver->getStreetNumber()])
        );
        self::assertEquals(
            $mageRequest->getRecipientAddressStreet1(),
            implode(' ', [$sdkReceiver->getStreetName(), $sdkReceiver->getStreetNumber()])
        );
        self::assertEquals(
            implode(' ', [$mageRequest->getRecipientContactPersonFirstName(), $mageRequest->getRecipientContactPersonLastName()]),
            $sdkReceiver->getName()
        );
        self::assertEquals($mageRequest->getRecipientContactPersonName(), $sdkReceiver->getName());

        /** Verify Shipper */
        $sdkShipper = $sdkRequest->getShipper();
        self::assertEquals($mageRequest->getShipperContactPersonName(), $sdkShipper->getName());
        self::assertEquals(
            implode(' ', [$mageRequest->getShipperContactPersonFirstName(), $mageRequest->getShipperContactPersonLastName()]),
            $sdkShipper->getName()
        );
        self::assertEquals($mageRequest->getShipperAddressCountryCode(), $sdkShipper->getCountryCode());
        self::assertEquals($mageRequest->getShipperAddressCity(), $sdkShipper->getCity());
        self::assertEquals($mageRequest->getShipperAddressPostalCode(), $sdkShipper->getPostalCode());
        self::assertEquals(
            $mageRequest->getShipperAddressStreet(),
            implode(' ', [$sdkShipper->getStreetName(), $sdkShipper->getStreetNumber()])
        );
        self::assertEquals(
            $mageRequest->getShipperAddressStreet1(),
            implode(' ', [$sdkShipper->getStreetName(), $sdkShipper->getStreetNumber()])
        );

        /** Verify Shipment Order */
        $sdkShipmentDetails = $sdkRequest->getShipmentDetails();
        self::assertEquals($mageRequest->getData('packages/0/params/weight'), $sdkShipmentDetails->getWeight());
        self::assertEquals($mageRequest->getData('packages/0/params/container'), $sdkShipmentDetails->getProduct());
        self::assertEquals($mageRequest->getData('packaging_type'), $sdkShipmentDetails->getProduct());
        self::assertEquals($mageRequest->getData('package_weight'), $sdkShipmentDetails->getWeight());

        /** Verify Package Params */
        $magePackageParams = $mageRequest->getData('package_params');
        self::assertEquals($magePackageParams->getData('container'), $sdkShipmentDetails->getProduct());
        self::assertEquals($magePackageParams->getData('weight'), $sdkShipmentDetails->getWeight());
    }
}
