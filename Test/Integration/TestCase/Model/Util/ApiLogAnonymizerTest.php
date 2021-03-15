<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Util;

use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Model\Util\ApiLogAnonymizer;
use PHPUnit\Framework\TestCase;

class ApiLogAnonymizerTest extends TestCase
{
    /**
     * @return string[][][]
     */
    public function getLogs(): array
    {
        return [
            'checkout' => [
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/checkout_log_orig.txt')],
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/checkout_log_anon.txt')],
            ],
            'label' => [
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/label_log_orig.txt')],
                ['message' => file_get_contents(__DIR__ . '/../../../Provider/_files/label_log_anon.txt')],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getLogs
     *
     * @param string[] $originalRecord
     * @param string[] $expectedRecord
     */
    public function stripSensitiveData(array $originalRecord, array $expectedRecord)
    {
        /** @var ApiLogAnonymizer $anonymizer */
        $anonymizer = Bootstrap::getObjectManager()->create(ApiLogAnonymizer::class, ['replacement' => '[test]']);
        $actualRecord = $anonymizer($originalRecord);
        self::assertSame($expectedRecord, $actualRecord);
    }
}
