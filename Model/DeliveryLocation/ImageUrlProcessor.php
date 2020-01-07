<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Model\DeliveryLocation;

use Magento\Framework\App\Area;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\View\DesignInterface;

/**
 * Class ImageUrlProcessor
 *
 * @package Dhl\Paket\Model\DeliveryLocation
 * @author  Andreas Müller <andreas.mueller@netresearch.de>
 * @link    https://netresearch.de
 */
class ImageUrlProcessor
{
    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var Repository
     */
    private $assetRepo;

    /**
     * ImageUrlProcessor constructor.
     * @param DesignInterface $design
     * @param ThemeProviderInterface $themeProvider
     * @param Repository $assetRepo
     */
    public function __construct(
        DesignInterface $design,
        ThemeProviderInterface $themeProvider,
        Repository $assetRepo
    ) {
        $this->design = $design;
        $this->themeProvider = $themeProvider;
        $this->assetRepo = $assetRepo;
    }

    /**
     * @param string $imageId
     * @return string
     */
    public function getUrl(string $imageId): string
    {
        $params = [];

        if (!in_array($this->design->getArea(), [Area::AREA_FRONTEND, Area::AREA_ADMINHTML], true)) {
            $themeId = $this->design->getConfigurationDesignTheme(Area::AREA_FRONTEND);
            $params = [
                'area' => Area::AREA_FRONTEND,
                'themeModel' => $this->themeProvider->getThemeById($themeId),
            ];
        }

        return $this->assetRepo->getUrlWithParams($imageId, $params);
    }
}
