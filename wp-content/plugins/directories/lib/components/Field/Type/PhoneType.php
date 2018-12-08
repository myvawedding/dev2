<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;

class PhoneType extends StringType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Phone Number', 'directories'),
            'default_widget' => $this->_name,
            'default_renderer' => $this->_name,
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'mask' => '(999) 999-9999',
            ),
            'icon' => 'fas fa-phone',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $form = parent::fieldTypeSettingsForm($fieldType, $bundle, $settings, $parents);
        unset($form['char_validation'], $form['regex'], $form['min_length'], $form['max_length']);
        return $form;
    }

    public function fieldSchemaProperties()
    {
        return array('telephone', 'faxNumber');
    }
}
