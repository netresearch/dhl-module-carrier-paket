<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Tracking;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Shipping\Model\Tracking\Result;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;

/**
 * Class TrackingInfoProvider
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class TrackingInfoProvider
{
    const TRACKING_PORTAL_URL = 'https://nolp.dhl.de/nextt-online-public/set_identcodes.do?lang=de&idc=';
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * TrackingInfoProvider constructor.
     *
     * @param ResultFactory         $trackingResultFactory
     * @param StatusFactory         $trackingStatusFactory
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        ResultFactory $trackingResultFactory,
        StatusFactory $trackingStatusFactory,
        ModuleConfig $moduleConfig
    ) {
        $this->resultFactory = $trackingResultFactory;
        $this->statusFactory = $trackingStatusFactory;
        $this->moduleConfig  = $moduleConfig;
    }

    /**
     * Returns the tracking information.
     *
     * @param string $trackingNumber
     *
     * @return Result
     */
    public function getTrackingInfo(string $trackingNumber): Result
    {
        $result = $this->resultFactory->create();
        $result->append(
            $this->statusFactory->create([
               'data' => [
                   'tracking'      => $trackingNumber,
                   'carrier_title' => $this->moduleConfig->getTitle(),
                   'url' => self::TRACKING_PORTAL_URL . $trackingNumber
               ],
            ])
        );

        return $result;
    }
}
