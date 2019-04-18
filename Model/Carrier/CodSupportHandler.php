<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\ShippingCore\Api\CodSupportInterface;
use Dhl\ShippingCore\Model\Config\CoreConfig;
use Magento\Quote\Model\Quote;

/**
 * Class CodSupportHandler
 *
 * @package Dhl\Paket\Model\Carrier
 * @author Paul Siedler <paul.siedler@netresearch.de>
 * @link https://www.netresearch.de/
 */
class CodSupportHandler implements CodSupportInterface
{
    /**
     * @var CoreConfig
     */
    private $config;

    /**
     * CodSupportHandler constructor.
     *
     * @param CoreConfig $config
     */
    public function __construct(CoreConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Determines if a carrier has support for Cash on Delivery payment methods.
     *
     * DHL Paket conditions for allowing cash on delivery payment comprise:
     * - shipment is domestic (DE-DE or AT-AT)
     * - preferredLocation or preferredNeighbour value-added services are not chosen for the given quote
     *
     * Note: No need to validate origin country. Paket carrier is only available for DE or AT origin checkouts anyway.
     *
     * @param Quote $quote
     * @return bool
     */
    public function hasCodSupport(Quote $quote): bool
    {
        $originCountryId = $this->config->getOriginCountry($quote->getStoreId());
        $destCountryId = $quote->getShippingAddress()->getCountryId();
        $isDomestic = ($originCountryId === $destCountryId);

        //todo(nr): Handle preferredLocation and preferredNeighbour when selectable in checkout
        $hasCodIncompatibleServices = false;

        return $isDomestic && !$hasCodIncompatibleServices;
    }
}
