<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class ButtonElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Button', 'display element name', 'directories'),
            'description' => __('Call to action button', 'directories'),
            'default_settings' => array(
                'size' => '',
                'btn' => true,
                'dropdown' => false,
                'dropdown_icon' => 'fas fa-cog',
                'dropdown_label' => '',
                'dropdown_right' => false,
                'separate' => true,
                'arrangement' => null,
                'tooltip' => true,
                'buttons' => [],
            ),
            'alignable' => true,
            'positionable' => true,
            'icon' => 'far fa-hand-pointer',
            'inlineable' => true,
            'headingable' => false,
        );
    }    
    
    protected function _getButtonSizeOptions()
    {
        return [
            'sm' => __('Small', 'directories'),
            '' => __('Medium', 'directories'),
            'lg' => __('Large', 'directories'),
        ];
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        switch ($tab) {
            case 'buttons':
                $form = [];
                foreach (array_keys($this->_application->Display_Buttons($bundle)) as $btn_name) {
                    if (!$btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) continue;
                    
                    if ($multiple = $btn->displayButtonInfo($bundle, 'multiple')) {
                        foreach ($multiple as $_btn_name => $_btn_info) {
                            $_btn_name = $btn_name . '-' . $_btn_name;
                            $form[$_btn_name] = $this->_getButtonSettingsForm($bundle, $_btn_name, $_btn_info['label'], $btn, $settings['buttons'], $parents);
                        }
                    } else {
                        $form[$btn_name] = $this->_getButtonSettingsForm($bundle, $btn_name, $btn->displayButtonInfo($bundle, 'label'), $btn, $settings['buttons'], $parents);
                    }
                }
                return $form;
            default:
                $options = $defaults = [];
                foreach (array_keys($this->_application->Display_Buttons($bundle)) as $btn_name) {
                    if (!$btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) continue;
                    
                    $info = $btn->displayButtonInfo($bundle);
                    if (!empty($info['multiple'])) {
                        foreach ($info['multiple'] as $_btn_name => $_btn_info) {
                            $_btn_name = $btn_name . '-' . $_btn_name;
                            $options[$_btn_name] = $_btn_info['label'];
                            if (!empty($_btn_info['default_checked'])) {
                                $defaults[] = $_btn_name;
                            }
                        } 
                    } else {
                        $options[$btn_name] = $info['label'];
                        if (!empty($info['default_checked'])) {
                            $defaults[] = $btn_name;
                        }
                    }
                }
                return array(
                    '#tabs' => array(
                        'buttons' => _x('Buttons', 'settings tab', 'directories'),
                    ),
                    'size'=> array(
                        '#type' => 'select',
                        '#title' => __('Button size', 'directories'),
                        '#options' => $this->_getButtonSizeOptions(),
                        '#horizontal' => true,
                        '#default_value' => $settings['size'],
                    ),
                    'dropdown' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Display as single dropdown', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['dropdown']),
                    ),
                    'dropdown_icon' => array(
                        '#type' => 'iconpicker',
                        '#title' => __('Dropdown icon', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => $settings['dropdown_icon'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'dropdown_label' => array(
                        '#type' => 'textfield',
                        '#title' => __('Dropdown label', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => $settings['dropdown_label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'dropdown_right' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Right align dropdown items', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['dropdown_right']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                            ),
                        ),
                    ),
                    'separate' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Separate buttons', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['separate']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                            ),
                        ),
                    ),
                    'tooltip' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Show tooltip if no label', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => !empty($settings['tooltip']),
                        '#states' => array(
                            'visible' => array(
                                sprintf('input[name="%s[dropdown]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => false),
                            ),
                        ),
                    ),
                    'arrangement' => array(
                        '#type' => 'sortablecheckboxes',
                        '#title' => __('Select buttons', 'directories'),
                        '#horizontal' => true,
                        '#options' => $options,
                        '#default_value' => isset($settings['arrangement']) ? $settings['arrangement'] : $defaults,
                    ),
                );
        }
    }
    
    protected function _getButtonSettingsForm(Entity\Model\Bundle $bundle, $btnName, $btnLabel, Display\Button\IButton $btn, array $settings, array $parents)
    {
        $_parents = $parents;
        $_parents[] = $btnName;
        $btn_parents = $_parents;
        $btn_parents[] = 'settings';
        $_settings = [];
        if (isset($settings[$btnName]['settings'])) {
            $_settings += $settings[$btnName]['settings'];
        }
        if ($default_settings = $btn->displayButtonInfo($bundle, 'default_settings')) {
            $_settings += $default_settings;
        }
        $arrangement_parents = array_slice($parents, 0, -1);
        $arrangement_selector = sprintf('input[name="%s[]"]', $this->_application->Form_FieldName(array_merge($arrangement_parents, ['arrangement'])));
        $ret = array(
            '#title' => $btnLabel,
            '#weight' => (int)$btn->displayButtonInfo($bundle, 'weight'),
            '#states' => array(
                'enabled' => array(
                    $arrangement_selector => array('value' => $btnName),
                ),
            ),
            'settings' => array(
                '#element_validate' => array(array(array($this, 'validateButtonSettings'), array($_parents))),
                '_hide_label' => array(
                    '#type' => 'checkbox',
                    '#title' => __('Hide label', 'directories'),
                    '#default_value' => !empty($_settings['_hide_label']),
                    '#horizontal' => true,
                    '#weight' => -3,
                ),
            ),
        );
        if ($btn->displayButtonInfo($bundle, 'colorable') !== false) {
            $ret['settings']['_color'] = array(
                '#type' => 'radios',
                '#title' => __('Button color', 'directories'),
                '#default_value' => isset($_settings['_color']) ? $_settings['_color'] : null,
                '#options' => $this->_application->System_Util_colorOptions(true, true),
                '#option_no_escape' => true,
                '#horizontal' => true,
                '#weight' => -2,
                '#columns' => 6,
            );
            $ret['settings']['_link_color'] = array(
                '#type' => 'colorpicker',
                '#title' => __('Link color', 'directories'),
                '#default_value' => isset($_settings['_link_color']) ? $_settings['_link_color'] : null,
                '#horizontal' => true,
                '#weight' => -1,
                '#states' => [
                    'visible' => [
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($btn_parents, ['_color']))) => ['value' => 'link'],
                    ],
                ],
            );
        }
        if ($btn->displayButtonInfo($bundle, 'iconable') !== false) {
            $ret['settings']['_icon'] = array(
                '#type' => 'iconpicker',
                '#title' => __('Button icon', 'directories'),
                '#default_value' => $_settings['_icon'],
                '#horizontal' => true,
                '#weight' => -2,
            );
        }
        if ($btn->displayButtonInfo($bundle, 'labellable') !== false) {
            $ret['settings']['_label'] = array(
                '#type' => 'textfield',
                '#title' => __('Button label', 'directories'),
                '#default_value' => $_settings['_label'],
                '#horizontal' => true,
                '#weight' => -4,
            );
        }
        if ($btn_settings_form = $btn->displayButtonSettingsForm($bundle, $_settings, $btn_parents)) {
            $ret['settings'] += $btn_settings_form;
        }
        
        return $ret;
    }
    
    public function validateButtonSettings($form, &$value, $element, $parents)
    {
        $settings = $form->getValue($parents);
        if (!empty($settings['_hide_label'])
            && !strlen($value['_icon'])
        ) {
            $error = __('Icon may not be empty if label is hidden', 'directories');
            $form->setError($error, $element['#name'] . '[_icon]');
        }
    }
            
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity') return false;
        
        $buttons = $this->_application->Display_Buttons($bundle);
        return !empty($buttons);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        $buttons = [];
        $apply_color = empty($settings['dropdown']);
        $display_name = isset($element['display']) ? $element['display'] : 'detailed';
        foreach ($settings['arrangement'] as $btn_name) {
            if ($link = $this->_getButtonLink($bundle, $btn_name, $var, $settings, $display_name, $apply_color)) {
                $buttons[$btn_name] = $link;
            }
        }
        if (empty($buttons)) return '';
        
        $options = array(
            'size' => $settings['size'],
            'tooltip' => empty($settings['dropdown']) && !empty($settings['tooltip']),
            'label' => true,
            'group' => true,
        );
        
        if (count($buttons) ===1) {
            if (!$apply_color) { // regenerate link with color if color has not been applied
                $buttons = array($this->_getButtonLink($bundle, current(array_keys($buttons)), $var, $settings, $display_name));
            }
            return $this->_application->ButtonLinks($buttons, $options);
        }
        
        if (!empty($settings['dropdown'])) {
            array_unshift($buttons, $this->_application->LinkTo($settings['dropdown_label'], '#', array('active' => true, 'icon' => $settings['dropdown_icon'])));
            return $this->_application->DropdownButtonLinks(
                $buttons,
                array('size' => $settings['size'], 'right' => !empty($settings['dropdown_right']), 'tooltip' => true, 'label' => true, 'color' => 'outline-secondary')
            );
        }
        
        if (empty($settings['separate'])) return $this->_application->ButtonLinks($buttons, $options);
        
        return $this->_application->ButtonToolbar($buttons, array('class' => DRTS_BS_PREFIX . 'd-inline-flex') + $options);
    }
    
    protected function _getButtonLink(Entity\Model\Bundle $bundle, $btnName, $entity, array $settings, $displayName, $applyColor = true)
    {
        $_btn_name = null;
        if (strpos($btnName, '-')) {
            list($btn_name, $_btn_name) = explode('-', $btnName);
        } else {
            $btn_name = $btnName;
        }
        if (!$btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) return;
            
        $btn_settings = isset($settings['buttons'][$btnName]['settings']) ? $settings['buttons'][$btnName]['settings'] : [];
        if ($applyColor) {
            if (isset($btn_settings['_color'])) {
                $btn_settings['_class'] = DRTS_BS_PREFIX . 'btn-' . (isset($btn_settings['_color']) ? $btn_settings['_color'] : 'outline-secondary');
                $btn_settings['_style'] = $btn_settings['_color'] === 'link' ? 'color:' . $btn_settings['_link_color'] . ';' : '';
            } else {
                $btn_settings['_class'] = DRTS_BS_PREFIX . 'btn-outline-secondary';
                $btn_settings['_style'] = '';
            }
        } else {
            $btn_settings['_class'] = $btn_settings['_style'] = '';
        }
        if (!$link = $btn->displayButtonLink($bundle, $entity, $btn_settings, $displayName)) return;
   
        if (!empty($btn_settings['_hide_label'])) {
            if (is_array($link)) {
                // Dropdown button
                $link[0]->setAttribute('title', strip_tags($link[0]->getLabel()))
                    ->setAttribute('data-button-name', $btnName)
                    ->setLabel('');
            } else {
                $link->setAttribute('rel', 'nofollow')
                    ->setAttribute('title', strip_tags($link->getLabel()))
                    ->setAttribute('data-button-name', $btnName)
                    ->setLabel('');
            }
        } else {
            if (!is_array($link)) {
                $link->setAttribute('rel', 'nofollow')
                    ->setAttribute('data-button-name', $btnName);
            }
        }
        
        return $link;
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element, $displayType)
    {
        foreach ($element['settings']['arrangement'] as $btn_name) {
            if (($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true))
                && ($btn->displayButtonIsPreRenderable($bundle, (array)@$element['settings']['buttons'][$btn_name]['settings']))
            ) {
                return true;
            }
        }
        return false;
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, $displayType, &$var)
    {
        foreach ($element['settings']['arrangement'] as $btn_name) {
            if (($btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true))
                && ($btn->displayButtonIsPreRenderable($bundle, $btn_settings = (array)@$element['settings']['buttons'][$btn_name]['settings']))
            ) {
                $btn->displayButtonPreRender($bundle, $btn_settings, $var['entities']);
            }
        }
    }
    
    public function displayElementOnCreate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $this->_unsetDisabledButtonSettings($data);
    }
    
    public function displayElementOnUpdate(Entity\Model\Bundle $bundle, array &$data, $weight)
    {
        $this->_unsetDisabledButtonSettings($data);
    }
    
    protected function _unsetDisabledButtonSettings(array &$data)
    {
        if (empty($data['settings']['buttons'])) return;
        
        foreach (array_keys($data['settings']['buttons']) as $btn_name) {
            if (!in_array($btn_name, $data['settings']['arrangement'])) {
                unset($data['settings']['buttons'][$btn_name]);
            }
        }
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (empty($settings['buttons'])) return;
        
        $btns = [];
        foreach (array_keys($settings['buttons']) as $btn_name) {
            if ($multiple = strpos($btn_name, '-')) {
                list($btn_name, $_btn_name) = explode('-', $btn_name);
            }
            if (!$btn = $this->_application->Display_Buttons_impl($bundle, $btn_name, true)) continue;
            
            $info = $btn->displayButtonInfo($bundle);
            if ($multiple) {
                if (!isset($info['multiple'][$_btn_name]['label'])) continue;
                
                $btns[] = $info['multiple'][$_btn_name]['label'];
            } else {
                $btns[] = $info['label'];
            }
        }
        $sizes = $this->_getButtonSizeOptions();
        $ret = [
            'buttons' => [
                'label' => __('Buttons', 'directories'),
                'value' => implode(', ', $btns),
            ],
            'button_size' => [
                'label' => __('Button size', 'directories'),
                'value' => $sizes[$settings['size']],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}