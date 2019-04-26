<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture;

use Dhl\Paket\Test\Integration\Fixture\Data\ProductInterface;
use Dhl\Paket\Test\Integration\Fixture\Data\RecipientInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Sales\Api\Data\OrderInterface;
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
     * @param RecipientInterface $recipientData
     * @param ProductInterface $productData
     * @return OrderInterface
     * @throws \Exception
     */
    public static function createPaketOrder(
        RecipientInterface $recipientData,
        ProductInterface $productData
    ) {
        if ($productData->getType() === Type::TYPE_SIMPLE) {
            // set up product
            $productBuilder = ProductBuilder::aSimpleProduct();
            $productBuilder = $productBuilder
                ->withSku($productData->getSku())
                ->withPrice($productData->getPrice())
                ->withWeight($productData->getWeight())
                ->withCustomAttributes($productData->getCustomAttributes());
            $product = $productBuilder->build();

            $productFixture = new ProductFixture($product);
        } else {
            throw new \InvalidArgumentException('Only simple product data fixtures are currently supported.');
        }

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

        $customerFixture = new CustomerFixture($customer);
        $customerFixture->login();

        // place order
        $cart = CartBuilder::forCurrentSession()
            ->withSimpleProduct($productFixture->getSku(), $productData->getCheckoutQty())
            ->build();

        $checkout = CustomerCheckout::fromCart($cart);

        $order = $checkout
            ->withShippingMethodCode('dhlpaket_flatrate')
            ->placeOrder();

        return $order;
    }
}
