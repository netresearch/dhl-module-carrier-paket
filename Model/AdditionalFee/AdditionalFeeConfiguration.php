<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\AdditionalFee;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Api\AdditionalFee\AdditionalFeeConfigurationInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Dhl\ShippingCore\Model\ResourceModel\Quote\Address\ShippingOptionSelectionCollection;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilderFactory;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote;

/**
 * Class AdditionalFeeConfiguration
 *
 */
class AdditionalFeeConfiguration implements AdditionalFeeConfigurationInterface
{
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
     * @var ShippingOptionSelectionCollection | null
     */
    private $serviceSelection = null;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * AdditionalFeeConfiguration constructor.
     *
     * @param QuoteSelectionRepository $quoteSelectionRepository
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param ModuleConfig $config
     */
    public function __construct(
        QuoteSelectionRepository $quoteSelectionRepository,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ModuleConfig $config
    ) {
        $this->quoteSelectionRepository = $quoteSelectionRepository;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    /**
     * @param Quote $quote
     * @return bool
     */
    public function isActive(Quote $quote): bool
    {
        return $this->getServiceCharge($quote) !== 0.0;
    }

    /**
     * @param Quote $quote
     * @return float
     */
    public function getServiceCharge(Quote $quote): float
    {
        $fee = 0.0;
        $serviceSelections = $this->getPreferredDaySelection($quote);

        /** @var SelectionInterface $selectedService */
        $selectedService = $serviceSelections->getFirstItem();
        if ($selectedService->getShippingOptionCode() === Codes::CHECKOUT_SERVICE_PREFERRED_DAY) {
            $fee = $this->config->getPreferredDayAdditionalCharge($quote->getStoreId());
        }

        return $fee;
    }

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('DHL Preferred Delivery');
    }

    /**
     * @param Quote $quote
     * @return ShippingOptionSelectionCollection
     */
    private function getPreferredDaySelection(Quote $quote): ShippingOptionSelectionCollection
    {
        if ($this->serviceSelection === null) {
            $addressFilter = $this->filterBuilder
                ->setField(AssignedSelectionInterface::PARENT_ID)
                ->setValue($quote->getShippingAddress()->getId())
                ->setConditionType('eq')
                ->create();
            $optionCodeFilter = $this->filterBuilder
                ->setField(SelectionInterface::SHIPPING_OPTION_CODE)
                ->setValue(Codes::CHECKOUT_SERVICE_PREFERRED_DAY)
                ->setConditionType('eq')
                ->create();

            $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
            $searchCriteria = $searchCriteriaBuilder
                ->addFilter($addressFilter)
                ->addFilter($optionCodeFilter)
                ->create();

            $this->serviceSelection = $this->quoteSelectionRepository->getList($searchCriteria);
        }

        return $this->serviceSelection;
    }
}
