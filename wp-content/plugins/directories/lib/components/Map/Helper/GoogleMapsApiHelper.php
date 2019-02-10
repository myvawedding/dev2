<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class GoogleMapsApiHelper
{
    protected $_loaded;

    public function load(Application $application, array $config = null)
    {
        if ($this->_loaded) return;

        if (!isset($config)) {
            $config = $application->getComponent('Map')->getConfig('lib', 'api', 'googlemaps');
        }
        if ($application->getPlatform()->isAdmin()
            || empty($config['no'])
        ) {
            $url = $this->url($application, '/js', [
                'key' => $config['key'],
                'libraries' => 'places',
                'language' => $application->Map_Api_language(),
            ], '//');
            $application->getPlatform()->addJsFile($url, 'drts-map-google-maps', 'drts-map-api', false)
                ->addJsInline('drts-location-google-maps', "var DRTS_Map_googlemapsApiKey = '" . $application->H($config['key']) . "';");
        }
        $this->_loaded = true;
    }

    public function url(Application $application, $path, array $params, $protocol = 'https://')
    {
        foreach ($params as $key => $value) {
            $params[$key] = $key . '=' . urlencode($value);
        }
        $path = rtrim($path, '?') . '?' . implode('&', $params);

        return $protocol . 'maps.googleapis.com/maps/api' . $path;
    }

    public function timezone(Application $application, $lat, $lng)
    {
        $url = $this->url($application, '/timezone/json', [
            'timestamp' => time(),
            'location' => $lat . ',' . $lng,
        ]);
        return $this->request($application, $url)->timeZoneId;
    }

    public function request(Application $application, $url)
    {
        $result = $application->getPlatform()->remoteGet($url);
        if (!$result = json_decode($result)) {
            throw new Exception\RuntimeException('Failed parsing result returned from URL: ' . $url);
        }
        if ($result->status !== 'OK') {
            $error = isset($result->errorMessage) ? $result->errorMessage : 'An error occurred while querying Google Maps API.';
            throw new Exception\RuntimeException($error . ' Requested URL: ' . $url . '; Returned status: ' . $result->status);
        }
        return $result;
    }
}