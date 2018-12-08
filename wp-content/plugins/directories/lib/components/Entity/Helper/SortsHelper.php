<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;

class SortsHelper
{    
    /**
     * @param Application $application
     * @param string $bundleName
     */
    public function help(Application $application, $bundleName, $useCache = true)
    {
        if (!$useCache
            || (!$ret = $application->getPlatform()->getCache('entity_sorts_' . $bundleName))
        ) {
            $ret = [];
            foreach ($application->Entity_Field($bundleName) as $field) {                
                if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\ISortable
                    || (false === $sort_options = $field_type->fieldSortableOptions($field))
                ) continue;

                $field_title = (string)$field;
                if (is_array($sort_options)) {
                    foreach ($sort_options as $sort_option) {
                        $name = $field->getFieldName();
                        if (!empty($sort_option['args'])) {
                            $name .= ',' . implode(',', $sort_option['args']);
                        }
                        $ret[$name] = array(
                            'label' => isset($sort_option['label']) ? sprintf($sort_option['label'], $field_title) : $field_title,
                            'field_name' => $field->getFieldName(),
                            'field_type' => $field->getFieldType(),
                        );
                    }
                } else {
                    $ret[$field->getFieldName()] = array(
                        'label' => $field_title,
                        'field_name' => $field->getFieldName(),
                        'field_type' => $field->getFieldType(),
                    );
                }
            }
            $ret['random'] = array('label' => __('Random', 'directories'));
            $application->getPlatform()->setCache($application->Filter('entity_sorts', $ret, [$bundleName]), 'entity_sorts_' . $bundleName);
        }
        return $ret;
    }
}