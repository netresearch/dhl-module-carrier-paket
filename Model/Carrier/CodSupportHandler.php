<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\ShippingCore\Api\CodSupportInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\Selection\AssignedSelectionInterface;
use Dhl\ShippingCore\Model\Config\CoreConfig;
use Dhl\ShippingCore\Model\ShippingOption\Selection\QuoteSelectionRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
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
     * @var QuoteSelectionRepository
     */
    private $quoteSelectionRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * CodSupportHandler constructor.
     *
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param FilterBuilder $filterBuilder
     * @param QuoteSelectionRepository $quoteSelectionRepository
     * @param CoreConfig $config
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        QuoteSelectionRepository $quoteSelectionRepository,
        CoreConfig $config
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder                = $filterBuilder;
        $this->quoteSelectionRepository     = $quoteSelectionRepository;
        $this->config                       = $config;
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
     *
     * @return bool
     */
    public function hasCodSupport(Quote $quote): bool
    {
        return $this->isDomesticShipment($quote)
            && !$this->hasCodIncompatibleServices($quote);
    }

    /**
     * Returns TRUE if the shipment is a domestic shipment (DE-DE or AT-AT).
     *
     * @param Quote $quote
     *
     * @return bool
     */
    private function isDomesticShipment(Quote $quote): bool
    {
        $originCountryId = $this->config->getOriginCountry($quote->getStoreId());
        $destCountryId   = $quote->getShippingAddress()->getCountryId();

        return $originCountryId === $destCountryId;
    }

    /**
     * Returns TRUE if a COD incompatible service is used.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    private function hasCodIncompatibleServices(Quote $quote): bool
    {
        $parentIdFilter = $this->filterBuilder
            ->setField(AssignedSelectionInterface::PARENT_ID)
            ->setConditionType('eq')
            ->setValue($quote->getShippingAddress()->getId())
            ->create();

        $optionCodeFilter = $this->filterBuilder
            ->setField(AssignedSelectionInterface::SHIPPING_OPTION_CODE)
            ->setConditionType('in')
            ->setValue(implode(',', ['preferredLocation', 'preferredNeighbour']))
            ->create();

        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter($parentIdFilter)
            ->addFilter($optionCodeFilter)
            ->create();

        return (bool) $this->quoteSelectionRepository
            ->getList($searchCriteria)
            ->count();
    }
}
