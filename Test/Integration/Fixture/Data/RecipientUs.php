<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Fixture\Data;

/**
 * Class RecipientUs
 *
 * @package Dhl\Test\Integration\Fixture
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class RecipientUs implements RecipientInterface
{
    public function getStreet(): string
    {
        return '3131 S Las Vegas Blvd';
    }

    public function getCity(): string
    {
        return 'Las Vegas';
    }

    public function getPostcode(): string
    {
        return '89109';
    }

    public function getCountryId(): string
    {
        return 'US';
    }

    public function getRegionId(): string
    {
        return 'NV';
    }
}
