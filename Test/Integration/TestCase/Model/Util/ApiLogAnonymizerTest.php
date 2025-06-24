<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\Paket\Test\Integration\TestCase\Model\Util;

use Magento\TestFramework\Helper\Bootstrap;
use Monolog\Level;
use Monolog\LogRecord;
use Netresearch\ShippingCore\Model\Util\ApiLogAnonymizer;
use PHPUnit\Framework\TestCase;

class ApiLogAnonymizerTest extends TestCase
{
    /**
     * @return LogRecord[][]
     */
    public static function getLogs(): array
    {
        $datetime = new \DateTimeImmutable();
        
        return [
            'checkout' => [
                new LogRecord(
                    $datetime,
                    'test',
                    Level::Info,
                    file_get_contents(__DIR__ . '/../../../Provider/_files/checkout_log_orig.txt')
                ),
                new LogRecord(
                    $datetime,
                    'test',
                    Level::Info,
                    file_get_contents(__DIR__ . '/../../../Provider/_files/checkout_log_anon.txt')
                ),
            ],
        ];
    }

    /**
     * @param LogRecord $originalRecord
     * @param LogRecord $expectedRecord
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getLogs')]
    #[\PHPUnit\Framework\Attributes\Test]
    public function stripSensitiveData(LogRecord $originalRecord, LogRecord $expectedRecord)
    {
        /** @var ApiLogAnonymizer $anonymizer */
        $anonymizer = Bootstrap::getObjectManager()->create(ApiLogAnonymizer::class, ['replacement' => '[test]']);
        $actualRecord = $anonymizer($originalRecord);
        self::assertSame($expectedRecord->message, $actualRecord->message);
    }
}
