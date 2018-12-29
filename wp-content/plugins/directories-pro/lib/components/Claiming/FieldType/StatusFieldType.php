<?php
namespace SabaiApps\Directories\Component\Claiming\FieldType;

use SabaiApps\Directories\Component\Field\Type\AbstractValueType;
use SabaiApps\Directories\Component\Field\Type\IColumnable;
use SabaiApps\Directories\Component\Field\Type\IRestrictable;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;

class StatusFieldType extends AbstractValueType implements IColumnable, IRestrictable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Claim Status', 'directories-pro'),
            'creatable' => false,
            'admin_only' => true,
        );
    }
    
    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        if (!$this->_application->HasPermission('entity_delete_others_' . $field->Bundle->name)) return;
        
        return ($ret = parent::fieldTypeOnSave($field, $values, $currentValues, $extraArgs)) ? $ret : null;
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'length' => 10,
                    'notnull' => true,
                    'was' => 'value',
                    'default' => '',
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }
    
    public function fieldColumnableInfo(IField $field)
    {
        return array(
            'label' => $field->getFieldLabel(),
        );
    }
    
    public function fieldColumnableColumn(IField $field, $value)
    {
        $status = isset($value[0]) ? $value[0] : null;
        switch ($status) {
            case 'approved':
                $color = 'success';
                $label = __('Approved', 'directories-pro');
                break;
            case 'rejected':
                $color = 'danger';
                $label = __('Rejected', 'directories-pro');
                break;
            default:
                $color = 'warning';
                $label = __('Pending', 'directories-pro');
        }
        return '<span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-' . $color . '">' . $this->_application->H($label) . '</span>';
    }
    
    public function fieldRestrictableOptions(IField $field)
    {
        return array(
            'approved' => __('Approved', 'directories-pro'),
            'rejected' => __('Rejected', 'directories-pro'),
            'pending' => __('Pending', 'directories-pro'),
        );
    }
    
    public function fieldRestrictableRestrict(IField $field, $value)
    {
        return ($value === 'pending') ? array('compare' => 'NULL') : [];
    }
}