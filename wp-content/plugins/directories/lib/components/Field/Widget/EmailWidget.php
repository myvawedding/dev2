<?php
namespace SabaiApps\Directories\Component\Field\Widget;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class EmailWidget extends TextfieldWidget
{
    protected function _fieldWidgetInfo()
    {
        $info = parent::_fieldWidgetInfo();
        $info['field_types'] = array($this->_name);
        $info['default_settings']['autopopulate'] = false;
        return $info;
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $form = parent::fieldWidgetSettingsForm($fieldType, $bundle, $settings, $parents);
        $form['autopopulate'] = array(
            '#type' => 'checkbox',
            '#title' => __("Auto-populate field with the current user's e-mail address", 'directories'),
            '#default_value' => $settings['autopopulate'],
        );
        return $form;
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        $form = parent::fieldWidgetForm($field, $settings, $value, $entity, $parents, $language);
        if (!isset($entity)
            && !empty($settings['autopopulate'])
        ) {
            $form['#autopopulate'] = 'email';
        }
        $field_settings = $field->getFieldSettings();
        $form['#check_mx'] = !empty($field_settings['check_mx']);

        return $form;
    }
}