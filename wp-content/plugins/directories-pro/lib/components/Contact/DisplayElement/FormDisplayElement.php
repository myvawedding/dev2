<?php
namespace SabaiApps\Directories\Component\Contact\DisplayElement;

use SabaiApps\Directories\Component\Display\Element\AbstractElement;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class FormDisplayElement extends AbstractElement
{
    protected function _displayElementInfo(Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => __('Contact Form', 'directories-pro'),
            'description' => __('Display a form to contact the content author', 'directories-pro'),
            'default_settings' => array(
                'form' => null,
            ),
            'displays' => array('detailed'),
            'icon' => 'far fa-envelope',
        );
    }
    
    protected function _displayElementSupports(Bundle $bundle, Display $display)
    {
        return $display->type === 'entity';
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'form' => array(
                '#title' => __('Select form', 'directories-pro'),
                '#options' => $this->_application->Contact_Form_options(),
                '#type' => 'select',
                '#default_value' => $settings['form'],
                '#horizontal' => true,
            ),
        );
    }
    
    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        if (!empty($element['settings']['form'])) {        
            return $this->_application->Contact_Form($var, $element['settings']['form']);
        }
    }
}