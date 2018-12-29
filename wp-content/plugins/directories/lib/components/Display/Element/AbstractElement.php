<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Assets;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

abstract class AbstractElement implements IElement
{
    /** @var Application */
    protected $_application;
    /** @var string */
    protected $_name;
    /** @var array */
    protected $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function displayElementInfo(Entity\Model\Bundle $bundle, $key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_displayElementInfo($bundle);
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
        
    public function displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->isAmp()
            && !$this->_displayElementSupportsAmp($bundle, $display)
        ) return false;
        
        return $this->_displayElementSupports($bundle, $display);
    }
        
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return true;
    }
    
    protected function _displayElementSupportsAmp(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return false;
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = []){}
    
    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return isset($element['heading']['label']) ? $this->_application->Display_ElementLabelSettingsForm_label($element['heading']) : '';
    }
    
    public function displayElementAdminAttr(Entity\Model\Bundle $bundle, array $settings)
    {
        return [];
    }
        
    public function displayElementIsEnabled(Entity\Model\Bundle $bundle, array $element, Display\Model\Display $display)
    {
        return true;
    }
    
    public function displayElementIsDimmed(Entity\Model\Bundle $bundle, array $settings)
    {
        return false;
    }
    
    public function displayElementIsInlineable(Entity\Model\Bundle $bundle, array $settings)
    {
        return (bool)$this->displayElementInfo($bundle, 'inlineable');
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element, $displayType)
    {
        $ret = false;
        if (!empty($element['children'])) {
            foreach (array_keys($element['children']) as $child_id) {
                if ($element_impl = $this->_application->Display_Elements_impl($bundle, $element['children'][$child_id]['name'], true)) {
                    $element['children'][$child_id]['parent_visibility'] = empty($element['parent_visibility']) ? [] : $element['parent_visibility'];
                    if (!empty($element['visibility'])) {
                        $element['children'][$child_id]['parent_visibility'][] = $element['visibility'];
                    }
                    if ($element_impl->displayElementIsPreRenderable($bundle, $element['children'][$child_id], $displayType)) {
                        $element['children'][$child_id]['pre_render'] = true;
                        $ret = true;
                    }
                }
            }
        }
        return $ret;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, $displayType, &$var)
    {
        if (empty($element['children'])) return;
        
        foreach ($element['children'] as $child) {
            if (!empty($child['pre_render'])
                && ($element_impl = $this->_application->Display_Elements_impl($bundle, $child['name'], true))
            ) {
                $element_impl->displayElementPreRender($bundle, $child, $displayType, $var);
            }
        }
    }
    
    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight){}
    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, $weight){}
    public function displayElementOnExport(Entity\Model\Bundle $bundle, array &$data){}
    public function displayElementOnRemoved(Entity\Model\Bundle $bundle, array $settings){}    
    public function displayElementOnPositioned(Entity\Model\Bundle $bundle, array $settings, $weight){}
    
    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        if (isset($element->data['heading']['label'])) {
            $this->_application->Display_ElementLabelSettingsForm_registerLabel(
                $element->data['heading'],
                $this->displayElementStringId('heading', $element->element_id)
            );
        }
    }

    public function displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        return $this->_application->Filter(
            'display_element_readable_info',
            (array)$this->_displayElementReadableInfo($bundle, $element),
            [$bundle, $element]
        );
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        return [];
    }
    
    protected function _registerString($str, $name, $id, $elementName = null)
    {
        $this->_application->getPlatform()->registerString($str, $this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    protected function _unregisterString($name, $id, $elementName = null)
    {
        $this->_application->getPlatform()->unregisterString($this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    protected function _translateString($str, $name, $id, $elementName = null)
    {
        return $this->_application->getPlatform()->translateString($str, $this->displayElementStringId($name, $id, $elementName), 'display_element');
    }
    
    public function displayElementStringId($name, $id, $elementName = null)
    {
        return self::stringId(isset($elementName) ? $elementName : $this->_name, $name, $id);
    }
    
    public static function stringId($elementName, $name, $id)
    {
        return $elementName . '_' . $name . '_' . $id;
    }
    
    protected function _renderChildren(Entity\Model\Bundle $bundle, array $children, $var)
    {
        if (empty($children)) return;
        
        $ret = [];
        foreach ($children as $child) {
            $child_content = call_user_func_array(
                array($this->_application, 'Display_Render_element'),
                array($bundle, $child, $var)
            );
            if ($child_content) {
                $ret[] = $child_content;
            }
        }
        return $ret;
    }

    abstract protected function _displayElementInfo(Entity\Model\Bundle $bundle);
}
