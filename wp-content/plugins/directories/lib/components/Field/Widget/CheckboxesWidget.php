<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class CheckboxesWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Checkboxes', 'directories'),
            'field_types' => array('choice'),
            'accept_multiple' => true,
            'default_settings' => array(
                'columns' => 3,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'columns'  => array(
                '#type' => 'select',
                '#inline' => true,
                '#title' => __('Number of columns', 'directories'),
                '#options' => array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 6 => 6, 12 => 12),
                '#default_value' => $settings['columns'],
            ),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $options = $this->_application->Field_ChoiceOptions($field, $language);
        if (isset($value)) {
            $default_value = empty($value) ? null : array_values($value);
        } else {
            $default_value = empty($options['default']) ? null : $options['default']; 
        }
        $form = array(
            '#type' => 'checkboxes',
            '#options' => $options['options'],
            '#max_selection' => $field->getFieldMaxNumItems(),
            '#default_value' => $default_value,
            '#columns' => $settings['columns'],
        );
        if (!empty($options['icons'])) {
            $form['#option_no_escape'] = true;
            foreach (array_keys($form['#options']) as $value) {
                $form['#options'][$value] = $this->_application->H($form['#options'][$value]);
                if (!empty($options['icons'][$value])) {
                    $form['#options'][$value] = '<i class="fa-fw ' . $options['icons'][$value] . '"></i> ' . $form['#options'][$value];
                }
            }
        }
        
        return $form;
    }
}