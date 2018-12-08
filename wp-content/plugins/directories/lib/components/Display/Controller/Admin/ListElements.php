<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;

class ListElements extends Controller
{
    protected $_defaultType = 'field';
    
    protected function _doExecute(Context $context)
    {
        if ((!$display = $this->_getDisplay($context))
            || (!$bundle = $this->Entity_Bundle($display->bundle_name))
        ) {
            $context->setError();
            return;
        }
        $requested_type = $context->getRequest()->asStr('type');
        $element_types = $this->Display_Elements_types($bundle);
        $elements = [];
        foreach (array_keys($this->Display_Elements($bundle, false)) as $element_name) {
            if ((!$element = $this->Display_Elements_impl($bundle, $element_name, true))
                || !$element->displayElementSupports($bundle, $display)
                || false === $element->displayElementInfo($bundle, 'listable')
                || ($requested_type && $element->displayElementInfo($bundle, 'type') !== $requested_type)
                || (($displays = $element->displayElementInfo($bundle, 'displays')) && !in_array($display->name, (array)$displays))
            ) continue;
            
            $info = $element->displayElementInfo($bundle);
            if (!empty($info['parent_element_name'])) continue;
           
            $elements[(string)@$info['type']][$element_name] = $info;
        }
        $sorter = function ($a, $b) {
            return strnatcmp($a['label'], $b['label']);
        };
        foreach (array_keys($elements) as $element_type) {
            uasort($elements[$element_type], $sorter);
        }
        foreach (array_keys($element_types) as $element_type) {
            if (empty($elements[$element_type])) unset($element_types[$element_type]);
        }
        $context->addTemplate(count($element_types) > 1 ? 'display_admin_elements_tabbed' : 'display_admin_elements')
            ->setAttributes(array(
                'element_types' => $element_types,
                'elements' => $elements,
                'default_type' => $this->_defaultType,
            ));
    }
    
    protected function _getDisplay(Context $context)
    {
        if ((!$display_id = $context->getRequest()->asInt('display_id'))
            || (!$display = $this->getModel('Display', 'Display')->fetchById($display_id))
        ) return false;
        
        return $display;
    }
}