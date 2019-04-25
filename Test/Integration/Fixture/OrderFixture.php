<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture;

use TddWizard\Fixtures\Catalog\ProductBuilder;
use TddWizard\Fixtures\Catalog\ProductFixture;
use TddWizard\Fixtures\Checkout\CartBuilder;
use TddWizard\Fixtures\Checkout\CustomerCheckout;
use TddWizard\Fixtures\Customer\AddressBuilder;
use TddWizard\Fixtures\Customer\CustomerBuilder;
use TddWizard\Fixtures\Customer\CustomerFixture;

/**
 * Class OrderFixture
 *
 * @package Dhl\Test\Integration\Fixture
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class OrderFixture
{
    /**
     * @param string $recipientStreet
     * @param string $recipientCity
     * @param string $recipientPostCode
     * @param string $recipientCountryId
     * @param int $recipientRegionId
     * @param string[] $productAttributes
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Exception
     */
    public static function createPaketOrderWithSimpleProduct(
        string $recipientStreet,
        string $recipientCity,
        string $recipientPostCode,
        string $recipientCountryId,
        int $recipientRegionId,
        array $productAttributes = []
    ) {
        // set up product
        $product = ProductBuilder::aSimpleProduct()
            ->withPrice(45.0)
            ->withWeight(1.0)
            ->withCustomAttributes($productAttributes)
            ->build();

        $productFixture = new ProductFixture($product);

        // set up logged-in customer
        $shippingAddressBuilder = AddressBuilder::anAddress()
            ->withFirstname('John')
            ->withLastname('Doe')
            ->withCompany(null)
            ->withCountryId($recipientCountryId)
            ->withRegionId($recipientRegionId)
            ->withCity($recipientCity)
            ->withPostcode($recipientPostCode)
            ->withStreet($recipientStreet);

        $customer = CustomerBuilder::aCustomer()
            ->withFirstname('John')
            ->withLastname('Doe')
            ->withAddresses(
                $shippingAddressBuilder->asDefaultBilling(),
                $shippingAddressBuilder->asDefaultShipping()
            )
            ->build();

        $customerFixture = new CustomerFixture($customer);
        $customerFixture->login();

        // place order
        $cart = CartBuilder::forCurrentSession()
            ->withSimpleProduct($productFixture->getSku())
            ->build();

        $checkout = CustomerCheckout::fromCart($cart);

        $order = $checkout
            ->withShippingMethodCode('dhlpaket_flatrate')
            ->placeOrder();

        return $order;
    }
}
