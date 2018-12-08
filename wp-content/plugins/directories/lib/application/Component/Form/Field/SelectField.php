<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class SelectField extends AbstractField
{
    protected static $_select2Elements, $_bootstrapSelectElements;

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#options'])) {
            $data['#options'] = [];
        }
        if (!isset($data['#options_disabled'])) {
            $data['#options_disabled'] = [];
        }
        if ($data['#multiple'] = !empty($data['#multiple'])) {
            $data['#size'] = isset($data['#size']) ? $data['#size'] : ((10 < $count = count($data['#options'])) ? 10 : $count);
        }

        if (!empty($data['#select2'])) {
            $this->_select2($name, $data, $form);
        }
        //elseif (!empty($data['#bootstrap'])) {
        //    $this->_bootstrapSelect($name, $data, $data['#bootstrap'], $form);
        //}
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        // Is it a required field?
        if (is_null($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection is required for this field.', 'directories'), $data);
            }
            $value = $data['#multiple'] ? [] : null;

            return;
        }

        // No options
        if (empty($data['#options'])) {
            if (empty($data['#skip_validate_option'])) {
                $value = $data['#multiple'] ? [] : null;

                return;
            }
        }

         $new_value = (array)$value;

        // Are all the selected options valid?
        foreach ($new_value as $k => $_value) {
            if (empty($data['#skip_validate_option']) && !isset($data['#options'][$_value])) {
                $form->setError(__('Invalid option selected.', 'directories'), $data);

                return;
            }
            if (isset($data['#empty_value']) && $_value == $data['#empty_value']) {
                unset($new_value[$k]);
            }
        }

        if (empty($new_value) && $form->isFieldRequired($data)) {
            $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Selection is required for this field.', 'directories'), $data);
            return;
        }

        if (!$data['#multiple']) {
            $value = isset($new_value[0]) ? $new_value[0] : null;
            return;
        }

        if (!empty($data['#max_selection']) && count($new_value) > $data['#max_selection']) {
            $form->setError(sprintf(__('Maximum of %d selections is allowed for this field.', 'directories'), $data['#max_selection']), $data);
            return;
        }
        $value = $new_value;
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $options = $optgroups = $options_attr = [];
        $values = isset($data['#default_value']) ? (array)$data['#default_value'] : [];
        $i = 0;
        foreach ($data['#options'] as $k => $label) {
            if (is_array($label)) {
                if (isset($label['#count'])) {
                    $_label = $label['#title'] . ' (' . $label['#count'] . ')';
                } else {
                    $_label = $label['#title'];
                }
                if (!empty($label['#depth']) && isset($label['#title_prefix'])) {
                    $_label = str_repeat($label['#title_prefix'], $label['#depth']) . ' ' . $_label;
                }
                if (!empty($label['#attributes'])) {
                    if (!isset($data['#options_attr'][$k])) $data['#options_attr'][$k] = [];
                    $data['#options_attr'][$k] += $label['#attributes'];
                }
                if (isset($label['#group'])
                    && isset($data['#optgroups'][$label['#group']])
                ) {
                    if (!isset($optgroups[$label['#group']])) {
                        $optgroups[$label['#group']] = array('options' => [], 'i' => $i);
                        ++$i;
                    }
                    $optgroups[$label['#group']]['options'][] = $this->_renderOption($form, $data, $k, $_label, $values);
                    continue;
                } else {
                    $label = $_label;
                }
            }
            $options[$i] = $this->_renderOption($form, $data, $k, $label, $values);
            ++$i;
        }
        foreach ($optgroups as $optgroup_name => $optgroup) {
            $options[$optgroup['i']] = sprintf(
                '<optgroup label="%s"%s>%s</optgroup>',
                $this->_application->H($data['#optgroups'][$optgroup_name]),
                isset($data['#optgroups_attr'][$optgroup_name]) ? $this->_application->Attr($data['#optgroups_attr'][$optgroup_name]) : '',
                implode(PHP_EOL, $optgroup['options'])
            );
        }
        ksort($options);

        if ($data['#multiple']) {
            $data['#attributes']['multiple'] = 'multiple';
            $name = $data['#name'] . '[]';
            if (isset($data['#size']) && $data['#size'] > 1) {
                $data['#attributes']['size'] = $data['#size'];
            }
        } else {
            unset($data['#attributes']['multiple']);
            $name = $data['#name'];

            if (isset($values[0])) {
                $data['#attributes']['data-default-value'] = $values[0];
            }
        }

        $select = sprintf(
            '<select class="%sform-control %s" name="%s"%s>%s</select>',
            DRTS_BS_PREFIX,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $name,
            $this->_application->Attr($data['#attributes'], 'class'),
            implode(PHP_EOL, $options)
        );

        $has_addon = false;
        $html = [];
        if (isset($data['#field_prefix'])) {
            if (empty($data['#field_prefix_no_addon'])) {
                $has_addon = true;
                $html[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-prepend"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_prefix'] . '</span></div>';
            } else {
                $html[] = $data['#field_prefix'];
            }
        }
        $html[] = $select;
        if (isset($data['#field_suffix'])) {
            if (empty($data['#field_suffix_no_addon'])) {
                $has_addon = true;
                $html[] = '<div class="' . DRTS_BS_PREFIX . 'input-group-append"><span class="' . DRTS_BS_PREFIX . 'input-group-text">' . $data['#field_suffix'] . '</span></div>';
            } else {
                $html[] = $data['#field_suffix'];
            }
        }

        $this->_render($has_addon ? '<div class="' . DRTS_BS_PREFIX . 'input-group">' . implode(PHP_EOL, $html) . '</div>' : implode(PHP_EOL, $html), $data, $form);
    }

    protected function _renderOption(Form $form, $data, $value, $label, array $selected)
    {
        $strict = empty($value) && $value !== '0';
        return sprintf(
            '<option value="%s"%s%s%s>%s</option>',
            $this->_application->H($value),
            in_array($value, $selected, $strict) ? ' selected="selected"' : '',
            in_array($value, $data['#options_disabled'], $strict) ? ' disabled="disabled"' : '',
            isset($data['#options_attr'][$value]) ? $this->_application->Attr($data['#options_attr'][$value]) : '',
            $this->_application->H($label)
        );
    }

    //protected function _bootstrapSelect($name, array &$data, $options, Form $form)
    //{
    //    if (isset($data['#attributes']['class'])) {
    //        $data['#attributes']['class'] .= ' drts-selectpicker';
    //    } else {
    //        $data['#attributes']['class'] = 'drts-selectpicker';
    //    }

    //    if (is_array($options)) {
    //        foreach (array('selected-text-format', 'dropdown-align-right') as $key) {
    //            if (isset($options[$key])) {
    //                $data['#attributes']['data-' . $key] = is_bool($options[$key]) ? ($options[$key] ? 'true' : false) : $options[$key];
    //            }
    //        }
    //    }
    //    $data['#attributes']['data-actions-box'] = 'true';

    //    if (!isset($form->settings['#pre_render']['bootstrap-select'])) {
    //        $form->settings['#pre_render']['bootstrap-select'] = array($this, 'bootstrapSelectCallback');
    //    }
    //}

    protected function _select2($name, array &$data, Form $form)
    {
        $data['#id'] = $form->getFieldId($name);

        if (!isset($data['#select2_allow_clear'])) {
            $data['#select2_allow_clear'] = true;
        }
        if (!empty($data['#max_selection'])) {
            $data['#select2_maximum_selection_length'] = $data['#max_selection'];
        }
        if (!isset($data['#select2_minimum_input_length'])) {
            $data['#select2_minimum_input_length'] = empty($data['#select2_ajax']) ? 0 : 2;
        }
        if (!isset($data['#select2_placeholder'])) {
            if (isset($data['#placeholder'])) {
                $data['#select2_placeholder'] = $data['#placeholder'];
            } elseif ($data['#multiple']) {
                $data['#select2_placeholder'] = '';
            } else {
                if (isset($data['#empty_value'])
                    && array_key_exists($data['#empty_value'], $data['#options'])
                ) {
                    $data['#select2_placeholder'] = $data['#options'][$data['#empty_value']];
                } else {
                    $data['#select2_placeholder'] = __('— Select —', 'directories');
                }
            }
        }
        // default select2 options
        foreach (array('minimum_input_length', 'maximum_input_length', 'tags', 'placeholder', 'allow_clear',
            'close_on_select', 'maximum_selection_length', 'search_min_results') as $key
        ) {
            if (isset($data['#select2_' . $key])) {
                $value = $data['#select2_' . $key];
                $data['#attributes']['data-' . str_replace('_', '-', $key)] = is_bool($value) ? ($value ? 'true' : false) : $value;
            }
        }

        // custom select2 options
        foreach (array('ajax', 'ajax_url', 'ajax_delay', 'item_class', 'item_id_key', 'item_text_key', 'item_image_key',
            //'item_text_style', 'item_image_style', 'item_image_text_style'
        ) as $key) {
            if (isset($data['#select2_' . $key])) {
                $value = $data['#select2_' . $key];
                $data['#data']['select2-' . str_replace('_', '-', $key)] = is_bool($value) ? ($value ? 'true' : false) : $value;
            }
        }
        if (!empty($data['#select2_placehoder']) && !isset($data['#options'][''])) {
            $data['#options'] = array('' => '') + $data['#options'];
        }
        if (isset($data['#class'])) {
            $data['#class'] .= ' drts-form-select2';
        } else {
            $data['#class'] = 'drts-form-select2';
        }

        //if (!empty($data['#select2_tags'])) {
            $data['#skip_validate_option'] = true;
        //}

        // Load default items
        if (!empty($data['#default_value'])) {
            if (is_string($data['#default_value'])) {
                // Form was submitted previously
                $data['#default_value'] = explode(',', $data['#default_value']);
            } else {
                if (!is_array($data['#default_value'])) {
                    $data['#default_value'] = array($data['#default_value']);
                }
            }
            if (!$data['#multiple']) {
                $data['#default_value'] = array(array_pop($data['#default_value']));
            }
            if (!empty($data['#default_value']) && isset($data['#default_options_callback'])) {
                $this->_application->CallUserFuncArray($data['#default_options_callback'], array($data['#default_value'], &$data['#options']));
                $data['#default_value'] = array_keys($data['#options']);
            }
        }

        if (!isset(self::$_select2Elements)) {
            self::$_select2Elements = [];
        }
        if (!isset(self::$_select2Elements[$form->settings['#id']])) {
            self::$_select2Elements[$form->settings['#id']] = [];
        }
        self::$_select2Elements[$form->settings['#id']][$data['#id']] = $data['#id'];
        if (!isset($form->settings['#pre_render']['select2'])) {
            $form->settings['#pre_render']['select2'] = array($this, 'select2Callback');
        }
    }

    public function select2Callback(Form $form)
    {
        $this->_application->Form_Scripts_select2();
        $this->_application->getPlatform()->addJsFile('form-field-select.min.js', 'drts-form-field-select', array('drts-form'));
        foreach (self::$_select2Elements[$form->settings['#id']] as $id) {
            $form->settings['#js_ready'][] = sprintf('DRTS.Form.field.select("#%s");', $id);
        }
    }

    //public function bootstrapSelectCallback(Form $form)
    //{
    //    $this->_application->getPlatform()
    //      ->addJsFile('bootstrap-select.min.js', 'drts-bootstrap-select', array('drts-bootstrap'), null, true, true)
    //      ->addCssFile('bootstrap-select.min.css', 'drts-bootstrap-select', null, null, null, true);
    //}
}
