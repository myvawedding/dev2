<?php
namespace SabaiApps\Directories\Component\Location\Api;

class GoogleMapsTimezoneApi extends AbstractGoogleMapsApi implements ITimezoneApi
{
    protected function _doGetInfo()
    {
        return [
            'label' => __('Google Maps Time Zone', 'directories-pro'),
        ];
    }

    public function locationApiInfo($key = null)
    {
        return $this->_getInfo($key);
    }

    public function locationApiLoad(array $settings)
    {
        $handle = $this->_load('timezone');
        $this->_application->getPlatform()->addJsInline(
            $handle,
            "var DRTS_Location_googlemapsTimezoneEndpoint = '" . $this->_application->H($this->_application->Url('/_drts/location/timezone.json')) . "';"
        );
    }

    public function locationApiGetTimezone(array $latlng, array $settings)
    {
        $url = $this->_application->Map_GoogleMapsApi_url('/timezone/json', [
            'key' => $settings['api']['key'],
            'timestamp' => time(),
            'location' => $latlng[0] . ',' . $latlng[1],
        ]);
        return $this->_application->Map_GoogleMapsApi_request($url)->timeZoneId;
    }
}