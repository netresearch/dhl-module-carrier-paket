<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Controller\Adminhtml\Shipment;

use Dhl\Paket\Model\Shipment\CancelRequestBuilder;
use Dhl\Paket\Model\ShipmentManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
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
    private $cancelRequestBuilder;

    /**
     * @var ShipmentManagement
     */
    private $shipmentManagement;

    /**
     * Cancel constructor.
     *
     * @param Context $context
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param CancelRequestBuilder $cancelRequestBuilder
     * @param ShipmentManagement $shipmentManagement
     */
    public function __construct(
        Context $context,
        ShipmentRepositoryInterface $shipmentRepository,
        CancelRequestBuilder $cancelRequestBuilder,
        ShipmentManagement $shipmentManagement
    ) {
        $this->shipmentRepository = $shipmentRepository;
        $this->cancelRequestBuilder = $cancelRequestBuilder;
        $this->shipmentManagement = $shipmentManagement;

        parent::__construct($context);
    }

    /**
     * Cancel shipment order, delete tracks and shipping label.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $shipmentId = (int) $this->getRequest()->getParam('shipment_id');

        try {
            $shipment = $this->shipmentRepository->get($shipmentId);

            $this->cancelRequestBuilder->setShipment($shipment);
            $cancelRequests = $this->cancelRequestBuilder->build();
            $this->shipmentManagement->cancelLabels($cancelRequests);

            $this->messageManager->addSuccessMessage(__('The shipment order was successfully cancelled.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addExceptionMessage($exception, __('The shipment order could not be cancelled.'));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('adminhtml/order_shipment/view', ['shipment_id' => $shipmentId]);
    }
}
