<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class TextareaWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Textarea field', 'directories'),
            'field_types' => array('text', 'wp_post_content'),
            'default_settings' => array(
                'rows' => 10,
                'nl2br' => false,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'rows' => array(
                '#type' => 'slider',
                '#min_value' => 1,
                '#max_value' => 50,
                '#integer' => true,
                '#title' => __('Rows', 'directories'),
                '#default_value' => $settings['rows'],
            ),
            'nl2br' => array(
                '#type' => 'checkbox',
                '#title' => __('Preserve line breaks'),
                '#default_value' => $settings['nl2br'],
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (isset($value)) {
            $_value = is_string($value) ? $value : (is_array($value) ? $value['value'] : null);
        } else {
            $_value = null;
        }
        $field_settings = $field->getFieldSettings();
        return array(
            '#type' => 'textarea',
            '#rows' => $settings['rows'],
            '#default_value' => $_value,
            '#min_length' => isset($field_settings['min_length']) ? intval($field_settings['min_length']) : null,
            '#max_length' => isset($field_settings['max_length']) ? intval($field_settings['max_length']) : null,
        );
    }

    public function fieldWidgetEditDefaultValueForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            '#type' => 'textarea',
            '#rows' => $settings['rows'] > 5 ? 5 : $settings['rows'],
        );
    }
    
    public function fieldWidgetSetDefaultValue(IField $field, array $settings, array &$form, $value)
    {
        if (strlen($value)) {
            $form['#default_value'] = $value;
        }
    }
    
    public function fieldWidgetFormatText(IField $field, array $settings, $value, Entity\Type\IEntity $entity)
    {
        if (!strlen($value)) {
            return '';
        }
        $value = strip_tags($value);
        if (!empty($settings['nl2br'])) {
            $value = nl2br($value);
        }
        return '<p>' . $value . '</p>';
    }
}