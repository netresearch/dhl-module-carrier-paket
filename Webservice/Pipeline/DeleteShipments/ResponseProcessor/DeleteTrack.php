<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Pipeline\DeleteShipments\ResponseProcessor;

use Dhl\ShippingCore\Api\Data\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\TrackResponseProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Sales\Model\Order\Shipment\TrackRepository;
use Psr\Log\LoggerInterface;

/**
 * Class DeleteTrack
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class DeleteTrack implements TrackResponseProcessorInterface
{
    /**
     * @var TrackRepository
     */
    private $trackRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DeleteTrack constructor.
     *
     * @param TrackRepository $trackRepository
     * @param LoggerInterface $logger
     */
    public function __construct(TrackRepository $trackRepository, LoggerInterface $logger)
    {
        $this->trackRepository = $trackRepository;
        $this->logger = $logger;
    }

    /**
     * Delete track entities for successfully cancelled shipment numbers.
     *
     * There is not necessarily a track persisted for a given shipment number.
     *
     * @param TrackResponseInterface[] $trackResponses Shipment cancellation responses
     * @param TrackErrorResponseInterface[] $errorResponses Shipment cancellation errors
     * @see \Magento\Shipping\Model\Carrier\AbstractCarrierOnline::rollBack
     *
     */
    public function processResponse(array $trackResponses, array $errorResponses)
    {
        foreach ($trackResponses as $trackResponse) {
            $track = $trackResponse->getSalesTrack();
            if ($track === null) {
                continue;
            }

            try {
                $this->trackRepository->delete($track);
            } catch (CouldNotDeleteException $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }
}
