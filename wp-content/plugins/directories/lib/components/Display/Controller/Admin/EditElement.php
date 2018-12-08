<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class EditElement extends Form\Controller
{
    protected $_element, $_display;

    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        if ((!$element_id = $context->getRequest()->asStr('element_id'))
            || (!$this->_element = $this->getModel('Element', 'Display')->fetchById($element_id))
            || (!$this->_display = $this->_getDisplay($context))
            || (!$bundle = $this->Entity_Bundle($this->_display->bundle_name))
            || (!$element = $this->Display_Elements_impl($bundle, $this->_element->name, true))
            || !$element->displayElementSupports($bundle, $this->_display)
        ) {
            $context->setError();
            return;
        }

        // Define form
        $form = array(
            '#action' => $this->Url($context->getRoute()),
            '#token_reuseable' => true,
            '#enable_storage' => true,
            '#bundle' => $bundle,
            '#header' => [],
            '#element_name' => $this->_element->name,
            '#element_data' => $this->_element->data,
        );

        // Set options
        $this->_cancelWeight = -99;
        $this->_submitButtons = [[
            '#btn_label' => __('Save Changes', 'directories'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
        ]];
        $this->_ajaxOnSuccess = 'function(result, target, trigger) {
            $(DRTS).trigger("display_element_updated.sabai", {trigger: trigger, result: result, target: target});
        }';
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxModalHideOnSend = true;
        
        $info = $element->displayElementInfo($bundle);
        $form += array(
            '#tabs' => array(
                'general' => array(
                    '#active' => true,
                    '#title' => _x('General', 'settings tab', 'directories'),
                    '#weight' => 1,
                ),
            ),
            '#tab_style' => 'pill',
            '#inherits' => array(
                'display_admin_edit_element_' . strtolower($this->_element->name),
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
            'element_id' => array(
                '#type' => 'hidden',
                '#value' => $this->_element->id,
            ),
        );
        
        $tab_weight = 5;
        $settings = (array)@$this->_element->data['settings'] + (array)@$info['default_settings'];
        $submitted_values = $this->_getSubimttedValues($context, $formStorage);
        if ($settings_form = (array)@$element->displayElementSettingsForm($bundle, $settings, $this->_display, ['general', 'settings'], null, true, isset($submitted_values['settings']) ? $submitted_values['settings'] : [])) {
            if (isset($settings_form['#tabs'])) {
                $form['settings'] = array(
                    '#tree' => true,
                    '#tree_allow_override' => false,
                    '#weight' => 2,
                );
                foreach ($settings_form['#tabs'] as $tab_name => $tab_info) {
                    if ($_settings_form = (array)@$element->displayElementSettingsForm($bundle, $settings, $this->_display, ['settings', $tab_name], $tab_name, true, isset($submitted_values['settings'][$tab_name]) ? $submitted_values['settings'][$tab_name] : [])) {
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
                '#type' => 'fieldset',
                '#weight' => 10,
            );
            $form['general']['settings'] += $settings_form;
        } else {
            $form['#tabs']['general']['#disabled'] = true;
        }
        
        if (!isset($info['headingable']) || false !== $info['headingable']) {
            $form['heading'] = $this->Display_ElementLabelSettingsForm(
                (array)@$this->_element->data['heading'],
                array('heading'),
                false
            );
        }
        
        $designable = !isset($info['designable']) || false !== $info['designable'];
        
        // Visibility settings tab
        if (!isset($info['visibility']) || false !== $info['visibility']) {
            $visibiilty_settings = $this->Display_VisibilitySettingsForm(
                $this->_display,
                $this->_element,
                (array)@$this->_element->data['visibility'],
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
            (array)@$this->_element->data['advanced'],
            array(
                'designable' => $designable,
                'cacheable' => $this->_display->type === 'entity' && !empty($info['cacheable']) ? $info['cacheable'] : false,
            )
        )) {
            $form['advanced'] = $advanced_settings;
        }
        
        $form = $this->Filter('display_element_settings_form', $form, array($bundle, $this->_display, $this->_element));
        
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
        $bundle = $this->Entity_Bundle($this->_display->bundle_name);
        $result = $this->Display_AdminElement_update($bundle, $this->_element, $form->values);
        $context->setSuccess($this->_getSuccessUrl($context), $result);
        
        // Clear display cache
        $this->Display_Display_clearCache($this->_display);
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