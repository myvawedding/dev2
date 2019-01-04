<?php
namespace SabaiApps\Directories\Component\Location;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\View;
use SabaiApps\Directories\Component\Search;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Directories\Component\Map;
use SabaiApps\Directories\Application;

class LocationComponent extends AbstractComponent implements
    Form\IFields,
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    Field\IFilters,
    Entity\IBundleTypes,
    CSV\IExporters,
    CSV\IImporters,
    Search\IFields,
    Map\IApis,
    IAutocompleteApis,
    IGeocodingApis,
    ITimezoneApis
{
    const VERSION = '1.2.17', PACKAGE = 'directories-pro';

    public static function interfaces()
    {
        return array(
            'Payment\IFeatures',
            'Faker\IGenerators',
        );
    }

    public static function description()
    {
        return 'Group and filter content by location and display them on a map.';
    }

    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Location_Hierarchy', array(__CLASS__, 'hierarchyHelper'));
    }

    public static function hierarchyHelper(Application $application, Entity\Model\Bundle $bundle = null)
    {
        if (!isset($bundle)
            || empty($bundle->info['location_hierarchy_custom'])
            || !isset($bundle->info['location_hierarchy'])
        ) {
            return [
                'country' => __('Country', 'directories-pro'),
                'province' => __('State / Province / Region', 'directories-pro'),
                'city' => __('City', 'directories-pro'),
            ];
        }
        return $bundle->info['location_hierarchy'];
    }

    public function formGetFieldTypes()
    {
        return array('location_address', 'location_text');
    }

    public function formGetField($name)
    {
        switch ($name) {
            case 'location_address':
                return new FormField\AddressFormField($this->_application, $name);
            case 'location_text':
                return new FormField\TextFormField($this->_application, $name);
        }
    }

    public function fieldGetTypeNames()
    {
        return array('location_address');
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'location_address':
                return new FieldType\AddressFieldType($this->_application, $name);
        }
    }

    public function fieldGetFilterNames()
    {
        return array('location_address');
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'location_address':
                return new FieldFilter\AddressFieldFilter($this->_application, $name);
        }
    }

    public function fieldGetRendererNames()
    {
        return array('location_address');
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'location_address':
                return new FieldRenderer\AddressFieldRenderer($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return array('location_address');
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'location_address':
                return new FieldWidget\AddressFieldWidget($this->_application, $name);
        }
    }

    public function csvGetImporterNames()
    {
        return array('location_address');
    }

    public function csvGetImporter($name)
    {
        return new CSVImporter\LocationCSVImporter($this->_application, $name);
    }

    public function csvGetExporterNames()
    {
        return array('location_address');
    }

    public function csvGetExporter($name)
    {
        return new CSVExporter\LocationCSVExporter($this->_application, $name);
    }

    public function fakerGetGeneratorNames()
    {
        return array('location_address');
    }

    public function fakerGetGenerator($name)
    {
        return new FakerGenerator\LocationFakerGenerator($this->_application, $name);
    }

    public function onViewEntitiesSettingsFilter(&$settings, $bundle, $view)
    {
        if (!$this->_application->Map_Api()) return;

        if ((string)$view === 'map') {
            $assets_dir = $this->_application->getPlatform()->getAssetsDir('directories-pro');
            $settings['template'] = $assets_dir . '/templates/location_map';
            $settings['ajax_container_template'] = $assets_dir . '/templates/location_entities_container';
            $this->_application->Location_Api_load([
                'location_map' => true,
            ]);
            return;
        }

        if (empty($settings['map']['show'])) return;

        $assets_dir = $this->_application->getPlatform()->getAssetsDir('directories-pro');
        $settings['map']['template'] = $settings['template'];
        $settings['template'] = $assets_dir . '/templates/location_entities';
        $settings['ajax_container_template'] = $assets_dir . '/templates/location_entities_container';
        if (empty($settings['map']['style'])) unset($settings['map']['style']); // required to have the default setting override
        $settings['map'] += $this->_application->getComponent('Map')->getConfig('map');

        $this->_application->Location_Api_load([
            'location_map' => true,
            'location_map_sticky' => !empty($settings['map']['sticky']),
        ]);
    }

    public function onViewEntitiesSortsFilter(&$sorts, $bundle, $request, $settings)
    {
        if (empty($bundle->info['location_enable'])
            || !isset($sorts['location_address'])
            || !isset($settings['query']['fields']['location_address'])
        ) return;

        // Pass query args as 2nd sort arg
        $sorts['location_address']['args'] = array(
            'asc',
            explode(',', $settings['query']['fields']['location_address'])
        );
    }

    public function searchGetFieldNames()
    {
        return array('location_address');
    }

    public function searchGetField($name)
    {
        return new SearchField\AddressSearchField($this->_application, $name);
    }

    public function onViewModeSettingsFormFilter(&$form, View\Mode\IMode $mode, Entity\Model\Bundle $bundle, $settings, $parents, $submitValues)
    {
        if ((string)$mode === 'map') {
            $_parents = array_merge($parents, ['map']);
            $field_name = $this->_application->Form_FieldName($_parents);
            $form['map'] += [
                'fullscreen' => [
                    '#type' => 'checkbox',
                    '#title' => __('Enable full screen mode', 'directories-pro'),
                    '#default_value' => !empty($settings['map']['fullscreen']),
                    '#horizontal' => true,
                    '#weight' => 15,
                ],
                'fullscreen_offset' => [
                    '#type' => 'slider',
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 500,
                    '#min_text' => __('Auto', 'directories-pro'),
                    '#title' => __('Full screen map top offset', 'directories-pro'),
                    '#field_suffix' => 'px',
                    '#default_value' => isset($settings['map']['fullscreen_offset']) ? $settings['map']['fullscreen_offset'] : 0,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s[fullscreen]"]', $field_name) => array('value' => 1),
                        ],
                    ],
                    '#horizontal' => true,
                    '#weight' => 18,
                ],
                'custom_infobox_addr_format' => [
                    '#type' => 'checkbox',
                    '#title' => __('Customize format of address on infobox', 'directories-pro'),
                    '#default_value' => !empty($settings['map']['custom_infobox_addr_format']),
                    '#weight' => 23,
                    '#horizontal' => true,
                ],
                'infobox_addr_format' => [
                    '#type' => 'textfield',
                    '#description' => sprintf(
                        __('Available tags: %s', 'directories-pro'),
                        implode(' ', $this->_application->Location_FormatAddress_tags($bundle))
                    ),
                    '#default_value' => isset($settings['map']['infobox_addr_format']) ? $settings['map']['infobox_addr_format'] : '{full_address}',
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s[custom_infobox_addr_format]"]', $field_name) => ['type' => 'checked', 'value' => true],
                            sprintf('select[name="%s[coordinates_field]"]', $field_name) => ['value' => 'location_address'],
                        ],
                    ],
                    '#weight' => 24,
                    '#horizontal' => true,
                ],
            ];
            return;
        }

        if ($bundle->info['is_taxonomy']
            || empty($bundle->info['location_enable'])
            || !$mode->viewModeInfo('mapable')
        ) return;

        $_parents = array_merge($parents, ['map']);
        $field_name = $this->_application->Form_FieldName($_parents);
        $states = array(
            sprintf('input[name="%s[show]"]', $field_name) => array('type' => 'checked', 'value' => true),
        );
        $form['map'] = array(
            'show' => array(
                '#type' => 'checkbox',
                '#title' => __('Show map', 'directories-pro'),
                '#default_value' => !empty($settings['map']['show']),
                '#horizontal' => true,
                '#weight' => 1,
            ),
            'coordinates_field' => [
                '#type' => 'select',
                '#title' => __('Map coordinates field', 'directories-pro'),
                '#horizontal' => true,
                '#options' => $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates']),
                '#default_value' => isset($settings['map']['coordinates_field']) ? $settings['map']['coordinates_field'] : 'location_address',
                '#states' => array('visible' => $states),
                '#required' => true,
                '#weight' => 3,
            ],
            'position' => array(
                '#type' => 'select',
                '#title' => __('Map position', 'directories-pro'),
                '#options' => array('side' => __('Side', 'directories-pro'), 'top' => __('Top', 'directories-pro')),
                '#default_value' => isset($settings['map']['position']) ? $settings['map']['position'] : 'side',
                '#states' => array('visible' => $states),
                '#horizontal' => true,
                '#weight' => 5,
            ),
            'span' => array(
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 11,
                '#title' => __('Map width', 'directories-pro'),
                '#description' => __('The horizontal display ratio (12 being 100% wide) of the map', 'directories-pro'),
                '#default_value' => isset($settings['map']['span']) ? $settings['map']['span'] : 4,
                '#states' => array('visible' => $states + array(
                    sprintf('select[name="%s[position]"]', $field_name) => array('value' => 'side'),
                )),
                '#horizontal' => true,
                '#weight' => 9,
            ),
            'height' => array(
                '#type' => 'slider',
                '#title' => __('Map height', 'directories-pro'),
                '#default_value' => isset($settings['map']['height']) ? $settings['map']['height'] : 400,
                '#min_value' => 100,
                '#max_value' => 1000,
                '#step' => 10,
                '#integer' => true,
                '#field_suffix' => 'px',
                '#horizontal' => true,
                '#weight' => 10,
                '#states' => array('visible' => $states),
            ),
            'scroll_to_item' => array(
                '#type' => 'checkbox',
                '#title' => __('Scroll to item on marker click', 'directories-pro'),
                '#default_value' => !isset($settings['map']['scroll_to_item']) || !empty($settings['map']['scroll_to_item']),
                '#horizontal' => true,
                '#weight' => 13,
                '#states' => array('visible' => $states),
            ),
            'sticky' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable sticky map', 'directories-pro'),
                '#default_value' => !isset($settings['map']['sticky']) || !empty($settings['map']['sticky']),
                '#horizontal' => true,
                '#weight' => 14,
                '#states' => array('visible' => $states),
            ),
            'sticky_offset' => array(
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 500,
                '#min_text' => __('Auto', 'directories-pro'),
                '#title' => __('Sticky map top offset', 'directories-pro'),
                '#field_suffix' => 'px',
                '#default_value' => isset($settings['map']['sticky_offset']) ? $settings['map']['sticky_offset'] : 0,
                '#states' => array(
                    'visible' => $states + array(
                            sprintf('input[name="%s[sticky]"]', $field_name) => array('value' => 1),
                        ),
                ),
                '#horizontal' => true,
                '#weight' => 15,
            ),
            'fullscreen' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable full screen mode', 'directories-pro'),
                '#default_value' => !isset($settings['map']['fullscreen']) || !empty($settings['map']['fullscreen']),
                '#horizontal' => true,
                '#weight' => 16,
                '#states' => array('visible' => $states),
            ),
            'fullscreen_span' => array(
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 1,
                '#max_value' => 11,
                '#title' => __('Full screen map width', 'directories-pro'),
                '#description' => __('The horizontal display ratio (12 being 100% wide) of the map', 'directories-pro'),
                '#default_value' => isset($settings['map']['fullscreen_span']) ? $settings['map']['fullscreen_span'] : 6,
                '#states' => array(
                    'visible' => $states + array(
                        sprintf('input[name="%s[fullscreen]"]', $field_name) => array('value' => 1),
                    ),
                ),
                '#horizontal' => true,
                '#weight' => 17,
            ),
            'fullscreen_offset' => array(
                '#type' => 'slider',
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 500,
                '#min_text' => __('Auto', 'directories-pro'),
                '#title' => __('Full screen map top offset', 'directories-pro'),
                '#field_suffix' => 'px',
                '#default_value' => isset($settings['map']['fullscreen_offset']) ? $settings['map']['fullscreen_offset'] : 0,
                '#states' => array(
                    'visible' => $states + array(
                        sprintf('input[name="%s[fullscreen]"]', $field_name) => array('value' => 1),
                    ),
                ),
                '#horizontal' => true,
                '#weight' => 18,
            ),
            'infobox' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable map infobox', 'directories-pro'),
                '#default_value' => !isset($settings['map']['infobox']) || !empty($settings['map']['infobox']),
                '#weight' => 20,
                '#horizontal' => true,
                '#states' => array('visible' => $states),
            ),
            'infobox_width' => array(
                '#type' => 'slider',
                '#min_value' => 100,
                '#max_value' => 500,
                '#step' => 10,
                '#default_value' => isset($settings['map']['infobox_width']) ? $settings['map']['infobox_width'] : 240,
                '#title' => __('Map infobox width', 'directories-pro'),
                '#field_suffix' => 'px',
                '#integer' => true,
                '#states' => array(
                    'visible' => $states + array(
                        sprintf('input[name="%s[infobox]"]', $field_name) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#weight' => 21,
                '#horizontal' => true,
            ),
            'trigger_infobox' => array(
                '#type' => 'checkbox',
                '#title' => __('Open infobox on item hover', 'directories-pro'),
                '#default_value' => !empty($settings['map']['trigger_infobox']),
                '#states' => array(
                    'visible' => $states + array(
                        sprintf('input[name="%s[infobox]"]', $field_name) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#weight' => 22,
                '#horizontal' => true,
            ),
            'custom_infobox_addr_format' => [
                '#type' => 'checkbox',
                '#title' => __('Customize format of address on infobox', 'directories-pro'),
                '#default_value' => !empty($settings['map']['custom_infobox_addr_format']),
                '#states' => array(
                    'visible' => $states + array(
                        sprintf('input[name="%s[infobox]"]', $field_name) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#weight' => 23,
                '#horizontal' => true,
            ],
            'infobox_addr_format' => [
                '#type' => 'textfield',
                '#description' => sprintf(
                    __('Available tags: %s', 'directories-pro'),
                    implode(' ', $this->_application->Location_FormatAddress_tags($bundle))
                ),
                '#default_value' => isset($settings['map']['infobox_addr_format']) ? $settings['map']['infobox_addr_format'] : '{full_address}',
                '#states' => [
                    'visible' => $states + [
                        sprintf('input[name="%s[infobox]"]', $field_name) => ['type' => 'checked', 'value' => true],
                        sprintf('input[name="%s[custom_infobox_addr_format]"]', $field_name) => ['type' => 'checked', 'value' => true],
                        sprintf('select[name="%s[coordinates_field]"]', $field_name) => ['value' => 'location_address'],
                    ],
                ],
                '#weight' => 24,
                '#horizontal' => true,
            ],
        );

        $marker_icon_options = $this->_application->Map_Marker_iconOptions($bundle);
        if (count($marker_icon_options) > 1) {
            $form['map']['view_marker_icon'] = [
                '#type' => 'select',
                '#title' => __('Map marker icon', 'directories-pro'),
                '#default_value' => isset($settings['map']['view_marker_icon']) ? $settings['map']['view_marker_icon'] : 'image',
                '#options' => $marker_icon_options,
                '#weight' => 12,
                '#horizontal' => true,
                '#states' => array('visible' => $states),
            ];
        }

        return $form;
    }

    public function entityGetBundleTypeNames()
    {
        return array('location_location');
    }

    public function entityGetBundleType($name)
    {
        return new EntityBundleType\LocationEntityBundleType($this->_application, $name);
    }

    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (empty($info['location_enable'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) return;

        $form['location_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable locations', 'directories-pro'),
            '#default_value' => !empty($settings['location_enable']) || is_null($settings),
            '#horizontal' => true,
        );
    }

    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {
        if (!isset($info['location_enable'])) return;

        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) {
            unset($info['location_enable']);
        }

        if (isset($settings['location_enable']) && !$settings['location_enable']) {
            $info['location_enable'] = false;
        }
    }

    public function onEntityBundlesInfoFilter(&$bundles, $componentName, $group)
    {
        foreach (array_keys($bundles) as $bundle_type) {
            $info =& $bundles[$bundle_type];

            if (empty($info['location_enable'])
                || !empty($info['is_taxonomy'])
                || !empty($info['parent'])
            ) continue;

            // Add location_location bundle
            if (!isset($bundles['location_location'])) { // may already set if updating or importing
                $bundles['location_location'] = [];
            }
            $bundles['location_location'] += $this->entityGetBundleType('location_location')->entityBundleTypeInfo();

            return; // there should be only one bundle with location enabled
        }

        // No bundle with locations enabled found, so make sure the location_location bundle is not assigned
        unset($bundles['location_location']);
    }

    public function onEntityBundleInfoFilter(&$info, $componentName, $group)
    {
        if (empty($info['location_enable'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
        ) return;

        // Associate location bundle
        $info['taxonomies']['location_location'] = array(
            'weight' => 10,
        );
        // Add location address field
        if (!isset($info['fields']['location_address'])) { // may already be set
            $info['fields']['location_address'] = [];
        }
        if (!empty($info['location_field'])) {
            $info['fields']['location_address'] += $info['location_field'];
        }
        $info['fields']['location_address'] += array(
            'type' => 'location_address',
            'settings' => [],
            'max_num_items' => 1,
            'weight' => 7,
            'label' => __('Location', 'directories-pro'),
            'required' => false,
        );
    }

    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'location_enable';
        $keys[] = 'location_marker_taxonomy';
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'location_hierarchy';
        $keys[] = 'location_hierarchy_custom';
    }

    public function onViewCurrentTermFilter(&$termId, $bundle)
    {
        if (empty($termId)
            && $bundle->type === 'location_location'
            && ($term_id = $this->_application->Location_IsSearchRequested())
        ) {
            $termId = $term_id;
        }
        return $termId;
    }

    public function onEntityBeforeCreateEntity($bundle, &$values)
    {
        $this->_setTermValuesFromLocation($bundle, $values);
    }

    public function onEntityBeforeUpdateEntity($bundle, Entity\Type\IEntity $entity, &$values)
    {
        $this->_setTermValuesFromLocation($bundle, $values);
    }

    /**
     * Fill taxonomy term values when submitting a post from the backend
     */
    protected function _setTermValuesFromLocation($bundle, &$values)
    {
        if (empty($bundle->info['location_enable'])) return;

        if (isset($values['location_address'])) {
            // Extract term ID from location field and copy to taxonomy term field
            $values['location_location'] = [];
            if (!isset($values['location_address'][0])) {
                $values['location_address'] = [$values['location_address']];
            }
            foreach ($values['location_address'] as $field_value) {
                if (!empty($field_value['term_id'])) {
                    $values['location_location'][] = $field_value['term_id'];
                }
            }
        }
    }

    public function paymentGetFeatureNames()
    {
        return array('location_locations');
    }

    public function paymentGetFeature($name)
    {
        return new PaymentFeature\LocationsPaymentFeature($this->_application, $name);
    }

    public function onCorePlatformWordPressInit()
    {
        if (is_admin()) {
            add_action('admin_head' , array($this, 'adminHeadAction'));
        }
    }

    public function adminHeadAction()
    {
        if (empty($GLOBALS['typenow'])
            || (!$bundle = $this->_application->Entity_Bundle($GLOBALS['typenow']))
            || empty($bundle->info['location_enable'])
            || empty($bundle->info['taxonomies']['location_location'])
        ) return;

        remove_meta_box($bundle->info['taxonomies']['location_location'] . 'div', $bundle->name, 'side');
    }

    public function onEntityFieldValuesLoaded(Entity\Type\IEntity $entity, Entity\Model\Bundle $bundle, array $fields, $cache)
    {
        if (!$cache
            || !$this->_application->isComponentLoaded('Payment')
            || empty($bundle->info['location_enable'])
            || empty($bundle->info['payment_enable'])
        ) return;

        $features = $this->_application->Payment_Plan_features($entity);

        if (!empty($features[0]['location_locations']['unlimited'])) return;

        if (!isset($features[0]['location_locations']['num'])) {
            $max_num_allowed = 1;
        } else {
            $max_num_allowed = empty($features[0]['location_locations']['num']) ? 0 : $features[0]['location_locations']['num'];
        }
        if (!empty($features[1]['location_locations']['num'])) { // any additional num of photos allowed?
            $max_num_allowed += $features[1]['location_locations']['num'];
        }

        // Limit both taxonomy term and address fields
        foreach (array('location_location', 'location_address') as $field_name) {
            if ((!$field_values = $entity->getFieldValue($field_name))
                || count($field_values) <= $max_num_allowed
            ) continue;

            $entity->setFieldValue($field_name, array_slice($field_values, 0, $max_num_allowed));
        }
    }

    public function onSystemAdminSystemToolsFilter(&$tools)
    {
        $tools['location_geocode'] = [
            'label' => __('Load geolocation data', 'directories-pro'),
            'description' => __('This tool will load geolocation data (lat/lng/timezone) of each content item if currently empty.', 'directories-pro'),
            'with_progress' => true,
            'weight' => 80,
        ];
    }

    public function onSystemAdminRunTool($tool, $progress, $values)
    {
        switch ($tool) {
            case 'location_geocode':
                $this->_application->Location_Tools_geocode($progress);
                break;
        }
    }

    public function mapGetApiNames()
    {
        return ['location_leaflet'];
    }

    public function mapGetApi($name)
    {
        switch ($name) {
            case 'location_leaflet':
                return new MapApi\LeafletMapApi($this->_application, $name);
        }
    }

    public function locationGetAutocompleteApiNames()
    {
        return ['location_googlemaps', 'location_algolia'];
    }

    public function locationGetAutocompleteApi($name)
    {
        switch ($name) {
            case 'location_googlemaps':
                return new Api\GoogleMapsAutocompleteApi($this->_application, $name);
            case 'location_algolia':
                return new Api\AlgoliaAutocompleteApi($this->_application, $name);
        }
    }

    public function locationGetGeocodingApiNames()
    {
        return ['location_googlemaps', 'location_nominatim', 'location_mapbox'];
    }

    public function locationGetGeocodingApi($name)
    {
        switch ($name) {
            case 'location_googlemaps':
                return new Api\GoogleMapsGeocodingApi($this->_application, $name);
            case 'location_nominatim':
                return new Api\NominatimGeocodingApi($this->_application, $name);
            case 'location_mapbox':
                return new Api\MapboxGeocodingApi($this->_application, $name);
        }
    }

    public function locationGetTimezoneApiNames()
    {
        return ['location_googlemaps', 'location_geonames'];
    }

    public function locationGetTimezoneApi($name)
    {
        switch ($name) {
            case 'location_googlemaps':
                return new Api\GoogleMapsTimezoneApi($this->_application, $name);
            case 'location_geonames':
                return new Api\GeoNamesTimezoneApi($this->_application, $name);
        }
    }

    public function onMapLibrarySettingsFormFilter(&$form, $config, $parents)
    {
        $form['location_geocoding'] = [
            '#type' => 'select',
            '#options' => ['' => __('— None —', 'directories-pro')] + $this->_application->Location_Api_options('Geocoding'),
            '#horizontal' => true,
            '#title' => __('Geocoding provider', 'directories-pro'),
            '#default_value' => isset($config['location_geocoding']) ? $config['location_geocoding'] : null,
            '#weight' => 5,
        ];
        $form['location_timezone'] = [
            '#type' => 'select',
            '#options' => ['' => __('— None —', 'directories-pro')] + $this->_application->Location_Api_options('Timezone'),
            '#horizontal' => true,
            '#title' => __('Time zone API', 'directories-pro'),
            '#default_value' => isset($config['location_timezone']) ? $config['location_timezone'] : null,
            '#weight' => 6,
        ];
        $form['location_autocomplete'] = [
            '#type' => 'select',
            '#options' => ['' => __('— None —', 'directories-pro')] + $this->_application->Location_Api_options('Autocomplete'),
            '#horizontal' => true,
            '#title' => __('Address autocomplete service', 'directories-pro'),
            '#default_value' => isset($config['location_autocomplete']) ? $config['location_autocomplete'] : null,
            '#weight' => 7,
        ];
        $form['api']['googlemaps']['#states']['visible_or'] += [
            '[name="Map[lib][location_geocoding]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
            '[name="Map[lib][location_autocomplete]"]' => ['type' => 'value', 'value' => 'location_googlemaps'],
        ];

        foreach ($this->_application->Location_Api_components() as $api_type => $components) {
            foreach (array_keys($components) as $api_name) {
                if (!$api = $this->_application->Location_Api_impl($api_name, $api_type, true)) continue;

                $api_settings = empty($config['api'][$api_name]) ? [] : $config['api'][$api_name];
                if ($api_defaults = $api->locationApiInfo('default_settings')) {
                    $api_settings = array_replace_recursive($api_defaults, $api_settings);
                }
                if (!$api_setting_form = $api->locationApiSettingsForm($api_settings, array_merge($parents, ['api', $api_name]))) continue;

                if (!isset($form['api'][$api_name])) {
                    $form['api'][$api_name] = $api_setting_form;
                } else {
                    $form['api'][$api_name] += $api_setting_form;
                }
            }
        }
    }

    public function onFormScripts($options)
    {
        $_options = [
            'location_field' => true,
            'location_textfield' => true,
            'location_autocomplete' => true,
        ];
        if (!empty($options)) {
            foreach (array_keys($_options) as $key) {
                if (!in_array($key, $options)) {
                    unset($_options[$key]);
                }
            }
        }
        $this->_application->Location_Api_load($_options);
    }

    public function onMapRenderFieldMap($field, $options)
    {
        if (!empty($options['directions'])) {
            $this->_application->Location_Api_load(['location_autocomplete' => true]);
        }
    }

    public function onMapMarkerAddressFilter(&$address, $entity, $value, $settings)
    {
        if (!empty($settings['custom_infobox_addr_format'])) {
            if (!empty($value['term_id'])
                && ($bundle = $this->_application->Entity_Bundle($entity))
                && ($location_bundle = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group))
            ) {
                $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle);
            } else {
                $location_hierarchy = null;
            }
            $address = $this->_application->Location_FormatAddress($value, $settings['infobox_addr_format'], $entity, $location_hierarchy);
        }
    }
}
