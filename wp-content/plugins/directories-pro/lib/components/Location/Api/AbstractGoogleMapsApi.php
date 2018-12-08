<?php
namespace SabaiApps\Directories\Component\Location\Api;

use SabaiApps\Directories\Application;

abstract class AbstractGoogleMapsApi
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    protected function _getInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = $this->_doGetInfo();
        }
        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }

    abstract protected function _doGetInfo();

    protected function _load($type, $loadUtil = false)
    {
        $this->_application->Map_GoogleMapsApi_load();
        $this->_application->getPlatform()->addJsFile(
            'location-googlemaps-' . $type . '.min.js',
            $handle = 'drts-location-googlemaps-' . $type,
            'drts-location-api',
            'directories-pro'
        );
        if ($loadUtil) {
            $this->_application->getPlatform()->addJsFile(
                'location-googlemaps-util.min.js',
                'drts-location-googlemaps-util',
                'drts-location-api',
                'directories-pro'
            );
        }
        return $handle;
    }

    public function locationApiSettingsForm(array $settings, array $parents)
    {
        return [
            'api' => [
                '#title' => __('Google Maps API Settings (Server)', 'directories-pro'),
                '#class' => 'drts-form-label-lg',
                '#states' => [
                    'visible_or' => [
                        '[name="Map[lib][location_geocoding]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
                        '[name="Map[lib][location_timezone]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
                    ],
                ],
                '#weight' => 1,
                'key' => [
                    '#type' => 'textfield',
                    '#title' => __('API key', 'directories-pro'),
                    '#default_value' => isset($settings['api']['key']) ? $settings['api']['key'] : null,
                    '#horizontal' => true,
                    '#required' => function($form) {
                        return $form->getValue(['Map', 'lib', 'location_geocoding']) === 'location_googlemaps'
                            || $form->getValue(['Map', 'lib', 'location_timezone']) === 'location_googlemaps';
                    },
                ],
            ],
        ];
    }
}