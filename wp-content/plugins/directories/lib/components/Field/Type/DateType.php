<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Application;

class DateType extends AbstractType
    implements ISortable, IQueryable, IOpenGraph, IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Date', 'directories'),
            'default_widget' => 'date',
            'default_renderer' => 'date',
            'default_settings' => array(
                'date_range_enable' => false,
                'date_range' => null,
                'enable_time' => true,
            ),
            'icon' => 'far fa-calendar-alt',
        );
    }

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return array(
            'enable_time' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable time (hour and minute)', 'directories'),
                '#default_value' => !empty($settings['enable_time']),
            ),
            'date_range_enable' => array(
                '#type' => 'checkbox',
                '#title' => __('Restrict dates', 'directories'),
                '#default_value' => !empty($settings['date_range_enable']),
            ),
            'date_range' => array(
                '#type' => 'datepicker',
                '#enable_range' => true,
                '#default_value' => is_array($settings['date_range']) ? $settings['date_range'] : null,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[date_range_enable]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => false,
                    'length' => 20,
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values)
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (!is_numeric($value)
                && (!$value = strtotime($value))
            ) {
                continue;
            } else {
                $value = intval($value);
            }
            $ret[]['value'] = $value;
        }

        return $ret;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $value['value'];
        }
    }

    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value['value'];
        }
        return $currentLoadedValue !== $new;
    }

    public function fieldSortableOptions(IField $field)
    {
        return array(
            [],
            array('args' => array('desc'), 'label' => __('%s (desc)', 'directories'))
        );
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, isset($args) && $args[0] === 'desc' ? 'DESC' : 'ASC');
    }

    public function fieldQueryableInfo(IField $field)
    {
        return array(
            'example' => '12/31/2017,28-3-99,now',
            'tip' => __('Enter a single date string for exact date match, two date strings separated with a comma for date range search.', 'directories'),
        );
    }

    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        $params = $this->_queryableParams($paramStr, false);
        switch (count($params)) {
            case 0:
                break;
            case 1:
                if (strlen($params[0])
                    && ($time = strtotime($params[0]))
                ) {
                    $query->fieldIs($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
                break;
            default:
                if (strlen($params[0])
                    && ($time = strtotime($params[0]))
                ) {
                    $query->fieldIsOrGreaterThan($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
                if (strlen($params[1])
                    && ($time = strtotime($params[1]))
                ) {
                    $query->fieldIsOrSmallerThan($fieldName, $this->_application->getPlatform()->getSiteToSystemTime($time));
                }
        }
    }

    public function fieldOpenGraphProperties()
    {
        return array('books:release_date', 'music:release_date', 'video:release_date');
    }

    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        return array(date('c', $value));
    }

    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';

        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->_application->System_Date_datetime($value);
        }
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
}
