<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\Sdk\Bcs\Api\Data\ShipmentRequestInterface;
use Dhl\Sdk\Bcs\Api\ShipmentRequestBuilderInterface;
use Dhl\Sdk\Bcs\Api\ShippingProductsInterface;
use Dhl\ShippingCore\Util\StreetSplitterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * @inheritDoc
 */
class RequestDataMapper implements RequestDataMapperInterface
{
    /**
     * The shipment request builder.
     *
     * @var ShipmentRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * The module configuration.
     *
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var StreetSplitterInterface
     */
    private $streetSplitter;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * Constructor.
     *
     * @param ShipmentRequestBuilderInterface $requestBuilder
     * @param ModuleConfigInterface           $moduleConfig
     * @param StreetSplitterInterface         $streetSplitter
     * @param TimezoneInterface               $timezone
     */
    public function __construct(
        ShipmentRequestBuilderInterface $requestBuilder,
        ModuleConfigInterface $moduleConfig,
        StreetSplitterInterface $streetSplitter,
        TimezoneInterface $timezone
    ) {
        $this->requestBuilder    = $requestBuilder;
        $this->moduleConfig      = $moduleConfig;
        $this->streetSplitter    = $streetSplitter;
        $this->timezone          = $timezone;
    }

    /**
     * @inheritDoc
     */
    public function mapRequest(Request $request): ShipmentRequestInterface
    {
        // Split address into street name and street number as required by the webservice
        $shipperAddress  = $this->streetSplitter->splitStreet($request->getShipperAddressStreet());
        $receiverAddress = $this->streetSplitter->splitStreet($request->getRecipientAddressStreet());

        $this->requestBuilder
            ->setShipperAddress(
                $request->getShipperContactPersonName(),
                $shipperAddress['street_name'],
                $shipperAddress['street_number'],
                (string) $request->getShipperAddressPostalCode(),
                $request->getShipperAddressCity(),
                $request->getShipperAddressCountryCode()
            );

        $this->requestBuilder
            ->setReceiverAddress(
                $request->getRecipientContactPersonName(),
                $receiverAddress['street_name'],
                $receiverAddress['street_number'],
                (string) $request->getRecipientAddressPostalCode(),
                $request->getRecipientAddressCity(),
                $request->getRecipientAddressCountryCode()
            );

        $this->requestBuilder->setShipmentDetails(
            $this->moduleConfig->getProduct(),
            $this->getBillingNumber(),
            $this->getShipmentDate(),
            (float) $request->getPackageParams()->getWeight(),
            (int) $request->getPackageParams()->getLength(),
            (int) $request->getPackageParams()->getWidth(),
            (int) $request->getPackageParams()->getHeight()
        );

        $this->requestBuilder
            ->setShipmentOrder(ShipmentRequestBuilderInterface::LABEL_RESPONSE_TYPE_B64);

        return $this->requestBuilder->build();
    }

    /**
     * Returns the 14-digit encoded billing number.
     *
     * @return string
     */
    private function getBillingNumber(): string
    {
        $number        = $this->moduleConfig->getAccountNumber();
        $product       = $this->moduleConfig->getProduct();
        $procedure     = null;
        $participation = '01';
        $products      = ShippingProductsInterface::PRODUCTS;

        foreach ($products as $countryProducts) {
            if (isset($countryProducts[$product])) {
                $procedure = $countryProducts[$product];
                break;
            }
        }

        return $number . $procedure . $participation;
    }

    /**
     * Returns the shipment date.
     *
     * @return string
     */
    private function getShipmentDate(): string
    {
        $shipmentDate = $this->timezone->date();
        $shipmentDate->modify('+1 day');

        return $shipmentDate->format('Y-m-d');
    }
}
