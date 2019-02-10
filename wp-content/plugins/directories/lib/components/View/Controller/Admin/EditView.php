<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class EditView extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        if ($context->getRequest()->asBool('show_settings')) {
            return array(
                'settings' => array(
                    '#type' => 'markup',
                    '#markup' => '<pre>' . print_r(array(
                        'name' => $context->view->name,
                        'mode' => $context->view->mode,
                        'label' => $context->view->getLabel(),
                        'settings' => $context->view->data['settings'],
                    ), true) . '</pre>',
                ),
            );
        }
        
        // Highlight row on success
        $this->_ajaxOnSuccessEdit = 'form.drts-view-admin-views tr[data-row-id=\'' . $context->view->id . '\']';
        
        $form = array(
            '#tabs' => array(
                'general' => array(
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ),
            ),
            '#tab_style' => 'pill',
            'general' => array(
                '#tab' => 'general',
                '#tree' => true,
                'label' => array(
                    '#type' => 'textfield',
                    '#title' => __('View label', 'directories'),
                    '#description' => __('Enter a label used for administration purpose only.', 'directories'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#horizontal' => true,
                    '#default_value' => $context->view->getLabel(),
                ),
                'name' => array(
                    '#type' => 'textfield',
                    '#title' => __('View name', 'directories'),
                    '#description' => __('Enter a unique name so that it can be easily referenced. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#regex' => '/^[a-z0-9_]+$/',
                    '#horizontal' => true,
                    '#slugify' => true,
                    '#element_validate' => array(array(array($this, '_validateName'), array($context->bundle, $context->view->id))),
                    '#default_value' => $context->view->name,
                ),
                'mode' => array(
                    '#title' => __('View mode', 'directories'),
                    '#type' => 'select',
                    '#horizontal' => true,
                    '#options' => [],
                    '#default_value' => $context->view->mode,
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
                $context->view->mode === $view_mode_name ? $context->view->data['settings'] : [],
                ['general', 'mode_settings', $view_mode_name],
                $this->_getSubimttedValues($context, $formStorage)
            );
            $form['general']['mode_settings'][$view_mode_name]['#states'] = array(
                'visible' => array(
                    'select[name="general[mode]"]' => array('value' => $view_mode_name),
                ),
            );
        }
        
        $form += $this->View_FeatureSettingsForm($context->bundle, $context->view->data['settings'], (bool)$context->view->default);
        
        return $form;
    }
    
    public function _validateName(Form\Form $form, &$value, $element, $bundle, $viewId)
    {        
        $query = $this->getModel('View', 'View')->bundleName_is($bundle->name)->name_is($value)->id_isNot($viewId);        
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
        $settings = isset($values['general']['mode_settings'][$mode]) ? $values['general']['mode_settings'][$mode] : array();
        unset($values['general']);
        $view = $this->View_AdminView_update($context->bundle, $context->view, $name, $mode, $label, $settings + $values);
        $this->View_AdminView_setDefault($context->bundle, $view);
    }
}