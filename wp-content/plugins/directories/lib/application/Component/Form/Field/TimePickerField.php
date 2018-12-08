<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class TimePickerField extends AbstractField
{
    protected static $_elements = [], $_locales = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }
        if (!isset($data['#default_value'])) {
            if (!empty($data['#current_time_selected'])) {
                $current_time = $this->_application->getPlatform()->getSystemToSiteTime(time());
                $data['#default_value'] = array(
                    'start' => date('H:i', $current_time),
                    'end' => '',
                    'day' => date('w', $current_time),
                );
            }
        } else {
            if (is_numeric($data['#default_value'])) {
                $data['#default_value'] = array(
                    'start' => date('H:i', $data['#default_value']),
                    'end' => '',
                    'day' => 0,
                );
            } else {
                if (isset($data['#default_value']['start']) && is_numeric($data['#default_value']['start'])) {
                    $data['#default_value']['start'] %= 86400;
                    $start_ts = mktime(0, 0, 0) + $data['#default_value']['start'];
                    $data['#default_value']['start'] = date('H:i', $start_ts);
                }
                if (isset($data['#default_value']['end']) && is_numeric($data['#default_value']['end'])) {
                    $data['#default_value']['end'] %= 86400;
                    $end_ts = mktime(0, 0, 0) + $data['#default_value']['end'];
                    $data['#default_value']['end'] = date('H:i', $end_ts);
                }
                if (isset($data['#default_value']['day'])) {
                    $data['#default_value']['day'] = (int)$data['#default_value']['day'];
                }
            }
        }

        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = self::$_locales[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$data['#id']] = $data['#id'];
        if (isset($data['#date_locale'])) {
            self::$_locales[$form->settings['#id']][$data['#id']] = $data['#date_locale'];
        }

        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!is_array($value)) {
            if ($form->isFieldRequired($data)) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
            }
            $value = null;
            return;
        }

        $value = array_map('trim', $value);
        foreach (array('start', 'end') as $key) {
            if (!isset($value[$key]) || !strlen($value[$key])) continue;

            if (false !== $time = $this->_application->Form_Validate_time($value[$key], true)) {
                $value[$key] = $time;
            } else {
                $form->setError(__('Invalid time.', 'directories'), $data);
                return;
            }
        }
        if (!isset($value['start']) || !strlen($value['start'])) {
            if ($form->isFieldRequired($data)
                || (empty($data['#disable_end']) && (isset($value['end']) && strlen($value['end'])))  // end time selected
            ) {
                $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
                return;
            }
            // no start/end value
            if (empty($data['#disable_day']) && !empty($value['day'])) { // day selected
                if (empty($data['#allow_day_only'])) {
                    $form->setError(isset($data['#required_error_message']) ? $data['#required_error_message'] : __('Please pick a time.', 'directories'), $data);
                }
                return;
            }
            // no start/end/day value
            $value = null;
        } else {
            if (empty($data['#disable_day'])) {
                if (empty($data['#allow_empty_day']) && empty($value['day'])) {
                    $form->setError(__('Please select a day of week.', 'directories'), $data);
                    return;
                }
            }
            if (empty($data['#disable_end']) && (!isset($value['end']) || !strlen($value['end']))) {
                $form->setError(__('Please select an end time.', 'directories'), $data);
            }
        }
    }

    public function formFieldRender(array &$data, Form $form)
    {
        if (!isset($data['#placeholder'])) {
            $data['#placeholder'] = 'HH:MM';
        }
        $add_clear = !isset($data['#add_clear']) || $data['#add_clear'];
        $gutter = isset($data['#gutter_size']) ? ' drts-gutter-' . $data['#gutter_size'] : '';
        $html = array('<div class="drts-row' . $gutter . '">');
        if (empty($data['#disable_day'])) {
            if (empty($data['#disable_end'])) {
                $html[] = sprintf(
                    '<div class="drts-form-timepicker-day drts-col-sm-6">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-6 drts-col-sm-3">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-6 drts-col-sm-3">%s</div>',
                    $this->_getDays($data),
                    $this->_getStartTime($data, $add_clear),
                    $this->_getEndTime($data, $add_clear)
                );
            } else {
                $html[] = sprintf(
                    '<div class="drts-form-timepicker-day drts-col-6">%s</div>'
                        . '<div class="drts-form-timepicker-start drts-col-6">%s</div>',
                    $this->_getDays($data),
                    $this->_getStartTime($data, $add_clear)
                );
            }
        } else {
            if (empty($data['#disable_end'])) {
                $html[] = sprintf(
                    '<div class="drts-form-timepicker-start drts-col-6">%s</div>'
                        . '<div class="drts-form-timepicker-end drts-col-6">%s</div>',
                    $this->_getStartTime($data, $add_clear),
                    $this->_getEndTime($data, $add_clear)
                );
            } else {
                $html[] = sprintf(
                    '<div class="drts-form-timepicker-start drts-md-6">%s</div>',
                    $this->_getStartTime($data, $add_clear)
                );
            }
        }
        $html[] = '</div>';
        $this->_render(implode(PHP_EOL, $html), $data, $form);
    }

    protected function _getDays(array $data)
    {
        $ret = array(sprintf(
            '<select class="%sform-control" name="%s[day]"%s>',
            DRTS_BS_PREFIX,
            $data['#name'],
            $data['#disabled'] ? ' disabled="disabled"' : ''
        ));
        foreach (array('' => __('— Select —', 'directories')) + $this->_application->Days() as $key => $day) {
            $ret[] = sprintf(
                '<option value="%s"%s>%s</option>',
                $key,
                isset($data['#default_value']['day']) && $data['#default_value']['day'] === $key ? ' selected="selected"' : '',
                $this->_application->H($day)
            );
        }
        $ret[] = '</select>';

        return implode(PHP_EOL, $ret);
    }

    protected function _getStartTime(array $data, $addClear)
    {
        return sprintf(
            '<div class="drts-form-flatpickr"><input type="text" name="%1$s[start]" data-date-locale="%8$s" data-date-ampm="%9$s" data-date-default-date="%2$s" class="%3$sform-control%7$s" placeholder="%4$s"%5$s />%6$s</div>',
            $data['#name'],
            isset($data['#default_value']['start']) ? $data['#default_value']['start']: '',
            DRTS_BS_PREFIX,
            $this->_application->H(isset($data['#placeholder_start']) ? $data['#placeholder_start'] : $data['#placeholder']),
            $data['#disabled'] ? ' disabled="disabled"' : '',
            $addClear ? '<i class="drts-clear fas fa-times-circle" data-clear></i>' : '',
            $addClear ? ' drts-form-type-textfield-with-clear' : '',
            isset($data['#date_locale']) ? $data['#date_locale'] : 'false',
            empty($data['#date_ampm']) ? 'false' : 'true'
        );
    }

    protected function _getEndTime(array $data, $addClear)
    {
        return sprintf(
            '<div class="drts-form-flatpickr"><input type="text" name="%1$s[end]" data-date-locale="%8$s" data-date-ampm="%9$s" data-date-default-date="%2$s" class="%3$sform-control" placeholder="%4$s"%5$s />%6$s</div>',
            $data['#name'],
            isset($data['#default_value']['end']) ? $data['#default_value']['end'] : '',
            DRTS_BS_PREFIX,
            $this->_application->H(isset($data['#placeholder_end']) ? $data['#placeholder_end'] : $data['#placeholder']),
            $data['#disabled'] ? ' disabled="disabled"' : '',
            $addClear ? '<i class="drts-clear fas fa-times-circle" data-clear></i>' : '',
            $addClear ? ' drts-form-type-textfield-with-clear' : '',
            isset($data['#date_locale']) ? $data['#date_locale'] : 'false',
            empty($data['#date_ampm']) ? 'false' : 'true'
        );
    }

    public function preRenderCallback(Form $form)
    {
        $this->_application->Form_Scripts_date(true, self::$_locales[$form->settings['#id']]);

        $js = [];
        // Add js to instantiate date/time pickers
        foreach (self::$_elements[$form->settings['#id']] as $id) {
            $js[] = 'DRTS.Form.field.timepicker("#'. $id .'");';
        }
        // Add js
        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);
    }
}
