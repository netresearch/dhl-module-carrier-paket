<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\Provider\Model\Util;

class ApiLogProvider
{
    /**
     * @return string[][][]
     */
    public static function getCheckoutRecords(): array
    {
        return [
            ['message' => file_get_contents(__DIR__ . '/_files/checkout_log_orig.txt')],
            ['message' => file_get_contents(__DIR__ . '/_files/checkout_log_anon.txt')],
        ];
    }
    /**
     * @return string[][][]
     */
    public static function getLabelRecords(): array
    {
        return [
            ['message' => file_get_contents(__DIR__ . '/_files/label_log_orig.txt')],
            ['message' => file_get_contents(__DIR__ . '/_files/label_log_anon.txt')],
        ];
    }
}
