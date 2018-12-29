<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class AddView extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $this->_ajaxOnSuccessRedirect = true;
        $this->_submitButtons[] = [
            '#btn_label' => __('Add View', 'directories'),
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ];
        $settings = $this->_getSettings($context);
        $form = array(
            '#tabs' => array(
                'general' => array(
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ),
            ),
            '#tab_style' => 'pill',
            'general' => array(
                '#tree' => true,
                '#tab' => 'general',
                'label' => array(
                    '#type' => 'textfield',
                    '#title' => __('View label', 'directories'),
                    '#description' => __('Enter a label used for administration purpose only.', 'directories'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#horizontal' => true,
                ),
                'name' => array(
                    '#type' => 'textfield',
                    '#title' => __('View name', 'directories'),
                    '#description' => __('Enter a unique name so that that it can be easily referenced. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#regex' => '/^[a-z0-9_]+$/',
                    '#horizontal' => true,
                    '#states' => array(
                        'slugify' => array(
                            'input[name="general[label]"]' => array('type' => 'filled', 'value' => true),
                        ),
                    ),
                    '#element_validate' => array(array(array($this, '_validateName'), array($context->bundle))),
                ),
                'mode' => array(
                    '#title' => __('View mode', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [],
                    '#default_value' => $settings['mode'],
                ),
                'mode_settings' => array(
                    '#tree' => true,
                ),
            ),
        );
        
        foreach (array_keys($this->View_Modes()) as $view_mode_name) {
            if ((!$view_mode = $this->View_Modes_impl($view_mode_name, true))
                || !$view_mode->viewModeSupports($context->bundle)
            ) continue;
            
            $form['general']['mode']['#options'][$view_mode_name] = $view_mode->viewModeInfo('label');
            $form['general']['mode_settings'][$view_mode_name] = $this->View_Modes_settingsForm(
                $view_mode,
                $context->bundle,
                $settings['mode'] === $view_mode_name ? $settings['settings'] : [],
                ['general', 'mode_settings', $view_mode_name],
                $this->_getSubimttedValues($context, $formStorage)
            );
            $form['general']['mode_settings'][$view_mode_name]['#states'] = array(
                'visible' => array(
                    'select[name="general[mode]"]' => array('value' => $view_mode_name),
                ),
            );
        }
        
        $form += $this->View_FeatureSettingsForm($context->bundle, $settings['settings']);
        
        return $form;
    }
    
    public function _validateName(Form\Form $form, &$value, $element, $bundle)
    {        
        $query = $this->getModel('View', 'View')->bundleName_is($bundle->name)->name_is($value);        
        if ($query->count()) {
            $form->setError(__('The name is already taken.', 'directories'), $element);
        }
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return dirname($context->getRoute());
    }
    
    protected function _saveConfig(Context $context, array $values, Form\Form $form)
    {      
        $name = $values['general']['name'];
        $mode = $values['general']['mode'];
        $label = $values['general']['label'];
        $settings = $values['general']['mode_settings'][$mode];
        unset($values['general']);
        $view = $this->View_AdminView_add($context->bundle, $name, $mode, $label, $settings + $values);
        $this->View_AdminView_setDefault($context->bundle, $view);
    }
    
    protected function _getSettings(Context $context)
    {
        return ['mode' => null, 'settings' => []];
    }
}