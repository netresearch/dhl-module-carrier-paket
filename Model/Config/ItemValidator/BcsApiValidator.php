<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Config\ItemValidator;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\Util\ShippingProducts;
use Dhl\Paket\Model\Webservice\ShipmentOrderRequestBuilderFactory;
use Dhl\Paket\Model\Webservice\ShipmentServiceFactory;
use Dhl\Sdk\Paket\Bcs\Api\Data\OrderConfigurationInterfaceFactory;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentOrderRequestBuilderInterface;
use Dhl\Sdk\Paket\Bcs\Exception\RequestValidatorException;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\Sdk\Paket\Bcs\RequestBuilder\ShipmentOrderRequestBuilder;
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
     * @var ShipmentOrderRequestBuilderFactory
     */
    private $requestBuilderFactory;

    /**
     * @var ShipmentServiceFactory
     */
    private $shipmentServiceFactory;

    /**
     * @var OrderConfigurationInterfaceFactory
     */
    private $orderConfigFactory;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        ModuleConfig $config,
        ShippingProducts $shippingProducts,
        ShipmentOrderRequestBuilderFactory $requestBuilderFactory,
        ShipmentServiceFactory $shipmentServiceFactory,
        OrderConfigurationInterfaceFactory $orderConfigFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->config = $config;
        $this->shippingProducts = $shippingProducts;
        $this->requestBuilderFactory = $requestBuilderFactory;
        $this->shipmentServiceFactory = $shipmentServiceFactory;
        $this->orderConfigFactory = $orderConfigFactory;
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

    /**
     * Build request for the configured API type.
     *
     * @param int $storeId
     * @throws RequestValidatorException
     * @return object
     */
    private function createRequest(int $storeId)
    {
        $requestBuilder = $this->requestBuilderFactory->create($storeId);
        $productCode = 'V01PAK'; // DHL Paket National
        $procedure = $this->shippingProducts->getProcedure($productCode);
        $tsShip = time() + 60 * 60 * 24; // tomorrow

        if ($this->config->getShippingApiType() === ModuleConfig::SHIPPING_API_SOAP) {
            $billingNumber = "2222222222{$procedure}04";
            $countryCode = 'DE';
        } else {
            $billingNumber = "3333333333{$procedure}02";
            $countryCode = 'DEU';
        }

        if (!$this->config->isSandboxMode($storeId)) {
            $ekp = $this->config->getEkp($storeId);

            $participations = $this->config->getParticipations($storeId);
            $participation = $participations[$procedure] ?? '';

            $billingNumber = $ekp . $procedure . $participation;
        }

        $requestBuilder->setShipperAccount($billingNumber);
        $requestBuilder->setShipperAddress(
            'Netresearch DTT GmbH',
            $countryCode,
            '04229',
            'Leipzig',
            'Nonnenstraße',
            '11 c'
        );
        $requestBuilder->setRecipientAddress(
            'John Doe',
            $countryCode,
            '53113',
            'Bonn',
            'Charles-de-Gaulle-Straße',
            '20'
        );
        $requestBuilder->setShipmentDetails($productCode, new \DateTime(date('Y-m-d', $tsShip)));
        $requestBuilder->setPackageDetails(2.4);

        return $requestBuilder->create();
    }

    public function execute(int $storeId): ResultInterface
    {
        try {
            $apiRequest = $this->createRequest($storeId);
        } catch (RequestValidatorException $exception) {
            $message = __('Invalid request: %1', $exception->getMessage());
            return $this->createResult(ResultInterface::ERROR, $message);
        }

        $shipmentService = $this->shipmentServiceFactory->create(['storeId' => $storeId]);
        $orderConfig = $this->orderConfigFactory->create(['storeId' => $storeId]);

        try {
            $shipmentService->validateShipments([$apiRequest], $orderConfig);

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
