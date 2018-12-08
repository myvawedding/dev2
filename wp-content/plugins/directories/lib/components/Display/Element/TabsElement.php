<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class TabsElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Tabs', 'display element name', 'directories'),
            'description' => 'Adds a horizontal tabbed content area',
            'default_settings' => [],
            'containable' => true,
            'child_element_name' => 'tab',
            'child_element_create' => 2,
            'add_child_label' => __('Add Tab', 'directories'),
            'icon' => 'far fa-folder',
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        if ($isEdit) return;
        
        return array(
            'tabs' => array(
                '#type' => 'options',
                '#title' => __('Tabs', 'directories'),
                '#options' => array(
                    'tab1' => __('Tab label', 'directories'),
                    'tab2' => __('Tab label', 'directories'),
                ),
                '#default_value' => array('tab1', 'tab2'),
                '#hide_value' => true,
                '#slugify_value' => true,
                '#multiple' => true,
                '#horizontal' => true,
                '#disable_icon' => true,
            ),
        );
    }
    
    public function displayElementCreateChildren(Entity\Model\Bundle $bundle, Display\Model\Display $display, array $settings, $parentId)
    {
        $ret = [];
        if (!empty($settings['tabs']['default'])) {
            foreach ($settings['tabs']['default'] as $tab_name) {
                $ret[] = $this->_application->Display_AdminElement_create($bundle, $display, 'tab', $parentId, array('settings' => array('label' => $settings['tabs']['options'][$tab_name])));
            }
        }
        return $ret;
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (empty($element['children'])) return;

        $tabs = [];
        $active_tab = null;
        foreach ($element['children'] as $child) {
            if (!isset($active_tab)) {
                $active_tab = $child['id'];
            }
            $tabs[$child['id']] = array(
                'label' => $this->_translateString($child['settings']['label'], 'label', $child['id'], 'tab'),
                'content' => $this->_application->callHelper('Display_Render_element', array($bundle, $child, $var)),
            );
        }
        
        $content = [];
        $ret = array('<div class="' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'mb-4">');
        foreach ($tabs as $element_id => $tab) {
            $is_active = $active_tab === $element_id;
            $ret[] = sprintf(
                '<a href="#" class="%1$snav-item %1$snav-link %2$s" data-target="#drts-display-element-tabs-%3$d" data-toggle="%1$stab" id="drts-display-element-tabs-%3$d-trigger">%4$s</a>',
                DRTS_BS_PREFIX,
                $is_active ? DRTS_BS_PREFIX . 'active' : '',
                $element_id,
                $tab['label']
            );
            $content[] = sprintf(
                '<div class="%1$stab-pane %1$sfade%2$s" id="drts-display-element-tabs-%3$d">
    %4$s
</div>',
                DRTS_BS_PREFIX,
                $is_active ? ' ' . DRTS_BS_PREFIX . 'show ' . DRTS_BS_PREFIX . 'active' : '',
                $element_id,
                $tab['content']
            );
        }
        $ret[] = '</div><div class="' . DRTS_BS_PREFIX . 'tab-content">';
        $ret[] = implode(PHP_EOL, $content);
        $ret[] = '</div>';
        
        return implode(PHP_EOL, $ret);
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type !== 'filters';
    }
}
