<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Util\ShippingProducts;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\Paket\Bcs\Exception\RequestValidatorException;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Magento\Framework\Phrase;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class BcsApiValidator implements ItemValidatorInterface
{
    use DhlSection;
    use DhlPaketGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var ShippingProducts
     */
    private $shippingProducts;

    /**
     * @var ShipmentOrderRequestBuilderInterface
     */
    private $requestBuilder;

    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config,
        ShippingProducts $shippingProducts,
        ShipmentOrderRequestBuilderInterface $requestBuilder,
        ShipmentServiceFactory $shipmentServiceFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->shippingProducts = $shippingProducts;
        $this->requestBuilder = $requestBuilder;
        $this->shipmentServiceFactory = $shipmentServiceFactory;
    }

    private function createResult(string $status, Phrase $message): ResultInterface
    {
        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Business Customer Shipping'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }

    public function execute(int $storeId): ResultInterface
    {
        try {
            $productCode = 'V01PAK'; // DHL Paket National
            $tsShip = time() + 60 * 60 * 24; // tomorrow

            $procedure = $this->shippingProducts->getProcedure($productCode);
            $ekp = $this->config->getEkp($storeId);

            $participations = $this->config->getParticipations($storeId);
            $participation = $participations[$procedure] ?? '';

            $billingNumber = $ekp . $procedure . $participation;

            $this->requestBuilder->setShipperAccount($billingNumber);
            $this->requestBuilder->setShipperAddress('Netresearch GmbH & Co.KG', 'DE', '04229', 'Leipzig', 'Nonnenstraße', '11d');
            $this->requestBuilder->setRecipientAddress('John Doe', 'DE', '53113', 'Bonn', 'Charles-de-Gaulle-Straße', '20');
            $this->requestBuilder->setShipmentDetails($productCode, new \DateTime(date('Y-m-d', $tsShip)));
            $this->requestBuilder->setPackageDetails(2.4);

            $apiRequest = $this->requestBuilder->create();
        } catch (RequestValidatorException $exception) {
            $message = __('Invalid request: %1', $exception->getMessage());
            return $this->createResult(ResultInterface::ERROR, $message);
        }

        $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $storeId]);

        try {
            $shipmentService->validateShipments([$apiRequest]);

            $status = ResultInterface::OK;
            $message = __('Label API connection established successfully.');
        } catch (ServiceException $exception) {
            $status = ResultInterface::ERROR;
            $message = __(
                'Web service error: %1 Please review your %2.',
                $exception->getMessage(),
                __('Account Settings')
            );
        }

        return $this->createResult($status, $message);
    }
}
