<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\Paket\Webservice;

use Dhl\Paket\Model\Config\ModuleConfig;

/**
 * Class PostFinderService
 *
 * @todo(nr): use proper SDK package
 *
 * @package Dhl\Paket\Webservice
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class PostFinderService
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * PostFinderService constructor.
     *
     * @param ModuleConfig $config
     * @param int|null $storeId
     */
    public function __construct(ModuleConfig $config, int $storeId = null)
    {
        $this->config = $config;
        $this->storeId = $storeId;
    }

    /**
     * Query post offices from postfinder API.
     *
     * @param string $countryId
     * @param string $postalCode
     * @return string[][]
     */
    public function getPostOffices(string $countryId, string $postalCode)
    {
        $branches = [];

        $user = $this->config->getAuthUsername($this->storeId);
        $pass = $this->config->getAuthPassword($this->storeId);

        $auth = base64_encode("$user:$pass");
        $query = http_build_query(
            [
                'PARTNER_ID' => 'DHLDS',
                'standorttyp' => 'filialen_verkaufspunkte',
                'pmtype' => '1',
                'lang' => 'de',
                'zip' => $postalCode,
            ]
        );

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . $auth . "\r\n",
            ],
        ];

        $context = stream_context_create($opts);
        $url = 'https://cig.dhl.de/services/sandbox/rest/postfinder?' . $query;

        $xml = file_get_contents($url, false, $context);

        $element = new \SimpleXMLElement($xml, \LIBXML_NOCDATA);
        foreach ($element->data->result->branches->children() as $branch) {
            $branches[] = [
                'postOfficeNumber' => trim(current($branch->depotServiceNo)),
                'country' => $countryId,
                'city' => trim(current($branch->address->city)),
                'zip' => trim(current($branch->address->zip)),
                'street' => trim(current($branch->address->street)),
                'streetNo' => !empty($branch->address->streetno) ? trim(current($branch->address->streetno)) : '',
            ];
        }

        return $branches;
    }

    /**
     * Query parcel stations from postfinder API.
     *
     * @param string $countryId
     * @param string $postalCode
     * @return string[][]
     */
    public function getParcelStations($countryId, $postalCode)
    {
        $automats = [];

        $user = $this->config->getAuthUsername($this->storeId);
        $pass = $this->config->getAuthPassword($this->storeId);

        $auth = base64_encode("$user:$pass");
        $query = http_build_query(
            [
                'PARTNER_ID' => 'DHLDS',
                'standorttyp' => 'packstation_paketbox',
                'pmtype' => '1',
                'lang' => 'de',
                'zip' => $postalCode,
            ]
        );

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . $auth . "\r\n",
            ],
        ];

        $context = stream_context_create($opts);
        $url = 'https://cig.dhl.de/services/sandbox/rest/postfinder?' . $query;

        $xml = file_get_contents($url, false, $context);

        $element = new \SimpleXMLElement($xml, \LIBXML_NOCDATA);
        foreach ($element->data->result->automats->children() as $automat) {
            $automats[] = [
                'parcelStationNumber' => substr(trim(current($automat['objectId'])), -3),
                'country' => $countryId,
                'city' => trim(current($automat->address->city)),
                'zip' => trim(current($automat->address->zip)),
                'street' => trim(current($automat->address->street)),
                'streetNo' => !empty($automat->address->streetno) ? trim(current($automat->address->streetno)) : '',
            ];
        }

        return $automats;
    }
}
