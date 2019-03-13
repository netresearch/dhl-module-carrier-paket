<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Webservice\Shipment\ErrorHandler;
use Dhl\Paket\Webservice\Shipment\RequestDataMapper;
use Dhl\Paket\Webservice\Shipment\ResponseDataMapper;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Shipment\Request;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritDoc
 */
class ShipmentAdapter implements ShipmentAdapterInterface
{
    /**
     * @var RequestDataMapper
     */
    private $requestDataMapper;

    /**
     * @var ResponseDataMapper;
     */
    private $responseDataMapper;

    /**
     * @var ShipmentClientInterface
     */
    private $client;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * ShipmentAdapter constructor.
     *
     * @param RequestDataMapper $requestDataMapper
     * @param ResponseDataMapper $responseDataMapper
     * @param ShipmentClientInterface $client
     * @param StoreManagerInterface $storeManager
     * @param ErrorHandler $errorHandler
     */
    public function __construct(
        RequestDataMapper $requestDataMapper,
        ResponseDataMapper $responseDataMapper,
        ShipmentClientInterface $client,
        StoreManagerInterface $storeManager,
        ErrorHandler $errorHandler
    ) {
        $this->requestDataMapper  = $requestDataMapper;
        $this->responseDataMapper = $responseDataMapper;
        $this->client             = $client;
        $this->storeManager       = $storeManager;
        $this->errorHandler       = $errorHandler;
    }

    /**
     * @inheritDoc
     */
    public function getShipmentLabel(Request $request): DataObject
    {
        try {
            $store         = $this->storeManager->getStore($request->getStoreId());
            $mappedRequest = $this->requestDataMapper->mapRequest($request);
            $response      = $this->client->performShipmentOrderRequest($mappedRequest, $store);
            $result        = $this->responseDataMapper->mapResult($response);
        } catch (\Exception $ex) {
            //@todo(nr) implement proper exception handling
            $result = $this->errorHandler->createErrorResult($ex);
        }

        return $result;
    }
}
