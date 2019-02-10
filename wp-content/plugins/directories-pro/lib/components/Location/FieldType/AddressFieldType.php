<?php
namespace SabaiApps\Directories\Component\Location\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Map;

class AddressFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable,
    Map\FieldType\ICoordinates
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Location', 'directories-pro'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'default_settings' => [
                'format' => '{street} {street2}, {city}, {province} {zip}, {country}',
            ],
            'icon' => 'fas fa-map-marker-alt',
            'entity_cache_clear' => true,
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'address' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'address',
                    'default' => '',
                ),
                'street' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'street',
                    'default' => '',
                ),
                'street2' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 255,
                    'notnull' => true,
                    'was' => 'street2',
                    'default' => '',
                ),
                'city' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 100,
                    'notnull' => true,
                    'was' => 'city',
                    'default' => '',
                ),
                'province' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 100,
                    'notnull' => true,
                    'was' => 'state',
                    'default' => '',
                ),
                'zip' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 30,
                    'notnull' => true,
                    'was' => 'zip',
                    'default' => '',
                ),
                'country' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 50,
                    'notnull' => true,
                    'was' => 'country',
                    'default' => '',
                ),
                'timezone' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 50,
                    'notnull' => true,
                    'was' => 'timezone',
                    'default' => '',
                ),
                'zoom' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'unsigned' => true,
                    'notnull' => true,
                    'length' => 2,
                    'was' => 'zoom',
                    'default' => 10,
                ),
                'lat' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'length' => 9,
                    'scale' => 6,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'lat',
                    'default' => 0,
                ),
                'lng' => array(
                    'type' => Application::COLUMN_DECIMAL,
                    'length' => 9,
                    'scale' => 6,
                    'notnull' => true,
                    'unsigned' => false,
                    'was' => 'lng',
                    'default' => 0,
                ),
                'term_id' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'unsigned' => true,
                    'notnull' => true,
                    'length' => 10,
                    'was' => 'term_id',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'lat_lng' => array(
                    'fields' => array(
                        'lat' => array('sorting' => 'ascending'),
                        'lng' => array('sorting' => 'ascending'),
                    ),
                    'was' => 'lat_lng',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $settings = $field->getFieldSettings();
        $format = isset($settings['format']) ? $settings['format'] : $this->_fieldTypeInfo()['default_settings']['format'];
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_array($value)) continue;

            foreach (array('city', 'zip', 'country', 'province') as $key) {
                if (isset($value[$key])
                    && is_array($value[$key])
                ) {
                    $value[$key] = trim((string)array_shift($value[$key]));
                }
            }
            if (isset($value['lat'])
                && ($lat = trim($value['lat']))
                && ($lat = (float)$lat)
            ) {
                $value['lat'] = $lat;
                if (isset($value['lng'])
                    && ($lng = trim($value['lng']))
                    && ($lng = (float)$lng)
                ) {
                    $value['lng'] = $lng;
                } else {
                    unset($value['lat'], $value['lng'], $value['zoom']);
                }
            } else {
                unset($value['lat'], $value['lng'], $value['zoom']);
            }

            if (!isset($value['address'])
                || !strlen($value['address'])
            ) {
                $replace = [];
                foreach (['street', 'street2', 'province', 'city', 'zip', 'country'] as $key) {
                    $replace['{' . $key . '}'] = isset($value[$key]) ? (string)$value[$key] : '';
                }
                $value['address'] = self::formatAddress($format, $replace);
            }

            if ($value = array_filter($value)) {
                $ret[] = $value;
            }
        }

        return $ret;
    }

    public static function formatAddress($format, $replace)
    {
        // Replace tags
        $formatted = trim(strtr($format, $replace));
        // Replace multiple columns with single column
        $formatted = preg_replace('/,+/', ',', $formatted);
        // Replace columns with spaces in between
        $formatted = preg_replace('/,\s*,/', ',', $formatted);
        // Replace multiple spacess with single space
        $formatted = preg_replace('/\s+/', ' ', $formatted);
        // Remove starting/trailising spaces/commas
        $formatted = trim($formatted, ' ,');

        return $formatted;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        foreach (array_keys($values) as $key) {
            settype($values[$key]['lat'], 'float');
            settype($values[$key]['lng'], 'float');
        }
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Distance', 'directories-pro')),
        );
    }
    
    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $config = $this->_application->getComponent('Map')->getConfig('map');
        $order = isset($args[0]) && $args[0] === 'desc' ? 'DESC' : 'ASC';
        $lat = $config['default_location']['lat'];
        $lng = $config['default_location']['lng'];
        if (isset($args[1])) {
            if (is_array($args[1]) && !empty($args[1])) {
                // Args passed from query settings of view
                switch (count($args[1])) {
                    case 1:
                    case 2:
                        if ($args[1][0] === '_current_') {
                            if (isset($GLOBALS['drts_entity'])
                                && ($location = $GLOBALS['drts_entity']->getSingleFieldValue($fieldName))
                                && !empty($location['lat'])
                                && !empty($location['lng'])
                            ) {
                                $lat = $location['lat'];
                                $lng = $location['lng'];
                            } else {
                                $this->_application->logError('Faield fetching current entity lat/lng for sorting by distance.');
                            }
                        } else {
                            try {
                                $geo = $this->_application->Location_Api_geocode($args[1][0], false);
                                $lat = $geo['lat'];
                                $lng = $geo['lng'];
                            } catch (Exception\IException $e) {
                                $this->_application->logError('Faield fetching lat/lng of ' . $args[1][0] . ' for sorting by distance. Geocode error: ' . $e);
                            }
                        }
                        break;
                    default:
                        $lat = $args[1][0];
                        $lng = $args[1][1];
                }
            } else {
                if (isset($args[2])) {
                    $lat = $args[1];
                    $lng = $args[2];
                }
            }
        }
        if (empty($lat) || empty($lng)) return;
        
        $query->sortByExtraField('distance', $order)->addExtraField(
            'distance',
            $fieldName,
            sprintf(
                '(%1$d * acos(cos(radians(%3$.6F)) * cos(radians(%2$s.lat)) * cos(radians(%2$s.lng) - radians(%4$.6F)) + sin(radians(%3$.6F)) * sin(radians(%2$s.lat))))',
                $config['distance_unit'] === 'mi' ? 3959 : 6371,
                $fieldName,
                $lat,
                $lng
            ),
            true
        );
    }
    
    public function fieldSchemaProperties()
    {
        return array('address', 'geo', 'location');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getFieldValue($field->getFieldName())) return;
     
        $ret = [];
        switch ($property) {
            case 'address':
            case 'location':
                if ($field->Bundle
                    && ($location_bundle = $this->_getLocationBundle($field->Bundle))
                ) {
                    $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle);
                    if (!isset($location_hierarchy['country'])
                        && !isset($location_hierarchy['province'])
                        && !isset($location_hierarchy['city'])
                    ) {
                        unset($location_hierarchy);
                    }
                }
                foreach ($value as $_value) {
                    $_ret = [
                        '@type' => 'PostalAddress',
                        'addressCountry' => $_value['country'],
                        'addressRegion' => $_value['province'],
                        'addressLocality' => $_value['city'],
                        'postalCode' => $_value['zip'],
                        'streetAddress' => $_value['street'],
                    ];
                    if (isset($location_hierarchy)
                        && !empty($_value['term_id'])
                        && ($term = $entity->getSingleFieldValue($location_bundle->type))
                    ) {
                        $location_titles = (array)$term->getCustomProperty('parent_titles');
                        $location_titles[$term->getId()] = $term->getTitle();
                        foreach (array_keys($location_hierarchy) as $key) {
                            if (!$prop = (string)array_shift($location_titles)) break;

                            switch ($key) {
                                case 'country':
                                    $prop_name = 'addressCountry';
                                    break;
                                case 'province':
                                    $prop_name = 'addressRegion';
                                    break;
                                case 'city':
                                    $prop_name = 'addressLocality';
                                    break;
                                default:
                                    continue;
                            }
                            $_ret[$prop_name] = $prop;
                        }
                    }
                    $ret[] = $_ret;
                }
                break;
            case 'geo':
                foreach ($value as $_value) {
                    $ret[] = array(
                        '@type' => 'GeoCoordinates',
                        'latitude' => $_value['lat'],
                        'longitude' => $_value['lng'],
                    );
                }
                break;
        }
        return $ret;
    }
    
    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => 'New York USA,10',
            'tip' => __('Enter an address (no commas) to query by address. Enter two values (address, radius) separated with a comma to specify a search radius. Enter three values (latitude, longitude, radius) separated with commas to query by coordinates. Enter "_current_" for the address of the current post if any.', 'directories-pro'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if (!$field = $this->_application->Entity_Field($bundle, $fieldName)) return;
        
        if (!$params = $this->_queryableParams($paramStr)) return;
                
        switch (count($params)) {
            case 1:
                if ($params[0] === '_current_') {
                    if (!isset($GLOBALS['drts_entity'])
                        || (!$location = $GLOBALS['drts_entity']->getSingleFieldValue($fieldName))
                        || empty($location['address'])
                    ) return;
                        
                    $params[0] = $location['address'];
                }
                $geo = $this->_application->Location_Api_geocode($params[0], false);
                $query->fieldIsOrGreaterThan($fieldName, $geo['viewport'][0], 'lat')
                    ->fieldIsOrSmallerThan($fieldName, $geo['viewport'][2], 'lat')
                    ->fieldIsOrGreaterThan($fieldName, $geo['viewport'][1], 'lng')
                    ->fieldIsOrSmallerThan($fieldName, $geo['viewport'][3], 'lng');
                return;
            case 2:
                if ($params[0] === '_current_') {
                    if (!isset($GLOBALS['drts_entity'])
                        || (!$location = $GLOBALS['drts_entity']->getSingleFieldValue($fieldName))
                        || empty($location['lat'])
                        || empty($location['lng'])
                    ) return;
                        
                    $lat = $location['lat'];
                    $lng = $location['lng'];
                } else {
                    $geo = $this->_application->Location_Api_geocode($params[0], false);
                    $lat = $geo['lat'];
                    $lng = $geo['lng'];
                }
                $radius = (int)$params[1];
                break;
            default:
                $lat = $params[0];
                $lng = $params[1];
                $radius = (int)$params[2];
                break;
        }
        $query->addCriteria($this->_application->Map_IsNearbyCriteria($lat, $lng, $field, $radius));
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('business:contact_data', 'place:location');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'business:contact_data':
                return array(
                    'business:contact_data:street_address' => $value['street'],
                    'business:contact_data:locality' => $value['city'],
                    'business:contact_data:region' => $value['province'],
                    'business:contact_data:postal_code' => $value['zip'],
                    'business:contact_data:country_name' => $value['country'],
                );
            case 'place:location':
                return array(
                    'place:location:latitude' => $value['lat'],
                    'place:location:longitude' => $value['lng'],
                );
        }
    }
    
    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $value['address'];
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    public function fieldConditionableInfo(Field\IField $field)
    {
        if (!$field->Bundle
            || (!$location_bundle = $this->_getLocationBundle($field->Bundle))
        ) return;
        
        return [
            'term_id' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter taxonomy term IDs and/or slugs separated with commas.', 'directories-pro'),
                'example' => '1,5,new-york',
                //'label' => $location_bundle->getLabel('singular'),
            ],
        ];
    }
    
    public function fieldConditionableRule(Field\IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                }
                return ['type' => $compare, 'value' => $value, 'target' => '.drts-location-term-select'];
            case 'empty':
                return ['type' => 'filled', 'value' => false, 'target' => '.drts-location-term-select'];
            case 'filled':
                return ['type' => 'empty', 'value' => false, 'target' => '.drts-location-term-select'];
            default:
                return;
        }
    }

    public function fieldConditionableMatch(Field\IField $field, array $rule, array $values = null)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if ($input['term_id'] == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            if ($rule['type'] === 'one') return true;
                            continue 2;
                        }
                    }
                    // One of rule values did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }

    protected function _getLocationBundle(Entity\Model\Bundle $bundle)
    {
        return $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group);
    }

    public function mapCoordinates(Field\IField $field, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [$value['lat'], $value['lng']];
    }
}
