<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Provider;

/**
 * Class CreateLabelTestProvider
 *
 * @package Dhl\Paket\Test\Integration
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class CreateLabelTestProvider
{
    /**
     * Provide request and response for the test case
     * - shipment(s) sent to the API, all label(s) successfully booked.
     *
     * @return string
     */
    public static function getLabelPdf(): string
    {
        return \file_get_contents(__DIR__ . '/_files/CreateLabel/minimal.pdf');
    }
}
