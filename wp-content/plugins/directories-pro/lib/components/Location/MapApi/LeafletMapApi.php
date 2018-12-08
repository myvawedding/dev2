<?php
namespace SabaiApps\Directories\Component\Location\MapApi;

use SabaiApps\Directories\Component\Map\Api\AbstractApi;

class LeafletMapApi extends AbstractApi
{
    protected function _mapApiInfo()
    {
        return [
            'label' => __('OpenStreetMap', 'directories-pro'),
            'default_settings' => [

            ],
            'default_map_settings' => [],
        ];
    }

    public function mapApiLoad(array $settings, array $mapSettings)
    {
        $this->_application->getPlatform()->addCssFile('leaflet.min.css', 'leaflet', null, 'directories-pro', null, true)
            ->addJsFile('leaflet.min.js', 'leaflet', null, 'directories-pro', true, true)
            ->addJsFile('bouncemarker.min.js', 'leaflet-bouncemarker', 'leaflet', 'directories-pro', true, true)
            ->addJsFile('location-leaflet-map.min.js', 'drts-location-leaflet-map', ['leaflet', 'drts-map-api'], 'directories-pro');
        if (!empty($mapSettings['marker_clusters'])) {
            $this->_application->getPlatform()->addCssFile('MarkerCluster.min.css', 'leaflet.markercluster', null, 'directories-pro', null, true)
                ->addCssFile('MarkerCluster.Default.min.css', 'leaflet.markercluster.default', null, 'directories-pro', null, true)
                ->addJsFile('leaflet.markercluster.min.js', 'leaflet.markercluster', 'leaflet', 'directories-pro', true, true);
        }
    }
}