<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Controller\Adminhtml\Shipment;

use Dhl\Paket\Model\Shipment\CancelRequestBuilder;
use Dhl\Paket\Model\ShipmentManagement;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\ShipmentRepositoryInterface;

/**
 * Class Cancel
 *
 * @package Dhl\Paket\Controller
 */
class Cancel extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::ship';

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var CancelRequestBuilder
     */
    private $requestBuilder;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * Cancel constructor.
     *
     * @param Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CancelRequestBuilder $requestBuilder
     * @param ShipmentManagement $shipmentManagement
     */
    public function __construct(
        Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        CancelRequestBuilder $requestBuilder,
        ShipmentManagement $shipmentManagement
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->requestBuilder = $requestBuilder;
        $this->shipmentManagement = $shipmentManagement;

        parent::__construct($context);
    }

    /**
     * Cancel shipment order, delete tracks and shipping label.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $shipmentId = (int) $this->getRequest()->getParam('shipment_id');

        try {
            $shipment = $this->shipmentRepository->get($shipmentId);
        } catch (LocalizedException $exception) {
            $this->messageManager->addExceptionMessage($exception, __('The shipment %1 could not be loaded.', $shipmentId));

            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('sales/shipment/index');
        }

        $this->requestBuilder->setShipments([$shipment]);
        $cancelRequests = $this->requestBuilder->build();
        $result = $this->shipmentManagement->cancelLabels($cancelRequests);

        $result = array_reduce($result, function (array $result, TrackResponseInterface $trackResponse) {
            if ($trackResponse instanceof TrackErrorResponseInterface) {
                $result['error'][] = $trackResponse->getErrors();
            } else {
                $result['success'][] = $trackResponse->getTrackNumber();
            }

            return $result;
        }, ['success' => [], 'error' => []]);

        if (!empty($result['success'])) {
            $this->messageManager->addSuccessMessage(
                __('The shipment order(s) %1 were successfully cancelled.', implode(', ', $result['success']))
            );
        }

        foreach ($result['error'] as $error) {
            $this->messageManager->addErrorMessage($error);
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);
    }
}
