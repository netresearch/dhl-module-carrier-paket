<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture;

use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\AssignedSelectionInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelection;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class QuoteServiceSelectionFixture
 *
 */
class QuoteServiceSelectionFixture
{
    /**
     * @var QuoteSelection[][]
     */
    private static $createdEntities = ['selections' => []];

    /**
     * @param CartInterface|Quote $quote
     * @param string $inputCode
     * @param string $shippingOptionCode
     * @param string $inputValue
     * @return QuoteSelection
     * @throws CouldNotSaveException
     */
    public static function createServiceSelection(
        CartInterface $quote,
        string $inputCode,
        string $shippingOptionCode,
        string $inputValue
    ): QuoteSelection {
        $objectManager = Bootstrap::getObjectManager();
        /** @var QuoteSelectionRepository $repository */
        $repository = $objectManager->get(QuoteSelectionRepository::class);
        /** @var QuoteSelection $selectionModel */
        $selectionModel = $objectManager->get(QuoteSelection::class);
        $selectionModel->setData([
            AssignedSelectionInterface::PARENT_ID => (int) $quote->getShippingAddress()->getId(),
            AssignedSelectionInterface::SHIPPING_OPTION_CODE => $shippingOptionCode,
            AssignedSelectionInterface::INPUT_CODE => $inputCode,
            AssignedSelectionInterface::INPUT_VALUE => $inputValue
        ]);

        $quoteSelection = $repository->save($selectionModel);

        self::$createdEntities['selections'][] = $quoteSelection;

        return $quoteSelection;
    }

    /**
     * Rollback for created quote service selection.
     *
     * @throws CouldNotDeleteException
     */
    public static function rollbackFixtureEntities()
    {
        /** @var QuoteSelectionRepository $repository */
        $repository = Bootstrap::getObjectManager()->get(QuoteSelectionRepository::class);

        /** @var CartInterface $quote */
        foreach (self::$createdEntities['selections'] as $selection) {
            $repository->delete($selection);
        }

        self::$createdEntities['selections'] = [];
    }
}
