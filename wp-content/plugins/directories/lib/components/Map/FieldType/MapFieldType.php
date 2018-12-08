<?php
namespace SabaiApps\Directories\Component\Map\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;

class MapFieldType extends Field\Type\AbstractType implements
    Field\Type\ISortable,
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\IOpenGraph,
    ICoordinates
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Map', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'icon' => 'far fa-map',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
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

    public function fieldTypeOnSave(Field\IField $field, array $values)
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_array($value)) continue;

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
            if ($value = array_filter($value)) {
                $ret[] = $value;
            }
        }

        return $ret;
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
            array('label' => __('Distance', 'directories')),
        );
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $config = $this->_application->getComponent('Map')->getConfig('map');
        $order = isset($args[0]) && $args[0] === 'desc' ? 'DESC' : 'ASC';
        if (isset($args[1])
            && isset($args[2])
        ) {
            $lat = $args[1];
            $lng = $args[2];
        } else {
            $lat = $config['default_location']['lat'];
            $lng = $config['default_location']['lng'];
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
        return array('geo');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getFieldValue($field->getFieldName())) return;
     
        $ret = [];
        switch ($property) {
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
            'example' => '40.69847,-73.95144,10',
            'tip' => __('Enter three values (latitude, longitude, radius) separated with commas to query by coordinates.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $params = $this->_queryableParams($paramStr);             
        if (count($params) !== 3
            || (!$field = $this->_application->Entity_Field($bundle, $fieldName))
        ) return;

        $lat = $params[0];
        $lng = $params[1];
        $radius = (int)$params[2];
        $query->addCriteria($this->_application->Map_IsNearbyCriteria($lat, $lng, $field, $radius));
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('place:location');
    }
    
    public function fieldOpenGraphRenderProperty(Field\IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        switch ($property) {
            case 'place:location':
                return array(
                    'place:location:latitude' => $value['lat'],
                    'place:location:longitude' => $value['lng'],
                );
        }
    }

    public function mapCoordinates(IField $field, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return [$value['lat'], $value['lng']];
    }
}