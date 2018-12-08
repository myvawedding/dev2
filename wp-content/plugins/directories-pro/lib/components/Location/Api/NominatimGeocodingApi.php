<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class NominatimGeocodingApi implements IGeocodingApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
        $this->_info = [
            'label' => __('OpenStreetMap Nominatim', 'directories-pro'),
            'default_settings' => [],
        ];
    }

    public function locationApiInfo($key = null)
    {
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    public function locationApiLoad(array $settings)
    {
        $geocoding_settings = [
            'language' => $this->_application->Map_Api_language(),
            'streetNumAfter' => defined('DRTS_LOCATION_GEOCODING_STREET_NUM_AFTER')
                && DRTS_LOCATION_GEOCODING_STREET_NUM_AFTER,
        ];
        $this->_application->getPlatform()
            ->addJsFile(
                'location-nominatim-geocoding.min.js',
                'drts-location-nominatim-geocoding',
                'drts-location-api',
                'directories-pro'
            )
            ->addJsInline(
                'drts-location-nominatim-geocoding',
                sprintf(
                    'var DRTS_Location_nominatimGeocoding = %s;',
                    $this->_application->JsonEncode($geocoding_settings)
                )
            );
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {

    }

    public function locationApiGeocode($address, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'https://nominatim.openstreetmap.org/search',
            'params' => [
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
                'q' => $address,
                'accept-language' => $this->_application->Map_Api_language(),
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results[0])) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results[0]);
    }

    public function locationApiReverseGeocode(array $latlng, array $settings)
    {
        $url = (string)$this->_application->Url([
            'script_url' => 'https://nominatim.openstreetmap.org/reverse',
            'params' => [
                'format' => 'json',
                'addressdetails' => 1,
                'zoom' => 18,
                'lat' => $latlng[0],
                'lon' => $latlng[1],
                'accept-language' => $this->_application->Map_Api_language(),
            ],
            'separator' => '&',
        ]);
        $results = $this->_sendRequest($url);
        if (empty($results)) {
            throw new Exception\RuntimeException('No geocoding results found. Request URL: ' . $url);
        }

        return $this->_parseResults($results);
    }

    protected function _parseResults(array $results)
    {
        return [
            'lat' => $results['lat'],
            'lng' => $results['lon'],
            'address' => $results['display_name'],
            'viewport' => [
                $results['boundingbox'][0],
                $results['boundingbox'][2],
                $results['boundingbox'][1],
                $results['boundingbox'][3],
            ],
        ] + $this->_getAddressComponents($results['address']);
    }

    protected function _getAddressComponents(array $components)
    {
        $ret = ['street' => '', 'city' => '', 'province' => '', 'zip' => '', 'country' => ''];
        foreach ($components as $type => $value) {
            switch ($type) {
                case 'road':
                    $ret['street'] = $value;
                    continue;
                case 'city':
                    $ret['city'] = $value;
                    continue;
                case 'state':
                    $ret['province'] = $value;
                    continue;
                case 'postcode':
                    $ret['zip'] = $value;
                    continue;
                case 'country_code':
                    $ret['country'] = strtoupper($value);
                    continue;
                default:
                    $ret[$type] = $value;
            }
        }
        if (isset($ret['street']) && strlen($ret['street'])) {
            if (isset($ret['house_number'])) {
                $ret['street'] = $ret['house_number'] . ' ' . $ret['street'];
            }
        } else {
            if (isset($ret['suburb']) && strlen($ret['suburb'])) {
                $ret['street'] = $ret['suburb'];
            }
        }

        return $ret;
    }

    protected function _sendRequest($url, $assoc = true)
    {
        $result = $this->_application->getPlatform()->remoteGet($url);
        if (!$result = json_decode($result, $assoc)) {
            throw new Exception\RuntimeException('Failed parsing result returned from URL: ' . $url);
        }
        return $result;
    }
}