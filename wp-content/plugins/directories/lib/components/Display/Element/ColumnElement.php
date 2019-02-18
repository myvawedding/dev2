<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Form;

class ColumnElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'utility',
            'label' => _x('Column', 'display element name', 'directories'),
            'default_settings' => array(
                'width' => 4,
                'responsive' => array(
                    'xs' => array('width' => 12),
                    'sm' => array('width' => 'inherit'),
                    'md' => array('width' => 'inherit'),
                    'lg' => array('width' => 'inherit'),
                    'xl' => array('width' => 'inherit'),
                    'grow' => true,
                )
            ),
            'containable' => true,
            'positionable' => true,
            'parent_element_name' => 'columns',
            'icon' => 'fas fa-columns',
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $widths = $this->_getWidthOptions();
        $form = array(
            'width' => array(
                '#title' => __('Column width', 'directories'),
                '#type' => 'select',
                '#options' => $widths + array(
                    'responsive' => __('Responsive', 'directories'),
                ),
                '#default_value' => $settings['width'],
                '#horizontal' => true,
            ),
            'responsive' => array(
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('width')))) => array('value' => 'responsive'),
                    ),
                ),
                '#element_validate' => [function(Form\Form $form, &$value, $element) {
                    foreach (array('xl', 'lg', 'md', 'sm', 'xs') as $key) {
                        if (!empty($value[$key]['width'])
                            && $value[$key]['width'] !== 'inherit'
                        ) {
                            $width = $value[$key]['width'];

                            break;
                        }
                    }
                    if (empty($width)) {
                        $form->setError(__('Please select at least one non-empty width.', 'directories'), $element);
                    }
                }],
            ),
        );
        foreach ($this->_getResponsiveWidthOptions() as $key => $title) {
            $options = array(0 => __('Hidden', 'directories')) + $widths;
            if ($can_inherit = isset($can_inherit)) {
                $options = array('inherit' => __('Inherit from smaller', 'directories')) + $options;
                $default = 'inherit';
            } else {
                $default = 12;
            }
            $form['responsive'][$key] = array(
                'width' => array(
                    '#field_prefix' => $title,
                    '#type' => 'select',
                    '#options' => $options,
                    '#default_value' => isset($settings['responsive'][$key]['width']) ? $settings['responsive'][$key]['width'] : $default,
                    '#horizontal' => true,
                    '#description' => sprintf(
                        __('Select the display width of this column when the container of the column is %s.', 'directories'),
                        $title
                    ),
                    '#empty_value' => $can_inherit ? 'inherit' : null,
                ),
            );
        }
        $form['responsive']['grow'] = array(
            '#type' => 'checkbox',
            '#horizontal' => true,
            '#title' => __('Stretch to fill space', 'directories'),
            '#default_value' => !empty($settings['responsive']['grow']),
        );
        
        return $form;
    }
    
    protected function _getResponsiveWidthOptions()
    {
        return ['xs' => '<= 320px', 'sm' => '> 320px', 'md' => '> 480px', 'lg' => '> 720px', 'xl' => '> 960px'];
    }
    
    protected function _getWidthOptions()
    {
        return [
            2 => '1/6',
            3 => '1/4',
            4 => '1/3',
            6 => '1/2',
            8 => '2/3',
            9 => '3/4',
            12 => __('Full width', 'directories'),
        ];
    }
    
    public function displayElementAdminAttr(Entity\Model\Bundle $bundle, array $settings)
    {
        if ($settings['width'] !== 'responsive') {
            $width = $settings['width'];
            $grow = 0;
        } else {
            foreach (array('xl', 'lg', 'md', 'sm', 'xs') as $key) {
                if (!empty($settings['responsive'][$key]['width'])
                    && $settings['responsive'][$key]['width'] !== 'inherit'
                ) {
                    $width = $settings['responsive'][$key]['width'];
                    
                    break;
                }
            }
            $grow = empty($settings['responsive']['grow']) ? 0 : 1;
        }
        $width = (100 * $width / 12) . '%';
        return array(
            // min-width:0 for firefox bug with flex items: https://github.com/philipwalton/flexbugs/issues/39
            'style' => 'flex:' . $grow . ' 0 ' . $width . ';width:' . $width . ';min-width:0;',
            'data-element-width' => $width,
        );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!$html = $this->_renderChildren($bundle, $element['children'], $var, $element)) return;
        
        $class = '';
        if ($element['settings']['width'] !== 'responsive') {
            $width = (int)$element['settings']['width'];
            $class .= ' drts-col-' . $width;
        } else {
            $is_hidden = false;
            $grow = !empty($element['settings']['responsive']['grow']);
            foreach ($element['settings']['responsive'] as $key => $width) {
                if (!strlen($width['width']) || $width['width'] === 'inherit') continue;
                
                if (!$_width = intval($width['width'])) {
                    $is_hidden = true;
                    $class .= ' drts-' . ($key === 'xs' ? 'd-none' : $key . '-d-none');
                } else {
                    if ($is_hidden) {
                        $class .= ' drts-' . $key . '-d-block';
                        $is_hidden = false;
                    }
                    $class .= ' drts-col-' . ($key === 'xs' ? $_width : $key . '-' . $_width);
                    if ($grow) {
                        $class .= ' drts-' . ($key === 'xs' ? 'grow' : $key . '-grow');
                    }
                }
            }
        }
        
        return array(
            'class' => $class,
            'html' => implode(PHP_EOL, $html),
        );
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'width' => [
                'label' => __('Column width', 'directories'),
            ],
        ];
        $widths = $this->_getWidthOptions();
        if ($settings['width'] === 'responsive') {
            $ret['width']['value'] = __('Responsive', 'directories');
            $widths[0] = __('Hidden', 'directories');
            $widths['inherit'] = __('Inherit from smaller', 'directories');
            foreach ($this->_getResponsiveWidthOptions() as $key => $title) {
                $setting = $settings['responsive'][$key];
                if (isset($setting['width'])) {
                    $value = $this->_application->H($widths[$setting['width']]);
                } else {
                    $value = __('Inherit from smaller', 'directories');
                }
                $ret['responsive-' . $key] = [
                    'label' => $title,
                    'value' => $value,
                    'is_html' => true,
                ];
            }
            $ret['responsive-grow'] = [
                'label' => __('Stretch to fill space', 'directories'),
                'value' => !empty($settings['responsive']['grow']),
                'is_bool' => true,
            ];
        } else {
            $ret['width']['value'] = $widths[$settings['width']];
        }

        return ['settings' => ['value' => $ret]];
    }
}