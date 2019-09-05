<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Checkout\DataProcessor\ServiceOptions;

use Dhl\Paket\Model\Checkout\DataProcessor\CurrencyService;
use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ProcessorInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\InputInterface;
use Dhl\ShippingCore\Api\Data\ShippingOption\ShippingOptionInterface;
use Dhl\ShippingCore\Model\Checkout\DataProcessor\ShippingOptionsProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Zend_Currency_Exception;

/**
 * Class AdditionalChargesProcessor
 *
 * @package Dhl\Paket\Model\Checkout\DataProcessor
 * @author Max Melzer <max.melzer@netresearch.de>
 * @author Rico Sonntag <rico.sonntag@netresearch.de>
 */
class AdditionalChargesProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ModuleConfig
     */
    private $paketConfig;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AdditionalChargesProcessor constructor.
     *
     * @param ModuleConfig $paketConfig
     * @param CurrencyService $currencyService
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleConfig $paketConfig,
        CurrencyService $currencyService,
        LoggerInterface $logger
    ) {
        $this->paketConfig = $paketConfig;
        $this->currencyService = $currencyService;
        $this->logger = $logger;
    }

    /**
     * Apply the amount to the input comment or remove the comment if the amount is 0.
     *
     * @param float $amount
     * @param InputInterface $date
     * @param int|null $storeId
     */
    private function apply(float $amount, InputInterface $date, int $storeId = null)
    {
        $comment = $date->getComment();

        if (!$comment) {
            return;
        }

        if ($amount > 0.0) {
            $comment->setContent(
                $this->currencyService->replaceAmount($amount, $comment->getContent(), $storeId)
            );
        } else {
            $date->setComment(null);
        }
    }

    /**
     * @param ShippingOptionInterface[] $optionsData
     * @param string $countryCode Destination country code
     * @param string $postalCode Destination postal code
     * @param int|null $storeId
     *
     * @return ShippingOptionInterface[]
     */
    public function process(
        array $optionsData,
        string $countryCode,
        string $postalCode,
        int $storeId = null
    ): array {
        $preferredDay = $optionsData[ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_DAY] ?? false;
        if ($preferredDay) {
            $date = $preferredDay->getInputs()['date'] ?? false;
            if ($date) {
                $amount = $this->paketConfig->getPreferredDayAdditionalCharge();
                $this->apply($amount, $date, $storeId);
            }
        }

        $preferredTime = $optionsData[ProcessorInterface::CHECKOUT_SERVICE_PREFERRED_TIME] ?? false;
        if ($preferredTime) {
            $time = $preferredTime->getInputs()['time'] ?? false;
            if ($time) {
                $amount = $this->paketConfig->getPreferredTimeAdditionalCharge();
                $this->apply($amount, $time, $storeId);
            }
        }

        return $optionsData;
    }
}
