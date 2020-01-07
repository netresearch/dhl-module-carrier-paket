<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture;

use Dhl\ShippingCore\Test\Integration\Fixture\Data\AddressInterface;
use Dhl\ShippingCore\Test\Integration\Fixture\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Checkout\CustomerCheckout;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;

/**
 * Class QuoteFixture
 *
 * @package Dhl\Paket\Test\Integration
 */
class QuoteFixture
{
    private static $createdEntities = [
        'products' => [],
        'customers' => [],
        'orders' => [],
    ];

    /**
     * @param AddressInterface $recipientData
     * @param ProductInterface[] $productData
     * @param string $carrierCode
     * @return CartInterface
     * @throws \Exception
     */
    public static function createQuote(
        AddressInterface $recipientData,
        array $productData,
        string $carrierCode
    ): CartInterface {
        // set up logged-in customer
        $shippingAddressBuilder = AddressBuilder::anAddress()
            ->withFirstname('François')
            ->withLastname('Češković')
            ->withCompany(null)
            ->withCountryId($recipientData->getCountryId())
            ->withRegionId($recipientData->getRegionId())
            ->withCity($recipientData->getCity())
            ->withPostcode($recipientData->getPostcode())
            ->withStreet($recipientData->getStreet());

        $customer = CustomerBuilder::aCustomer()
            ->withFirstname('François')
            ->withLastname('Češković')
            ->withAddresses(
                $shippingAddressBuilder->asDefaultBilling(),
                $shippingAddressBuilder->asDefaultShipping()
            )
            ->build();

        self::$createdEntities['customers'][] = $customer;
        $customerFixture = new CustomerFixture($customer);
        $customerFixture->login();

        // create quote
        $cartBuilder = CartBuilder::forCurrentSession();
        $cartBuilder = self::addProductsToCart($productData, $cartBuilder);
        $cart = $cartBuilder->build();

        CustomerCheckout::fromCart($cart)->withShippingMethodCode($carrierCode);

        /**
         * This is needed since we do not place the order,
         * thus we would not have a assigned shipping address on the quote
         */
        $objectManager = Bootstrap::getObjectManager();
        $addressRepository = $objectManager->create(AddressRepositoryInterface::class);
        $shippingAddress = $cart->getQuote()->getShippingAddress();
        $shippingAddress->setShippingMethod($carrierCode);
        $addressId = $cart->getCustomerSession()->getCustomer()->getDefaultBillingAddress()->getId();
        $shippingAddress->importCustomerAddressData($addressRepository->getById($addressId));

        $quote = $cart->getQuote();
        self::$createdEntities['quotes'][] = $quote;

        return $quote;
    }

    /**
     * @param ProductInterface[] $productData
     * @param CartBuilder $cartBuilder
     * @return CartBuilder
     * @throws \Exception
     */
    private static function addProductsToCart(array $productData, CartBuilder $cartBuilder): CartBuilder
    {
        foreach ($productData as $productDatum) {
            if ($productDatum->getType() === Type::TYPE_SIMPLE) {
                // set up product
                $productBuilder = ProductBuilder::aSimpleProduct();
                $productBuilder = $productBuilder
                    ->withSku($productDatum->getSku())
                    ->withPrice($productDatum->getPrice())
                    ->withWeight($productDatum->getWeight())
                    ->withName($productDatum->getDescription());

                $product = $productBuilder->build();

                self::$createdEntities['products'][] = $product;
                $productFixture = new ProductFixture($product);
                $cartBuilder = $cartBuilder->withSimpleProduct(
                    $productFixture->getSku(),
                    $productDatum->getCheckoutQty()
                );
            } else {
                throw new \InvalidArgumentException('Only simple product data fixtures are currently supported.');
            }
        }

        return $cartBuilder;
    }

    /**
     * Rollback for created quote, customer and product entities
     *
     * @throws LocalizedException
     */
    public static function rollbackFixtureEntities()
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var CartRepositoryInterface $quoteRepository */
        $quoteRepository = $objectManager->get(CartRepositoryInterface::class);
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $objectManager->get(ProductRepositoryInterface::class);
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $objectManager->get(CustomerRepositoryInterface::class);

        /** @var CartInterface $quote */
        foreach (self::$createdEntities['quotes'] as $quote) {
            $quoteRepository->delete($quote);
        }
        self::$createdEntities['quotes'] = [];

        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        foreach (self::$createdEntities['products'] as $product) {
            $productRepository->delete($product);
        }
        self::$createdEntities['products'] = [];

        /** @var CustomerInterface $customer */
        foreach (self::$createdEntities['customers'] as $customer) {
            $customerRepository->delete($customer);
        }
        self::$createdEntities['customers'] = [];
    }
}
