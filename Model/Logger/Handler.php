<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Logger;

use Dhl\Paket\Model\Config\ModuleConfig;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 *
 * @package Dhl\Paket\Model
 * @copyright 2018 Netresearch DTT GmbH
 * @link      http://www.netresearch.de/
 */
class Handler extends Base
{
    /**
     * @var ModuleConfig
     */
    private $moduleConfig;

    /**
     * @var string
     */
    protected $fileName = 'var/log/dhl_paket.log';

    /**
     * Handler constructor.
     *
     * @param DriverInterface $filesystem
     * @param ModuleConfig $config
     * @param string|null $filePath
     * @param string|null $fileName
     */
    public function __construct(
        DriverInterface $filesystem,
        ModuleConfig $config,
        string $filePath = null,
        string $fileName = null
    ) {
        $this->moduleConfig = $config;
        parent::__construct($filesystem, $filePath, $fileName);
    }

    /**
     * @inheritdoc
     */
    public function isHandling(array $record): bool
    {
        $logEnabled = $this->moduleConfig->isLoggingEnabled();
        $logLevel   = $this->moduleConfig->getLogLevel();

        return $logEnabled && $record['level'] >= $logLevel && parent::isHandling($record);
    }
}
