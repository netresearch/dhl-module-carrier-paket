<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Tracking;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Magento\Shipping\Model\Tracking\Result;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Shipping\Model\Tracking\ResultFactory;

/**
 * Class TrackingInfoProvider
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link    https://www.netresearch.de/
 */
class TrackingInfoProvider
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * TrackingInfoProvider constructor.
     *
     * @param ResultFactory         $trackingResultFactory
     * @param StatusFactory         $trackingStatusFactory
     * @param ModuleConfigInterface $moduleConfig
     */
    public function __construct(
        ResultFactory $trackingResultFactory,
        StatusFactory $trackingStatusFactory,
        ModuleConfigInterface $moduleConfig
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
               ],
           ])
        );

        return $result;
    }
}
