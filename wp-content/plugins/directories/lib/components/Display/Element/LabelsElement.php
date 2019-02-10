<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class LabelsElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Labels', 'display element name', 'directories'),
            'description' => __('Small tags for adding context', 'directories'),
            'default_settings' => array(
                'labels' => [],
            ),
            'alignable' => true,
            'positionable' => true,
            'inlineable' => true,
            'icon' => 'fas fa-tags',
        );
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        switch ($tab) {
            case 'labels':
                $form = [];
                $labels_available = $this->_application->Display_Labels($bundle);
                $arrangement_parents = array_slice($parents, 0, -1);
                $arrangement_selector = sprintf('input[name="%s[]"]', $this->_application->Form_FieldName(array_merge($arrangement_parents, array('arrangement'))));
                foreach (array_keys($labels_available) as $label_name) {
                    if (!$label = $this->_application->Display_Labels_impl($bundle, $label_name, true)) continue;

                    if ($multiple = $label->displayLabelInfo($bundle, 'multiple')) {
                        foreach ($multiple as $_label_name => $_label_info) {
                            $_label_name = $label_name . '-' . $_label_name;
                            $label_settings = isset($settings['labels'][$_label_name]['settings']) ? (array)$settings['labels'][$_label_name]['settings'] : [];
                            $form[$_label_name] = $this->_getLabelSettingsForm($bundle, $_label_name, $_label_info['label'], $label, $label_settings, array_merge($parents, [$_label_name]), $arrangement_selector);
                        }
                    } else {
                        $label_settings = isset($settings['labels'][$label_name]['settings']) ? (array)$settings['labels'][$label_name]['settings'] : [];
                        $form[$label_name] = $this->_getLabelSettingsForm($bundle, $label_name, $label->displayLabelInfo($bundle, 'label'), $label, $label_settings, array_merge($parents, [$label_name]), $arrangement_selector);
                    }
                }
                return $form;
            default:
                $options = $options_placeholder = [];
                foreach ($this->_application->Display_Labels($bundle) as $label_name => $component_name) {
                    if (!$label = $this->_application->Display_Labels_impl($bundle, $label_name, true)) continue;

                    $info = $label->displayLabelInfo($bundle);
                    if (!empty($info['multiple'])) {
                        foreach ($info['multiple'] as $_label_name => $_label_info) {
                            $_label_name = $label_name . '-' . $_label_name;
                            $options[$_label_name] = $_label_info['label'];
                            if (!empty($_label_info['default_checked'])) {
                                $defaults[] = $_label_name;
                            }
                        }
                    } else {
                        $options[$label_name] = $info['label'];
                        if (!empty($info['default_checked'])) {
                            $defaults[] = $label_name;
                        }
                    }
                }
                return array(
                    '#tabs' => array(
                        'labels' => _x('Labels', 'settings tab', 'directories'),
                    ),
                    'arrangement' => array(
                        '#type' => 'sortablecheckboxes',
                        '#title' => __('Display order', 'directories'),
                        '#horizontal' => true,
                        '#default_value' => isset($settings['arrangement']) ? $settings['arrangement'] : array_keys($options),
                        '#options' => $options,
                    ),
                );
        }
    }

    protected function _getLabelSettingsForm(Entity\Model\Bundle $bundle, $labelName, $labelLabel, Display\Label\ILabel $label, array $settings, array $parents, $arrangementSelector)
    {
        $parents[] = 'settings';
        if ($default_settings = $label->displayLabelInfo($bundle, 'default_settings')) {
            $settings += $default_settings;
        }

        $form = [];
        if ($label->displayLabelInfo($bundle, 'labellable') !== false) {
            $form['_label'] = [
                '#type' => 'textfield',
                '#title' => __('Label text', 'directories'),
                '#default_value' => $settings['_label'],
                '#horizontal' => true,
                '#weight' => -3,
            ];
        }
        if ($label->displayLabelInfo($bundle, 'colorable') !== false) {
            $form['_color'] = [
                '#weight' => -2,
                'type' => [
                    '#type' => 'radios',
                    '#title' => __('Label color', 'directories'),
                    '#default_value' => isset($settings['_color']['type']) ? $settings['_color']['type'] : null,
                    '#options' => $this->_application->System_Util_colorOptions() + ['custom' => __('Custom', 'directories')],
                    '#option_no_escape' => true,
                    '#horizontal' => true,
                    '#columns' => 6,
                ],
                'value' => [
                    '#type' => 'colorpicker',
                    '#default_value' => isset($settings['_color']['value']) ? $settings['_color']['value'] : null,
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['_color', 'type']))) => ['value' => 'custom'],
                        ],
                    ],
                ],
            ];
        }
        if ($label_settings_form = $label->displayLabelSettingsForm($bundle, $settings, $parents)) {
            $form += $label_settings_form;
        }
        if (!empty($form)) {
            $form = [
                '#title' => $labelLabel,
                '#states' => [
                    'enabled' => [
                        $arrangementSelector => ['value' => $labelName],
                    ],
                ],
                'settings' => $form,
            ];
        }

        return $form;
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity') return false;

        $labels = $this->_application->Display_Labels($bundle);
        return !empty($labels);
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $labels = [];
        $settings = $element['settings'];
        foreach ($settings['arrangement'] as $label_name) {
            if (!$label = $this->_application->Display_Labels_impl($bundle, $label_name, true)) continue;

            $label_settings = isset($settings['labels'][$label_name]['settings']) ? $settings['labels'][$label_name]['settings'] : [];
            if (!$text = $label->displayLabelText($bundle, $var, $label_settings)) continue;

            $color = ['type' => 'secondary'];
            $attr = null;
            if (is_array($text)) {
                if (isset($text['color'])) {
                    $color = $text['color'];
                }
                if (isset($text['attr'])) {
                    $attr = $text['attr'];
                }
                $text = $text['label'];
            } elseif (is_bool($text)) {
                $text = $label_settings['_label'];
            }
            $labels[$label_name] = $this->_renderLabel($label_name, $text, $color, $attr);
        }

        return empty($labels) ? '' : implode(PHP_EOL, $labels);
    }

    protected function _renderLabel($name, $text, $color, array $attr = null)
    {
        $color_class = $color_style = '';
        if ($color['type'] === 'custom') {
            $color_style = 'background-color:' . $this->_application->H($color['value']) . ';';
        } else {
            $color_class = DRTS_BS_PREFIX . 'badge-' . $color['type'];
        }
        $attr = isset($attr) ? $this->_application->Attr($attr) : '';
        return '<span style="' . $color_style . '" class="' . DRTS_BS_PREFIX . 'badge ' . $color_class . '" data-label-name="' . $name . '"' . $attr . '>' . $this->_application->H($text) . '</span>';
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        if (empty($settings['arrangement'])) return;

        $labels = [];
        foreach ($settings['arrangement'] as $label_name) {
            if (!$label = $this->_application->Display_Labels_impl($bundle, $label_name, true)) continue;

            $info = $label->displayLabelInfo($bundle);
            $labels[] = $info['label'];
        }
        $ret = [
            'labels' => [
                'label' => __('Labels', 'directories'),
                'value' => implode(', ', $labels),
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}
