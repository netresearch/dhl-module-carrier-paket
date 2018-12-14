<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Logger;

use Dhl\Paket\Model\Config\ModuleConfigInterface;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 */
class Handler extends Base
{
    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var string
     */
    protected $fileName = 'var/log/dhl_paket.log';

    /**
     * Constructor.
     *
     * @param DriverInterface       $filesystem
     * @param ModuleConfigInterface $config
     * @param string|null           $filePath
     * @param string|null           $fileName
     */
    public function __construct(
        DriverInterface $filesystem,
        ModuleConfigInterface $config,
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
