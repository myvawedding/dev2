<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class DatePickerField extends AbstractField
{
    protected static $_elements = [], $_locales = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        if (!isset($data['#id'])) {
            $data['#id'] = $form->getFieldId($name);
        }

        $data['#disable_time'] = isset($data['#disable_time']) ? $data['#disable_time'] : false;
        if (empty($data['#enable_range'])) {
            if (!array_key_exists('#empty_value', $data)) {
                $data['#empty_value'] = null;
            }
            if (!isset($data['#default_value'])) {
                if (!empty($data['#current_date_selected'])) {
                    $data['#default_value'] = $this->_application->getPlatform()->getSystemToSiteTime(time());
                } else {
                    $data['#default_value'] = $data['#empty_value'];
                }
            } else {
                if (is_int($data['#default_value'])) {
                    $data['#default_value'] = $this->_application->getPlatform()->getSystemToSiteTime($data['#default_value']);
                }
            }
        }

        // Define min/max date
        if (isset($data['#min_date']) && !is_int($data['#min_date'])) {
            unset($data['#min_date']);
        }
        if (isset($data['#max_date'])) {
            if (!is_int($data['#max_date'])
                || (isset($data['#min_date']) && $data['#max_date'] < $data['#min_date'])
            ) {
                unset($data['#max_date']);
            }
        }

        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = self::$_locales[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$data['#id']] = $data['#id'];
        if (!isset($data['#date_locale'])) {
            $data['#date_locale'] = $this->_application->Form_Scripts_locale();
        }
        if ($data['#date_locale']) {
            self::$_locales[$form->settings['#id']][$data['#id']] = $data['#date_locale'];
        }

        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }

    protected function _getDateInput($data)
    {
        if (!isset($data['#attributes']['placeholder'])) {
            $data['#attributes']['placeholder'] = empty($data['#enable_range']) ? __('Select date', 'directories') : __('Select date range', 'directories');
        }
        if (!empty($data['#default_value'])) {
            if (!empty($data['#enable_range'])) {
                if (!is_array($data['#default_value'])) {
                    $_default_values = explode(' to ', trim($data['#default_value']));
                    if (!$_default_values
                        || !isset($_default_values[0])
                        || !isset($_default_values[1])
                    ) {
                        unset($data['#default_value']);
                    } else {
                        $data['#default_value'] = [$_default_values[0], $_default_values[1]];
                    }
                }
                if (!empty($data['#default_value'])
                    && count($data['#default_value']) === 2
                ) {
                    $_default_values = array_values($data['#default_value']);
                    if (is_int($_default_values[0])) $_default_values[0] *= 1000;
                    if (is_int($_default_values[1])) $_default_values[1] *= 1000;
                    $data['#attributes']['data-date-default-date'] = json_encode($_default_values);
                }
            } else {
                if (is_int($data['#default_value'])) {
                    $data['#attributes']['data-date-default-date'] = $data['#default_value'] !== $data['#empty_value'] ? date('Y-m-d H:i', $data['#default_value']) : '';
                } elseif (is_string($data['#default_value'])) {
                    // only date
                    $data['#attributes']['data-date-default-date'] = $data['#default_value'];
                }
            }
        }

        $mode = 'single';
        if (!empty($data['#enable_range'])) {
            $mode = 'range';
        } elseif (!empty($data['#multiple'])) {
            $mode = 'multiple';
        }
        if ($data['#date_locale']) {
            $data['#attributes']['data-date-locale'] = $data['#date_locale'];
        }
        if (isset($data['#date_display_format'])) {
            $default_display_format = $data['#date_display_format'];
        } else {
            $default_display_format = $this->_application->getPlatform()->getDateFormat();
            if (empty($data['#disable_time'])) {
                $default_display_format .= ' ' . $this->_application->getPlatform()->getTimeFormat();
            }
        }
        // Replace PHP date/time token to that of flatpickr
        $default_display_format = strtr($default_display_format, [
            'c' => 'Z',
            'g' => 'h',
            's' => 'S',
            'a' => 'K', // am/pm
            'A' => 'K', // AM/PM
        ]);
        $data['#attributes']['data-date-display-format'] = $this->_application->Filter('form_field_datepicker_date_format', $default_display_format);
        foreach (['min_date', 'max_date'] as $date_key) {
            if (!empty($data['#' . $date_key])) {
                $data['#attributes']['data-' . str_replace('_', '-', $date_key)] = date('Y/m/d', $data['#' . $date_key]);
            }
        }
        $add_clear = !isset($data['#add_clear']) || $data['#add_clear'];
        $date = sprintf(
            '<div class="drts-form-flatpickr"><input type="text" name="%1$s" size="8" class="%2$sform-control drts-form-datepicker-date%7$s" data-date-mode="%3$s" data-date-enable-time="%4$d"%5$s />%6$s</div>',
            $data['#name'],
            DRTS_BS_PREFIX,
            $mode,
            empty($data['#disable_time']),
            $this->_application->Attr($data['#attributes']),
            $add_clear ? '<i class="drts-clear fas fa-times-circle" data-clear></i>' : '',
            $add_clear ? ' drts-form-type-textfield-with-clear' : ''
        );

        return '<div class="drts-row"><div class="drts-col-md-6 drts-view-filter-ignore">' . $date . '</div></div>';
    }

    public function formFieldSubmit(&$value, array &$data, Form $form)
    {
        if (!empty($data['#enable_range'])) {
            if (strlen($value)
                && ($_value = explode(' to ', trim($value)))
                && is_array($_value)
                && isset($_value[0])
                && isset($_value[1])
                && false !== ($_value[0] = $this->_application->Form_Validate_date($_value[0], $data, $form))
                && false !== ($_value[1] = $this->_application->Form_Validate_date($_value[1], $data, $form))
            ) {
                $value = $_value;
                return;
            }
        } else {
            if (false !== $validated = $this->_application->Form_Validate_date($value, $data, $form)) {
                $value = $validated;
                return;
            }
        }
        $value = null;
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $this->_render($this->_getDateInput($data), $data, $form);
    }

    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts_date(false, self::$_locales[$form->settings['#id']]);

        $js = [];
        // Add js to instantiate date/time pickers
        foreach (self::$_elements[$form->settings['#id']] as $id) {
            $js[] = 'DRTS.Form.field.datepicker("#'. $id .'");';
        }
        // Add js
        $form->settings['#js_ready'][] = implode(PHP_EOL, $js);
    }
}
