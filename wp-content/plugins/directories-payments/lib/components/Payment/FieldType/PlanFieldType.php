<?php
namespace SabaiApps\Directories\Component\Payment\FieldType;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Application;

class PlanFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\ISortable,
    Field\Type\IRestrictable,
    Field\Type\IHumanReadable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Payment Plan', 'directories-payments'),
            'creatable' => false,
            'default_renderer' => $this->_name,
            'default_settings' => [],
            'admin_only' => true,
            'entity_types' => array('post'),
            'icon' => 'far fa-money-bill-alt'
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'expires_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'expires_at',
                    'default' => 0,
                ),
                'deactivated_at' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'deactivated_at',
                    'default' => 0,
                ),
                'plan_id' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'length' => 20,
                    'was' => 'plan_id',
                ),
                'addon_features' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'addon_features',
                ),
                'extra_data' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'addon_features',
                ),
            ),
            'indexes' => array(
                'expires_at' => array(
                    'fields' => array('expires_at' => array('sorting' => 'ascending')),
                    'was' => 'expires_at',
                ),
                'deactivated_at' => array(
                    'fields' => array('deactivated_at' => array('sorting' => 'ascending')),
                    'was' => 'deactivated_at',
                ),
                'plan_id' => array(
                    'fields' => array('plan_id' => array('sorting' => 'ascending')),
                    'was' => 'plan_id',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $value = array_shift($values); // single entry allowed for this field
        if (!is_array($value)) {
            if ($value === null) return; // do nothing

            return array(false); // delete
        }

        // Keep expiration date
        if (!isset($value['expires_at'])) {
            if (!empty($currentValues[0]['expires_at'])) {
                $value['expires_at'] = $currentValues[0]['expires_at'];
            } else {
                $value['expires_at'] = 0;
            }
        }

        // Keep deactivation date unless expiration date ahs been updated
        if (!isset($value['deactivated_at'])) {
            if (!empty($currentValues[0]['deactivated_at'])
                && $currentValues[0]['deactivated_at'] >= $value['expires_at']
            ) {
                $value['deactivated_at'] = $currentValues[0]['deactivated_at'];
            } else {
                $value['deactivated_at'] = 0;
            }
        }

        // Make sure plan id is set
        if (!isset($value['plan_id'])) {
            if (empty($currentValues[0]['plan_id'])) {
                return array(false); // delete
            }

            $value['plan_id'] = $currentValues[0]['plan_id'];
        } elseif (empty($value['plan_id'])) {
            return array(false); // delete
        } else {
            if ($default_lang = $this->_application->getPlatform()->getDefaultLanguage()) {
                $value['plan_id'] = $this->_application->getComponent('Payment')
                    ->getPaymentComponent()
                    ->paymentGetPlanId($value['plan_id'], $default_lang);
            }
        }

        // Add or keep addon features
        if (isset($value['addon_features']) && is_array($value['addon_features'])) {
            foreach (array_keys($value['addon_features']) as $feature_name) {
                if ($value['addon_features'][$feature_name] === false) {
                    unset($value['addon_features'][$feature_name], $currentValues[0]['addon_features'][$feature_name]);
                }
            }
            if (!empty($currentValues[0]['addon_features'])) {
                $value['addon_features'] += $currentValues[0]['addon_features'];
            }
        } else {
            $value['addon_features'] = isset($currentValues[0]['addon_features']) ?
                $currentValues[0]['addon_features'] :
                [];
        }
        $value['addon_features'] = empty($value['addon_features']) ? '' : serialize($value['addon_features']);

        if (!isset($value['extra_data'])) {
            if (!empty($currentValues[0]['extra_data'])) {
                $value['extra_data'] = $currentValues[0]['extra_data'];
            }
        }
        $value['extra_data'] = empty($value['extra_data']) ? '' : serialize($value['extra_data']);

        return array($value);
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        foreach (array_keys($values) as $key) {
            $values[$key]['addon_features'] = strlen($values[$key]['addon_features']) ? (array)@unserialize($values[$key]['addon_features']) : [];
            $values[$key]['extra_data'] = strlen($values[$key]['extra_data']) ? (array)@unserialize($values[$key]['extra_data']) : [];
        }
    }

    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {
        foreach (array_keys($currentLoadedValue) as $key) {
            $currentLoadedValue[$key]['addon_features'] = serialize(isset($currentLoadedValue[$key]['addon_features']) ? (array)$currentLoadedValue[$key]['addon_features'] : []);
            $currentLoadedValue[$key]['extra_data'] = serialize(isset($currentLoadedValue[$key]['extra_data']) ? (array)$currentLoadedValue[$key]['extra_data'] : []);
        }
        return $currentLoadedValue !== $valueToSave;
    }

    public function fieldQueryableInfo(Field\IField $field)
    {
        return array(
            'example' => 'default',
            'tip' => __('Enter payment plan IDs separated with commas. Enter 1 only to query items with a payment plan, 0 only to query items without any payment plan, -1 only to query expired items, -2 only to query deactivated items, or -3 only to query expiring items.', 'directories-payments'),
        );
    }

    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle = null)
    {
        if ($paramStr == 1) {
            $query->fieldIsNotNull($fieldName, 'plan_id');
        } elseif ($paramStr == 0) {
            $query->fieldIsNull($fieldName, 'plan_id');
        } elseif ($paramStr == -1) {
            $query->fieldIsOrSmallerThan($fieldName, time(), 'expires_at')
                ->fieldIsGreaterThan($fieldName, 0, 'expires_at');
        } elseif ($paramStr == -2) {
            $query->fieldIsGreaterThan($fieldName, 0, 'deactivated_at');
        } elseif ($paramStr == -3) {
            $expiring_ts = time() + 86400 * $this->_application->getComponent('Payment')->getConfig('renewal', 'expiring_days');
            $query->fieldIsGreaterThan($fieldName, time(), 'expires_at')
                ->fieldIsOrSmallerThan($fieldName, $expiring_ts, 'expires_at');
        } else {
            if ($plan_ids = $this->_queryableParams($paramStr)) {
                $query->fieldIsIn($fieldName, $plan_ids, 'plan_id');
            }
        }
    }

    public function fieldSortableOptions(Field\IField $field)
    {
        return array(
            array('label' => __('Expiration Date', 'directories-payments')),
        );
    }

    public function fieldSortableSort(Field\Query $query, $fieldName, array $args = null)
    {
        $query->sortByField($fieldName, 'ASC', 'expires_at', null, null, true); // moves NULL or 0 to last in order
    }

    public function fieldRestrictableOptions(Field\IField $field)
    {
        if (empty($field->Bundle->info['payment_enable'])
            || (!$plans = $this->_application->Payment_Plans($field->Bundle->name, 'base'))
        ) return;

        foreach (array_keys($plans) as $plan_id) {
            $plans[$plan_id] = $plans[$plan_id]->paymentPlanTitle();
        }
        $plans['-1'] = __('Show all expired', 'directories-payments');
        $plans['-2'] = __('Show all deactivated', 'directories-payments');
        $plans['-3'] = __('Show all expiring', 'directories-payments');

        return $plans;
    }

    public function fieldRestrictableRestrict(Field\IField $field, $value)
    {
        if ($value == -1) {
            return array('column' => 'expires_at', 'compare' => '<=', 'value' => time());
        } elseif ($value == -2) {
            return array('column' => 'deactivated_at', 'compare' => '>', 'value' => 0);
        } elseif ($value == -3) {
            $expiring_ts = time() + 86400 * $this->_application->getComponent('Payment')->getConfig('renewal', 'expiring_days');
            return array('column' => 'expires_at', 'compare' => 'BETWEEN', 'value' => array(time(), $expiring_ts));
        }
        return array('column' => 'plan_id');
    }

    public function fieldHumanReadableText(Field\IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;

        switch ($key) {
            case 'expire_on':
                return empty($value['expires_at']) ? '' : $this->_application->System_Date($value['expires_at']);
            case 'expire_days':
                if (empty($value['expires_at'])) return '';

                $days = floor(($value['expires_at'] - time()) / 86400);
                if ($days === 0) $days = 1;
                return $days;
            default:
                return ($plan = $this->_application->Payment_Plan($entity)) ? $plan->paymentPlanTitle() : '';
        }
    }
}
