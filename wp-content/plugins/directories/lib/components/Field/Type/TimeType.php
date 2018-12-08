<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class TimeType extends AbstractType implements ISortable, ISchemable, IQueryable, IOpenGraph, IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Time', 'directories'),
            'default_widget' => 'time',
            'default_renderer' => 'time',
            'default_settings' => array(
                'enable_day' => false,
                'enable_end' => false,
            ),
            'icon' => 'far fa-clock',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            'enable_day' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable day of week', 'directories'),
                '#default_value' => !empty($settings['enable_day']),
            ),
            'enable_end' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable end time', 'directories'),
                '#default_value' => !empty($settings['enable_end']),
            ),
        );
    }

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity){}

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'start' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 8,
                    'was' => 'start',
                    'default' => '0',
                ),
                'end' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 8,
                    'was' => 'end',
                    'default' => '0',
                ),
                'day' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'length' => 1,
                    'was' => 'day',
                    'default' => '0',
                ),
            ),
            'indexes' => array(
                'start' => array(
                    'fields' => array('start' => array('sorting' => 'ascending')),
                ),
                'end' => array(
                    'fields' => array('end' => array('sorting' => 'ascending')),
                ),
                'day' => array(
                    'fields' => array('day' => array('sorting' => 'ascending')),
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values)
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (is_array($value)) {
                $value += array('start' => 0, 'end' => null, 'day' => null);
            } else {
                if (!is_numeric($value)) {
                    continue;
                }
                $value = array('start' => $value, 'end' => null, 'day' => null);
            }

            if (isset($value['day'])) {
                $value['day'] = intval($value['day']);
                if ($value['day'] > 7 && $value['day'] % 7) {
                    $value['day'] = $value['day'] % 7;
                }
            } else {
                $value['day'] = 0;
            }
            $value['start'] = intval($value['start']) % 86400;
            if ($value['start'] < 0) $value['start'] += 86400;
            if (isset($value['end'])) {
                $value['end'] = intval($value['end']) % 86400;
                if ($value['end'] < $value['start']) {
                    $value['end'] += 86400;
                }
            } else {
                $value['end'] = $value['start'];
            }
            $ret[] = $value;
        }
        return $ret;
    }

    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories')),
        );
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC', 'start');
    }

    public function fieldSchemaProperties()
    {
        return array('openingHoursSpecification');
    }

    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $days = $this->_application->Days();
        $_values = [];
        foreach ($values as $value) {
            if (!$value['day'] || !isset($days[$value['day']])) continue;

            $_values[$value['start']][$value['end']][] = $days[$value['day']];
        }
        if (empty($_values)) return;

        $ret = [];
        foreach ($_values as $start => $__values) {
            foreach ($__values as $end => $_days) {
                $ret[] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => $_days,
                    'opens' => date('H:i', $start),
                    'closes' => date('H:i', $end),
                );
            }
        }
        return $ret;
    }

    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => '8:30,17:30,7',
            'tip' => __('Enter a single numeric value to query by day of week (1 = Mon, 7 = Sun), two time values for time range query, and three values for day and time range query, e.g. "1:00,24:00,2" for Tuesday 1:00 - 24:00.', 'directories'),
        );
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])) {
                    $query->fieldIs($fieldName, $params[0], 'day');
                }
                break;
            default:
                if (strlen($params[0])
                    && false !== ($params[0] = $this->_application->Form_Validate_time($params[0], true))
                ) {
                    $start = $params[0];
                    $query->fieldIsOrSmallerThan($fieldName, $params[0], 'start');
                }
                if (strlen($params[1])
                    && false !== ($params[1] = $this->_application->Form_Validate_time($params[1], true))
                ) {
                    if (isset($start)) {
                        if ($params[1] < $start) $params[1] += 86400;
                        $query->fieldIsOrGreaterThan($fieldName, $params[1], 'end');
                    } else {
                        $query->startCriteriaGroup('OR')
                            ->startCriteriaGroup()
                                ->fieldIsOrSmallerThan($fieldName, $params[1], 'start')
                                ->fieldIsOrGreaterThan($fieldName, $params[1], 'end')
                            ->finishCriteriaGroup()
                            ->startCriteriaGroup()
                                ->fieldIsGreaterThan($fieldName, $params[1], 'start')
                                ->fieldIsOrGreaterThan($fieldName, $params[1] + 86400, 'end')
                            ->finishCriteriaGroup()
                            ->finishCriteriaGroup();
                    }
                }
                if (strlen($params[2])) {
                    $query->fieldIs($fieldName, $params[2], 'day');
                }
                break;
        }
    }

    public function fieldOpenGraphProperties()
    {
        return array('business:hours');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $days = array(
            1 => 'monday',
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            7 => 'sunday',
        );
        $ret = [];
        foreach ($values as $value) {
            if (!$value['day'] || !isset($days[$value['day']])) continue;

            $ret[] = array(
                'business:hours:day' => $days[$value['day']],
                'business:hours:start' => date('H:i', $value['start']),
                'business:hours:end' => date('H:i', $value['end']),
            );
        }

        return $ret;
    }

    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        $ret = [];
        foreach ($values as $value) {
            $_ret = [];
            if (!empty($value['day'])) {
                $_ret[] = $this->_application->Days($value['day']);
            }
            $_ret[] = $this->_application->System_Date_time($value['start']);
            if (!empty($value['end'])) {
                $_ret[] = '-';
                $_ret[] = $this->_application->System_Date_time($value['end']);
            }
            $ret[] = implode(' ', $_ret);
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
}
