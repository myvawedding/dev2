<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\System;

abstract class AbstractDisplays extends System\Controller\Admin\AbstractSettings
{
    protected $_displayType = 'entity', $_displays =[], $_enableCSS = false, $_hideTabsIfSingle = true;
    
    abstract protected function _getDisplays(Context $context);
    
    protected function _getDisplay(Context $context, $displayName)
    {
        return $this->Display_Display($context->bundle->name, $displayName, $this->_displayType, true, true);
    }
        
    protected function _getDisplayWeight(array $display)
    {
        return $display['name'] === 'default' ? 1 : ($display['is_amp'] ? 20 : 10);
    }
    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = array('#tabs' => array(), '#tab_style' => 'pill_less_margin', '#displays' => []);
        foreach ($this->_getDisplays($context) as $display_name => $display_label) {
            if (!$display = $this->_getDisplay($context, $display_name)) continue;
            
            $form['#tabs'][$display_name] = [
                '#title' => isset($display_label) ? $display_label : __('Default', 'sabai_plugin_name-'),
                '#weight' => $this->_getDisplayWeight($display),
            ];
            $form['#displays'][] = $display_name;
            $form[$display_name] = [
                '#tree' => true,
                '#tab' => $display_name,
                'elements' => [
                    '#type' => 'display_elements',
                    '#display' => $display,
                    '#clear_display_cache' => false,
                ],
            ];
            if ($this->_enableCSS) {
                $form[$display_name]['css'] = [
                    '#title' => __('Custom CSS', 'directories'),
                    '#description' => sprintf(
                        $this->H(__('Enter custom CSS for the display above. You can use %s to target the display with a CSS class.', 'directories')),
                        '<code>.' . $display['class'] . '</code>'
                    ),
                    '#description_top' => true,
                    '#description_no_escape' => true,
                    '#type' => 'editor',
                    '#language' => 'css',
                    '#default_value' => $display['css'],
                ];
            }
            $this->_displays[$display_name] = $display_name;
        }
        if ($this->_hideTabsIfSingle
            && count($form['#tabs']) <= 1
        ) {
            $form['#tabs'] = [];
        }
        
        return $form;
    }
    
    protected function _saveConfig(Context $context, array $config, Form\Form $form)
    {
        if ($this->_enableCSS) {
            foreach ($this->getModel('Display', 'Display')
                ->bundleName_is($context->bundle->name)
                ->type_is($this->_displayType)
                ->name_in($this->_displays)
                ->fetch() as $display
            ) {
                $data = $display->data ?: [];
                if (isset($config[$display->name]['css'])
                    && strlen($config[$display->name]['css'])
                ) {
                    $data['css'] = $config[$display->name]['css'];
                } else {
                    unset($data['css']);
                }
                $display->data = $data;
            }
            $this->getModel(null, 'Display')->commit();
        }
        
        // Clear display and elements cache
        foreach ($this->_displays as $display_name) {
            $this->Display_Display_clearCache($context->bundle->name, $this->_displayType, $display_name);
        }
        $this->getPlatform()->deleteCache('display_elements_' . $context->bundle->name);
    }
}