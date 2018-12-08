<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class AddElement extends Form\Controller
{    
    protected $_display;
    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        if ((!$this->_display = $this->_getDisplay($context))
            || (!$element_name = $context->getRequest()->asStr('element'))
            || (!$bundle = $this->Entity_Bundle($this->_display->bundle_name))
            || (!$element = $this->Display_Elements_impl($bundle, $element_name, true))
            || false === $element->displayElementInfo($bundle, 'creatable')
            || !$element->displayElementSupports($bundle, $this->_display)
        ) {
            $context->setError();
            return;
        }

        // Define form
        $form = array(
            '#header' => [],
            '#action' => $this->Url($context->getRoute()),
            '#token_reuseable' => true,
            '#enable_storage' => true,
            '#bundle' => $bundle,
            '#element_name' => $element_name,
        );

        // Set options
        $this->_cancelWeight = -99;
        $this->_submitButtons = [[
            '#btn_label' => __('Add Element', 'directories'),
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ]];
        $this->_ajaxOnSuccess = 'function(result, target, trigger) {
            $(DRTS).trigger("display_element_created.sabai", {trigger: trigger, result: result, target: target});
        }';
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxModalHideOnSend = true;

        $info = $element->displayElementInfo($bundle);
        $form += array(
            '#inherits' => array(
                'display_admin_add_element_' . strtolower($element_name),
            ),
            '#tab_style' => 'pill',
            '#tabs' => array(
                'general' => array(
                    '#active' => true,
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ),
            ),
            'general' => array(
                '#tree' => true,
                '#tab' => 'general',
                '#weight' => 1,
            ),
            'display_id' => array(
                '#type' => 'hidden',
                '#value' => $this->_display->id,
            ),
            'element' => array(
                '#type' => 'hidden',
                '#value' => $element_name,
            ),
            'parent_id' => array(
                '#type' => 'hidden',
                '#id' => 'drts-display-add-element-parent',
                '#value' => null,
            ),
        );
        
        $tab_weight = 5;
        $settings = [];
        if (isset($info['default_settings'])) $settings += (array)$info['default_settings'];
        $submitted_values = $this->_getSubimttedValues($context, $formStorage);
        if ($settings_form = (array)@$element->displayElementSettingsForm($bundle, $settings, $this->_display, ['general', 'settings'], null, false, isset($submitted_values['settings']) ? $submitted_values['settings'] : [])) {
            if (isset($settings_form['#tabs'])) {
                $form['settings'] = array(
                    '#tree' => true,
                    '#tree_allow_override' => false,
                    '#weight' => 2,
                );
                foreach ($settings_form['#tabs'] as $tab_name => $tab_info) {
                    if ($_settings_form = (array)@$element->displayElementSettingsForm($bundle, $settings, $this->_display, array('settings', $tab_name), $tab_name, false, isset($submitted_values['settings'][$tab_name]) ? $submitted_values['settings'][$tab_name] : [])) {
                        $_tab_name = 'settings-' . $tab_name;
                        $form['settings'][$tab_name] = array(
                            '#tab' => $_tab_name,
                        ) + $_settings_form;
                        if (is_string($tab_info)) {
                            $tab_info = array(
                                '#title' => $tab_info,
                                '#weight' => ++$tab_weight,
                            );
                        } 
                        if (isset($form['#tabs'][$_tab_name])) {
                            $form['#tabs'][$_tab_name] += $tab_info;
                            $form['#tabs'][$_tab_name]['#disabled'] = false;
                        } else {
                            $form['#tabs'][$_tab_name] = $tab_info;
                        }
                    }
                }
                unset($settings_form['#tabs']);
            }
            if (isset($settings_form['#header'])) {
                $form['#header'] += (array)$settings_form['#header'];
                unset($settings_form['#header']);
            }
            $form['general']['settings'] = array(
                '#tree' => true,
                '#tree_allow_override' => false,
                '#type' => 'fieldset',
                '#weight' => 10,
            );
            $form['general']['settings'] += $settings_form;
        } else {
            $form['#tabs']['general']['#disabled'] = true;
        }
        
        if (!isset($info['headingable']) || false !== $info['headingable']) {
            $form['heading'] = $this->Display_ElementLabelSettingsForm(
                isset($info['headingable']) && is_array($info['headingable']) ? $info['headingable'] : [],
                array('heading'),
                false
            );
        }
        
        $designable = !isset($info['designable']) || false !== $info['designable'];
        
        // Visibility settings tab
        if (!isset($info['visibility']) || false !== $info['visibility']) {
            $visibiilty_settings = $this->Display_VisibilitySettingsForm(
                $this->_display,
                $element_name,
                [],
                array(
                    'globalable' => $designable && $this->_display->type === 'entity' && $this->_display->name === 'detailed',
                    'parent' => empty($bundle->info['parent']) ? null : $bundle->info['parent'],
                )
            );
            if ($visibiilty_settings) {
                $form['visibility'] = $visibiilty_settings;
            }
        }
        
        // Advanced settings tab
        if ($advanced_settings = $this->Display_AdvancedSettingsForm(
            $this->_display,
            [],
            array(
                'designable' => $designable,
                'cacheable' => $this->_display->type === 'entity' && !empty($info['cacheable']) ? $info['cacheable'] : false,
            )
        )) {
            $form['advanced'] = $advanced_settings;
        }
        
        $form = $this->Filter('display_element_settings_form', $form, array($bundle, $this->_display, $element_name));
        
        $weight = 90;
        foreach (array(
            'heading' => _x('Heading', 'settings tab', 'directories'),
            'visibility' => _x('Visibility', 'settings tab', 'directories'),
            'advanced' => _x('Advanced', 'settings tab', 'directories'),
        ) as $key => $label) {
            if (!empty($form[$key])) {
                $form[$key]['#tab'] = $key;
                $form['#tabs'][$key] = array(
                    '#title' => $label,
                    '#weight' => ++$weight,
                );
            }
        }
        
        return $form;
    }

    public function submitForm(Form\Form $form, Context $context)
    {        
        $result = $this->Display_AdminElement_create(
            $bundle = $this->Entity_Bundle($this->_display->bundle_name),
            $this->_display,
            $form->values['element'],
            (int)@$form->values['parent_id'],
            array(
                'settings' => (array)@$form->values['general']['settings'] + (array)@$form->values['settings'],
                'advanced' => @$form->values['advanced'],
                'visibility' => @$form->values['visibility'],
                'heading' => @$form->values['heading'],
            )
        );
        
        // Clear display and elements cache
        $this->Display_Display_clearCache($this->_display);
        $this->getPlatform()->deleteCache('display_elements_' . $this->_display->bundle_name);
        
        $context->setSuccess($this->_getSuccessUrl($context), $result);
    }
    
    protected function _getDisplay(Context $context)
    {
        if ((!$display_id = $context->getRequest()->asInt('display_id'))
            || (!$display = $this->getModel('Display', 'Display')->fetchById($display_id))
        ) return false;
        
        return $display;
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return dirname($context->getRoute());
    }
}