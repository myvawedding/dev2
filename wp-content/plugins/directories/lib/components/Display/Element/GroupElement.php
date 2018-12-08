<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class GroupElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Group', 'display element name', 'directories'),
            'description' => __('Group multiple display elements', 'directories'),
            'default_settings' => array(
                'inline' => false,
                'separator' => null,
            ),
            'containable' => true,
            'positionable' => true,
            'icon' => 'far fa-object-group',
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'inline' => array(
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ),
            'separator' => array(
                '#type' => 'textfield',
                '#title' => __('Element separator', 'directories'),
                '#default_value' => $settings['separator'],
                '#horizontal' => true,
                '#no_trim' => true,
            ),
        );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var, $element)) return;
        
        $settings = $element['settings'];
        $separator = strlen($settings['separator']) ? '<div class="drts-display-group-element-separator">' . $settings['separator'] . '</div>' : PHP_EOL;
        
        return array(
            'html' =>  implode($separator, $html),
            'style' => '',
            'class' => $element['settings']['inline'] ? 'drts-display-group-inline' : '',
        );
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'inline' => [
                'label' => __('Display inline', 'directories'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
            ],
            'separator' => [
                'label' => __('Element separator', 'directories'),
                'value' => $settings['separator'],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}