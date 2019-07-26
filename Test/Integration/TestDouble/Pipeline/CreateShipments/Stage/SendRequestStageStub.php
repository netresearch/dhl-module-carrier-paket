<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestDouble\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Webservice\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\Paket\Webservice\Pipeline\CreateShipments\Stage\SendRequestStage;
use Dhl\Sdk\Paket\Bcs\Api\Data\ShipmentInterface;
use Dhl\Sdk\Paket\Bcs\Exception\ServiceException;
use Dhl\Sdk\Paket\Bcs\Service\ShipmentService\Shipment;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class SendRequestStageStub
 *
 * @package Dhl\Paket\Test\Integration\TestDouble
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class SendRequestStageStub extends SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * API request objects passed to the stage. Can be used for assertions.
     *
     * @var \object[]
     */
    public $apiRequests = [];

    /**
     * Regular API responses. Built during runtime from the given shipment requests.
     *
     * @var ShipmentInterface[]
     */
    public $apiResponses = [];

    /**
     * Service exception. Can be set to make the request fail.
     *
     * @var ServiceException
     */
    public $exception;

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
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $this->apiRequests = $artifactsContainer->getApiRequests();

        $pdf = $this->getLabelPdf();

        foreach ($requests as $shipmentRequest) {
            foreach ($shipmentRequest->getData('packages') as $packageId => $package) {
                if (isset($package['sequence_number'])) {
                    $orderId = $shipmentRequest->getOrderShipment()->getOrderId();
                    $sequenceNumber = (string) $package['sequence_number'];

                    $this->apiResponses[$sequenceNumber] = new Shipment(
                        $sequenceNumber,
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

        return parent::execute($requests, $artifactsContainer);
    }
}
