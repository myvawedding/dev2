<?php
namespace SabaiApps\Directories\Component\CSV\Exporter;

use SabaiApps\Directories\Component\Entity;

class FieldExporter extends AbstractExporter
{
    protected function _csvExporterInfo()
    {
        return array(
            'field_types' => array(substr($this->_name, 6)), // remove field_ part
        );
    }
    
    public function csvExporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        if ($this->_name === 'field_boolean') return;
        
        $form = $reserved_separator = [];
        
        switch ($this->_name) {                
            case 'field_video':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Video provider/ID separator', 'directories'),
                        '#description' => __('Enter the character used to separate the video provider and ID.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_range':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Field min/max separator', 'directories'),
                        '#description' => __('Enter the character used to separate the minimum and maximum values.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            case 'field_date':
                $form += $this->_getDateFormatSettingsForm($parents);
                break;
            case 'field_time':
                $form += array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => __('Start/End/Day separator', 'directories'),
                        '#description' => __('Enter the character used to separate the starting time, ending time, and day of week.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                    ),
                );
                $reserved_separator['separator'] = $form['separator']['#title'];
                break;
            default:
        }
        
        if ($field->isCustomField()) {
            $form += $this->_acceptMultipleValues($field, $enclosure, $parents, $reserved_separator);
        }
        
        return $form;
    }
    
    public function csvExporterDoExport(Entity\Model\Field $field, array $settings, $value, array $columns, array &$formStorage)
    {
        $ret = [];
        switch ($this->_name) {
            case 'field_boolean':
                if (!empty($value[0])) {
                    $ret[] = (int)$value[0];
                }
                break;
            case 'field_video':
                foreach ($value as $_value) {
                    $ret[] = $_value['provider'] . $settings['separator'] . $_value['id'];
                }
                break;
            case 'field_range':
                foreach ($value as $_value) {
                    $ret[] = $_value['min'] . $settings['separator'] . $_value['max'];
                }
                break;
            case 'field_user':
                foreach ($value as $_value) {
                    $ret[] = $_value->id;
                }
                break;
            case 'field_date':
                $ret = [];
                switch ($settings['date_format']) {
                    case 'string':
                        foreach ($value as $_value) {
                            if (false !== $__value = @date($settings['date_format_php'], $_value)) {
                                $ret[] = $__value;
                            } else {
                                $ret[] = $_value;
                            }
                        }
                        break;
                    default:
                        foreach ($value as $_value) {
                            $ret[] = $_value;
                        }
                }
                break;
            case 'field_time':
                $ret = [];
                foreach ($value as $_value) {
                    if (false === $start = @date('H:i', $_value['start'])) {
                        $start = (string)$_value['start'];
                    }
                    if (empty($_value['end'])
                        || false === ($end = @date('H:i', $_value['end']))
                    ) {
                        $end = (string)$_value['end'];
                    }
                    $ret[] = implode($settings['separator'], array($start, $end, (string)@$_value['day']));
                }
                break;
            default:
                $ret = $value;
        }
        
        return isset($settings['_separator']) ? implode($settings['_separator'], $ret) : $ret[0];
    }
}
