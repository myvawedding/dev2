<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Field\IField;

class ChoiceOptionsHelper
{
    public function help(Application $application, IField $field, $language = null)
    {
        if ($field->getFieldType() !== 'choice') throw new Exception\RuntimeException('Invalid field type');
        
        $field_settings = $field->getFieldSettings();
        $options = $field_settings['options'];
        
        if ($field->bundle_name) {
            foreach (array_keys($options['options']) as $key) {
                $options['options'][$key] = $application->getPlatform()->translateString(
                    $options['options'][$key],
                    $field->bundle_name . '_' . $field->getFieldName() . '_choice_' . $key,
                    'entity_field',
                    $language
                );
            }
        }
        
        return $options;
    }
}