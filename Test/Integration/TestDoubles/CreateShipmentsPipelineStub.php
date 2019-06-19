<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDoubles;

use Dhl\Paket\Webservice\CreateShipmentsPipeline;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Dhl\Sdk\Paket\Bcs\Api\ShipmentServiceInterface;
use Dhl\Sdk\Paket\Bcs\Service\ShipmentService\Shipment;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\LabelResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Dhl\ShippingCore\Api\RequestValidatorInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class CreateShipmentsPipelineStub
 *
 * Set prepared response to shipment service.
 *
 * @package Dhl\Ecommerce\Test\Integration\TestDouble
 */
class CreateShipmentsPipelineStub extends CreateShipmentsPipeline
{
    /**
     * @var ShipmentServiceStub
     */
    private $shipmentService;

    /**
     * Core shipment requests.
     *
     * @var Request[]
     */
    private $shipmentRequests = [];

    /**
     * Pipeline constructor.
     *
     * @param RequestValidatorInterface $requestValidator
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param ShipmentServiceInterface $shipmentService
     * @param LabelResponseInterfaceFactory $shipmentResponseFactory
     * @param ShipmentErrorResponseInterfaceFactory $errorResponseFactory
     * @param Request[] $shipmentRequests
     */
    public function __construct(
        RequestValidatorInterface $requestValidator,
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        ShipmentServiceInterface $shipmentService,
        LabelResponseInterfaceFactory $shipmentResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory,
        array $shipmentRequests
    ) {
        $this->shipmentService = $shipmentService;
        $this->shipmentRequests = $shipmentRequests;

        parent::__construct(
            $requestValidator,
            $requestDataMapper,
            $responseDataMapper,
            $shipmentService,
            $shipmentResponseFactory,
            $errorResponseFactory,
            $shipmentRequests
        );
    }

    /**
     * @return string
     */
    private function getLabelPdf(): string
    {
        return <<<'B64'
JVBERi0xLjUKJbXtrvsKMyAwIG9iago8PCAvTGVuZ3RoIDQgMCBSCiAgIC9GaWx0ZXIgL0ZsYXRl
RGVjb2RlCj4+CnN0cmVhbQp4nCvkCuQCAAKSANcKZW5kc3RyZWFtCmVuZG9iago0IDAgb2JqCiAg
IDEyCmVuZG9iagoyIDAgb2JqCjw8Cj4+CmVuZG9iago1IDAgb2JqCjw8IC9UeXBlIC9QYWdlCiAg
IC9QYXJlbnQgMSAwIFIKICAgL01lZGlhQm94IFsgMCAwIDEwNCAxNDcgXQogICAvQ29udGVudHMg
MyAwIFIKICAgL0dyb3VwIDw8CiAgICAgIC9UeXBlIC9Hcm91cAogICAgICAvUyAvVHJhbnNwYXJl
bmN5CiAgICAgIC9DUyAvRGV2aWNlUkdCCiAgID4+CiAgIC9SZXNvdXJjZXMgMiAwIFIKPj4KZW5k
b2JqCjEgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzCiAgIC9LaWRzIFsgNSAwIFIgXQogICAvQ291bnQg
MQo+PgplbmRvYmoKNiAwIG9iago8PCAvQ3JlYXRvciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWly
b2dyYXBoaWNzLm9yZykpCiAgIC9Qcm9kdWNlciAoY2Fpcm8gMS45LjUgKGh0dHA6Ly9jYWlyb2dy
YXBoaWNzLm9yZykpCj4+CmVuZG9iago3IDAgb2JqCjw8IC9UeXBlIC9DYXRhbG9nCiAgIC9QYWdl
cyAxIDAgUgo+PgplbmRvYmoKeHJlZgowIDgKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMzQ2
IDAwMDAwIG4gCjAwMDAwMDAxMjUgMDAwMDAgbiAKMDAwMDAwMDAxNSAwMDAwMCBuIAowMDAwMDAw
MTA0IDAwMDAwIG4gCjAwMDAwMDAxNDYgMDAwMDAgbiAKMDAwMDAwMDQxMSAwMDAwMCBuIAowMDAw
MDAwNTM2IDAwMDAwIG4gCnRyYWlsZXIKPDwgL1NpemUgOAogICAvUm9vdCA3IDAgUgogICAvSW5m
byA2IDAgUgo+PgpzdGFydHhyZWYKNTg4CiUlRU9GCg==
B64;
    }

    /**
     * Send label request objects to shipment service.
     *
     * Set created labels prior to sending the request.
     *
     * @return CreateShipmentsPipeline
     */
    public function send()
    {
        $apiResponses = [];
        $pdf = $this->getLabelPdf();

        foreach ($this->shipmentRequests as $shipmentRequest) {
            foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
                if (isset($package['sequence_number'])) {
                    $orderId = $shipmentRequest->getOrderShipment()->getOrderId();
                    $sequenceNumber = $package['sequence_number'];
                    $apiResponses[]= new Shipment(
                        (string) $package['sequence_number'],
                        "{$orderId}-{$sequenceNumber}",
                        '',
                        $pdf,
                        '',
                        '',
                        ''
                    );
                }
            }
        }

        $this->shipmentService->setCreatedShipments($apiResponses);

        return parent::send();
    }
}
