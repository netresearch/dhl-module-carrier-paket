<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Model\Carrier;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes as ServiceCodes;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Quote\Model\Quote;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Netresearch\ShippingCore\Api\PaymentMethod\MethodAvailabilityInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\CodSelectorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;

class CodSupportHandler implements MethodAvailabilityInterface, CodSelectorInterface
{
    /**
     * @var ShippingConfigInterface
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

    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        FilterBuilder $filterBuilder,
        QuoteSelectionRepository $quoteSelectionRepository,
        ShippingConfigInterface $config
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->filterBuilder                = $filterBuilder;
        $this->quoteSelectionRepository     = $quoteSelectionRepository;
        $this->config                       = $config;
    }

    /**
     * Returns TRUE if the shipment is a domestic shipment (DE-DE).
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
            ->setValue(
                implode(
                    ',',
                    [
                        ServiceCodes::SERVICE_OPTION_DROPOFF_DELIVERY,
                        ServiceCodes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
                        Codes::SERVICE_OPTION_DELIVERY_LOCATION,
                    ]
                )
            )
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

    /**
     * Determines if a carrier has support for Cash on Delivery payment methods.
     *
     * DHL Paket conditions for allowing cash on delivery payment comprise:
     * - shipment is domestic (DE-DE)
     * - value-added services "neighbor delivery" and "parcel drop-off" are not chosen for the given quote
     *
     * Note: No need to validate origin country. Paket carrier is only available for DE origin checkouts anyway.
     *
     * @param Quote $quote
     *
     * @return bool
     */
    #[\Override]
    public function isAvailable(Quote $quote): bool
    {
        return $this->isDomesticShipment($quote)
            && !$this->hasCodIncompatibleServices($quote);
    }

    /**
     * Add Cash on Delivery service data to the selection model.
     *
     * @param AssignedSelectionInterface $selection
     */
    #[\Override]
    public function assignCodSelection(AssignedSelectionInterface $selection)
    {
        $selection->setShippingOptionCode(Codes::SERVICE_OPTION_CASH_ON_DELIVERY);
        $selection->setInputCode('enabled');
        $selection->setInputValue((string) true);
    }
}
