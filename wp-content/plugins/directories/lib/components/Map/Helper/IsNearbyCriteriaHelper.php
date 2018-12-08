<?php
namespace SabaiApps\Directories\Component\Map\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\Criteria\IsOrSmallerThanCriteria;
use SabaiApps\Directories\Component\Field\IField;

class IsNearbyCriteriaHelper
{    
    public function help(Application $application, $lat, $lng, IField $field, $radius = 100)
    {
        $alias = $field->getFieldType();
        $target = array(
            'tables' => array(
                $application->getDB()->getResourcePrefix() . 'entity_field_' . $field->getFieldType()  => array(
                    'alias' => $alias,
                    'on' => null,
                    'field_name' => $field->getFieldName(),
                ),
            ),
            'column' => sprintf(
                '(%1$d * acos(cos(radians(%2$.6F)) * cos(radians(%4$s.lat)) * cos(radians(%4$s.lng) - radians(%3$.6F)) + sin(radians(%2$.6F)) * sin(radians(%4$s.lat))))',
                $application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi' ? 3959 : 6371,
                $lat,
                $lng,
                $alias
            ),
            'column_type' => Application::COLUMN_DECIMAL,
        );
        
        return new IsOrSmallerThanCriteria($target, $radius);
    }
}