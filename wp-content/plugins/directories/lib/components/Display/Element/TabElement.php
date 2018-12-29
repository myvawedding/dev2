<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class TabElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => $label = _x('Tab', 'display element name', 'directories'),
            'default_settings' => array(
                'label' => $label,
            ),
            'containable' => true,
            'positionable' => true,
            'parent_element_name' => 'tabs',
            'icon' => 'far fa-folder',
            'headingable' => false,
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'label' => array(
                '#title' => __('Tab label', 'directories'),
                '#type' => 'textfield',
                '#default_value' => $settings['label'],
                '#horizontal' => true,
            ),
        );
    }

    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['label']);
    }
    
    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $this->_registerString($element->data['settings']['label'], 'label',  $element->element_id);
        $this->_unregisterString('label', $element->id); // for old versions
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var, $element)) return;
        
        return implode(PHP_EOL, $html);
    }
}