<?php
namespace SabaiApps\Directories\Component\DirectoryPro\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class OpeningHoursFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Opening Hours', 'directories-pro'),
            'field_types' => array('time'),
            'default_settings' => array(
                'show_closed' => true,
                'closed' => _x('Closed', 'opening hours', 'directories-pro'),
                '_separator' => ', ',
            ),
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'show_closed' => array(
                '#type' => 'checkbox',
                '#title' => __('Show days without any entry as closed', 'directories-pro'),
                '#default_value' => $settings['show_closed'],
            ),
            'closed' => array(
                '#type' => 'textfield',
                '#title' => __('Label for closed days', 'directories-pro'),
                '#default_value' => $settings['closed'],
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[show_closed]"]', $this->_application->Form_FieldName($parents)) => array(
                            'type' => 'checked',
                            'value' => true,
                        ),
                    ),
                ),
            ),
        );
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $field_settings = $field->getFieldSettings();
        if (empty($field_settings['enable_day'])) return '';

        // If timezone value is available, get the current datetime of the timezone
        $current_day = $current_time = null;
        if ($timezone = $entity->getSingleFieldValue('location_address', 'timezone')) {
            try {
                $dt = new \DateTime('now', new \DateTimeZone($timezone));
                $current_day = (int)$dt->format('N');
                $current_time = $dt->format('G') * 3600 + (int)$dt->format('i') * 60;
            } catch (\Exception $e) {
                $this->_application->logError('Invalid timezone or error (ID: ' . $entity->getId() . ', timezone: ' . $timezone . ', message: ' . $e->getMessage());
                return;
            }
        }

        $_values = [];
        foreach ($values as $value) {
            if (empty($value['day'])) continue;

            $_values[$value['day']][$value['start']] = $value['end'];
        }

        $value_text_align_class = DRTS_BS_PREFIX . ($this->_application->getPlatform()->isRtl() ? 'text-left' : 'text-right');
        $html = ['<div class="' . DRTS_BS_PREFIX . 'list-group ' . DRTS_BS_PREFIX . 'list-group-flush drts-entity-fieldlist drts-directory-opening-hours">'];
        $closed_label = null;
        foreach ($this->_application->Days() as $day => $day_label) {
            if (!isset($_values[$day])) {
                if (!$settings['show_closed']) continue;

                if (!isset($closed_label)) {
                    $closed_label = $this->_application->getPlatform()->translateString($settings['closed'], 'opening_hours_closed_label', 'directorypro');
                }
                $time_label = $closed_label;
                $is_open = false;
            } elseif (1 === $count = count($_values[$day])) {
                $_start = current(array_keys($_values[$day]));
                $_end = current($_values[$day]);
                $time_label = sprintf(
                    '%s - %s',
                    $this->_application->System_Date_time($_start),
                    $this->_application->System_Date_time($_end)
                );
                $is_open = $day === $current_day
                    && $current_time >= $_start
                    && $current_time <= $_end;
            } else {
                ksort($_values[$day]); // sort by starting time
                $starts = array_keys($_values[$day]);
                $ends = array_values($_values[$day]);
                $i = 0;
                for ($j = 1; $j < $count; ++$j) {
                    if ($starts[$j] > $ends[$i] + 60) {
                        $i = $j;
                    } else {
                        if ($ends[$i] < $ends[$j]) {
                            $ends[$i] = $ends[$j];
                        }
                        unset($starts[$j], $ends[$j]);
                    }
                }
                $_ret = [];
                foreach (array_keys($starts) as $i) {
                    $_ret[] = sprintf(
                        '%s - %s',
                        $this->_application->System_Date_time($starts[$i]),
                        $this->_application->System_Date_time($ends[$i])
                    );
                    $is_open = empty($is_open)
                        && $day === $current_day
                        && $current_time >= $starts[$i]
                        && $current_time <= $ends[$i];
                }
                $time_label = implode($settings['_separator'], $_ret);
            }
            if ($is_open) {
                $time_label = '<i title="' . $this->_application->H(__('Open Now', 'directories-pro')) . '" class="fa-fw fas fa-check-circle ' . DRTS_BS_PREFIX . 'text-success"></i> ' . $time_label;
            }
            $is_open_class = $is_open ? ' drts-directory-listing-open-now' : '';
            $html[] = '<div class="' . DRTS_BS_PREFIX . 'list-group-item ' . DRTS_BS_PREFIX . 'px-0' . $is_open_class . '"><div class="drts-entity-field ' . DRTS_BS_PREFIX . 'justify-content-between">';
            $html[] = '<div class="drts-entity-field-label">' . $this->_application->H($day_label) . '</div>';
            $html[] = '<div class="drts-entity-field-value ' . $value_text_align_class . '">' . $time_label . '</div>';
            $html[] = '</div></div>';
        }
        $html[] = '</div>';
        return implode(PHP_EOL, $html);
    }
}
