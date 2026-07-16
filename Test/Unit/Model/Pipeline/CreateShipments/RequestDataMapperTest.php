<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Unit\Model\Pipeline\CreateShipments;

use Dhl\Paket\Api\ShipmentDateInterface;
use Dhl\Paket\Model\Pipeline\CreateShipments\RequestDataMapper;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\Data\PackageAdditional;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractor;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractorFactory;
use Dhl\Paket\Model\Util\CustomsDeclarationCurrency;
use Dhl\Paket\Model\Webservice\ShipmentOrderRequestBuilderFactory;
use Dhl\Sdk\ParcelDe\Shipping\Api\ShipmentOrderRequestBuilderInterface;
use Magento\Sales\Model\Order;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageItemInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Util\UnitConverterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Customs values reach the DHL API in the declaration currency: for US customs territory
 * shipments from EUR stores they are converted to USD at DHL's fixed 0.85 EUR/USD rate
 * (DHL directive, 2026-07-16, DHLGW-1561); everywhere else the amounts are passed through
 * with the EUR label the SDK always used.
 */
class RequestDataMapperTest extends TestCase
{
    /**
     * @var mixed[]|null args of the captured setCustomsDetails() call
     */
    private ?array $customsDetailsArgs = null;

    /**
     * @var mixed[]|null args of the captured addExportItem() call
     */
    private ?array $exportItemArgs = null;

    private function createBuilderMock(): ShipmentOrderRequestBuilderInterface
    {
        $builder = $this->createMock(ShipmentOrderRequestBuilderInterface::class);
        $builder->method('setCustomsDetails')->willReturnCallback(
            function (...$args) use ($builder): ShipmentOrderRequestBuilderInterface {
                $this->customsDetailsArgs = $args;
                return $builder;
            }
        );
        $builder->method('addExportItem')->willReturnCallback(
            function (...$args) use ($builder): ShipmentOrderRequestBuilderInterface {
                $this->exportItemArgs = $args;
                return $builder;
            }
        );

        return $builder;
    }

    private function createExtractorMock(string $baseCurrency, string $destinationCountry): RequestExtractor
    {
        $order = $this->createMock(Order::class);
        $order->method('getIncrementId')->willReturn('100000001');

        $recipient = $this->createMock(RecipientInterface::class);
        $recipient->method('getCountryCode')->willReturn($destinationCountry);

        $packageAdditional = $this->createMock(PackageAdditional::class);
        $packageAdditional->method('getCustomsFees')->willReturn(7.99);

        $package = $this->createMock(PackageInterface::class);
        $package->method('getCustomsValue')->willReturn(100.0);
        $package->method('getPackageAdditional')->willReturn($packageAdditional);

        $packageItem = $this->createMock(PackageItemInterface::class);
        $packageItem->method('getQty')->willReturn(2.0);
        $packageItem->method('getCustomsValue')->willReturn(29.99);

        $extractor = $this->createMock(RequestExtractor::class);
        $extractor->method('getStoreId')->willReturn(1);
        $extractor->method('getBaseCurrencyCode')->willReturn($baseCurrency);
        $extractor->method('getOrder')->willReturn($order);
        $extractor->method('getRecipient')->willReturn($recipient);
        $extractor->method('getPackages')->willReturn([0 => $package]);
        $extractor->method('getPackageItems')->willReturn([$packageItem]);

        return $extractor;
    }

    /**
     * @dataProvider customsCurrencyProvider
     */
    public function testCustomsValuesAreDeclaredInCustomsCurrency(
        string $baseCurrency,
        string $destinationCountry,
        string $expectedCurrency,
        float $expectedFee,
        float $expectedItemValue
    ): void {
        $extractor = $this->createExtractorMock($baseCurrency, $destinationCountry);
        $extractorFactory = $this->createMock(RequestExtractorFactory::class);
        $extractorFactory->method('create')->willReturn($extractor);

        $builderFactory = $this->createMock(ShipmentOrderRequestBuilderFactory::class);
        $builderFactory->method('create')->willReturn($this->createBuilderMock());

        $shipmentDate = $this->createMock(ShipmentDateInterface::class);
        $shipmentDate->method('getDate')->willReturn(new \DateTimeImmutable('2026-07-16'));

        $mapper = new RequestDataMapper(
            $builderFactory,
            $extractorFactory,
            $shipmentDate,
            $this->createMock(UnitConverterInterface::class),
            new CustomsDeclarationCurrency()
        );

        $request = new Request();
        $request->setData('packages', [0 => ['params' => []]]);

        $mapper->mapRequest(0, $request);

        self::assertNotNull($this->customsDetailsArgs, 'setCustomsDetails was not called');
        self::assertNotNull($this->exportItemArgs, 'addExportItem was not called');
        self::assertSame($expectedFee, $this->customsDetailsArgs[2], 'postal charges amount');
        self::assertSame($expectedCurrency, $this->customsDetailsArgs[12] ?? null, 'declaration currency');
        self::assertSame($expectedItemValue, $this->exportItemArgs[2], 'export item value');
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: float, 4: float}>
     */
    public static function customsCurrencyProvider(): array
    {
        return [
            // 7.99 / 0.85 = 9.40, 29.99 / 0.85 = 35.28
            'EUR store, USA: converted to USD'  => ['EUR', 'USA', 'USD', 9.40, 35.28],
            'EUR store, Puerto Rico: converted' => ['EUR', 'PRI', 'USD', 9.40, 35.28],
            'USD store, USA: passed through'    => ['USD', 'USA', 'USD', 7.99, 29.99],
            'EUR store, Switzerland: stays EUR' => ['EUR', 'CHE', 'EUR', 7.99, 29.99],
            // unsupported base currency: previous behavior, EUR label (German market)
            'GBP store, USA: keeps EUR label'   => ['GBP', 'USA', 'EUR', 7.99, 29.99],
        ];
    }
}
