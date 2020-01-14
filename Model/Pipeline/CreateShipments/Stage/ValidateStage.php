<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\Pipeline\CreateShipments\Stage;

use Dhl\Paket\Model\Pipeline\CreateShipments\ArtifactsContainer;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentRequest\RequestValidatorInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Shipping\Model\Shipment\Request;

/**
 * Class ValidateStage
 *
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ValidateStage implements CreateShipmentsStageInterface
{
    /**
     * @var RequestValidatorInterface
     */
    private $requestValidator;

    /**
     * ValidateStage constructor.
     *
     * @param RequestValidatorInterface $requestValidator
     */
    public function __construct(RequestValidatorInterface $requestValidator)
    {
        $this->requestValidator = $requestValidator;
    }

    /**
     * Validate shipment requests.
     *
     * Invalid requests are removed from shipment requests and instantly added as label failures.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $callback = function (Request $request, int $requestIndex) use ($artifactsContainer) {
            try {
                $this->requestValidator->validate($request);
                return true;
            } catch (ValidatorException $exception) {
                $artifactsContainer->addError(
                    (string) $requestIndex,
                    $request->getOrderShipment(),
                    $exception->getMessage()
                );
                return false;
            }
        };

        // pass on only the shipment requests that validate
        return array_filter($requests, $callback, ARRAY_FILTER_USE_BOTH);
    }
}
