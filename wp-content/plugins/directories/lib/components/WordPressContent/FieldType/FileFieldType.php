<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class FileFieldType extends Field\Type\AbstractType implements Field\Type\IHumanReadable
{
    public static $txtExtensions = 'txt|asc|c|cc|h|srt';

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'attachment_id' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'attachmend_id',
                    'default' => 0,
                ),
                'display_order' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'display_order',
                    'default' => 0,
                    'length' => 4,
                ),
               'featured' => array(
                    'type' => Application::COLUMN_BOOLEAN,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'featured',
                    'default' => false,
               ),
            ),
            'indexes' => array(
                'attachmend_id' => array(
                    'fields' => array('attachment_id' => array('sorting' => 'ascending')),
                    'was' => 'attachmend_id',
                ),
                'display_order' => array(
                    'fields' => array(
                        'display_order' => array('sorting' => 'ascending'),
                    ),
                    'was' => 'display_order',
                ),
            ),
        );
    }
    
    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        $_values = [];
        foreach ($values as $value) {
            $_values[$value['display_order']] = array(
                'attachment_id' => $value['attachment_id'],
                'featured' => !empty($value['featured'])
            );
        }
        ksort($_values);
        $values = array_values($_values);
    }
    
    public function fieldTypeOnSave(Field\IField $field, array $values)
    {
        $ret = [];
        $i = 0;
        foreach ($values as $value) {
            if (empty($value)) continue;
            
            $ret[] = array(
                'attachment_id' => $value,
                'display_order' => $i,
                'featured' => false,
            );
            ++$i;
        }
        return $ret;
    }
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {   
        $current = [];
        foreach ($currentLoadedValue as $key => $value) {
            $current[] = array('display_order' => $key, 'attachment_id' => $value);
        }
        return $current !== $valueToSave;
    }
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => _x('File', 'field type', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'icon' => 'far fa-file-alt',
            'default_settings' => array(
                'max_file_size' => 2048,
                'allowed_files' => '',
                'allowed_extensions' => array(self::$txtExtensions, 'pdf', 'zip'),
            ),
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        if ($fieldType instanceof \SabaiApps\Directories\Component\Field\IField) {
            $settings += $fieldType->getFieldWidgetSettings(); // compat with <1.1.x
        }
        $options = [];
        foreach (array_keys(get_allowed_mime_types()) as $ext) {
            if (strpos($ext, '|')) {
                if ($ext === self::$txtExtensions) {
                    $options[$ext] = 'txt';
                } else {
                    $options[$ext] = str_replace('|', ' / ', $ext);
                }
            } else {
                $options[$ext] = $ext;
            }
        }
        return array(
            'max_file_size' => array(
                '#type' => 'textfield',
                '#title' => __('Maximum file size', 'directories'),
                '#description' => __('The maximum file size of uploaded files in kilobytes. Leave this field blank for no limit.', 'directories'),
                '#size' => 7,
                '#integer' => true,
                '#field_suffix' => 'KB',
                '#default_value' => $settings['max_file_size'],
                '#weight' => 2,
            ),
            'allowed_files' => [
                '#type' => 'select',
                '#title' => __('Allowed files', 'directories'),
                '#default_value' => $settings['allowed_files'],
                '#required' => true,
                '#weight' => 1,
                '#options' => [
                    '' => _x('Any', 'option', 'directories'),
                    'image' => _x('Image files', 'directories'),
                    'video' => _x('Video files', 'directories'),
                    'audio' => _x('Audio files', 'directories'),
                ],
                '#columns' => 3,
            ],
            'allowed_extensions' => array(
                '#type' => 'checkboxes',
                '#title' => __('Allowed file extensions', 'directories'),
                '#default_value' => $settings['allowed_extensions'],
                '#required' => true,
                '#weight' => 1,
                '#options' => $options,
                '#columns' => 3,
                '#states' => [
                    'visible' => [
                        '[name="' . $this->_application->Form_FieldName(array_merge($parents, ['allowed_files'])) . '"]' => ['value' => '']
                    ],
                ],
            ),
        );
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $value) {
            if ($url = wp_get_attachment_url($value['attachment_id'])) {
                $ret[] = $url;
            }
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
}