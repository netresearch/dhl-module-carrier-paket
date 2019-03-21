<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice\Shipment;

use Dhl\Sdk\Bcs\Webservice\Exception\ValidationStatusException;
use Dhl\Sdk\Bcs\Webservice\Exception\WeakValidationException;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;

/**
 * ErrorHandler class.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ErrorHandler
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * Constructor.
     *
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * @param \Exception $ex
     *
     * @return DataObject
     */
    public function createErrorResult(\Exception $ex): DataObject
    {
        if ($ex instanceof ValidationStatusException) {
            $messages = array_unique($ex->getStatusMessages());
            $message  = 'Failed to create shipment label: ' . implode(', ', $messages);
        } else {
            $message = $ex->getMessage();
        }

        return $this->dataObjectFactory->create([
            'data' => [
                'errors' => $message,
            ],
        ]);
    }
}
