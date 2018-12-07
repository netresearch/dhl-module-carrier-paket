<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Dhl\ShippingCore\Api\RateRequestEmulationInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class Paket extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'dhlpaket';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var RateRequestEmulationInterface
     */
    private $rateRequestService;

    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    public function collectRates(RateRequest $request)
    {
        return $this->rateRequestService->emulateRateRequest(
            $this->moduleConfig->getEmulatedCarrier($request->getStoreId()),
            $request
        );
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     * @api
     */
    public function getAllowedMethods()
    {
        return [];
    }

    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        throw new \RuntimeException('Not yet implemented');
    }
}
