<?php
namespace SabaiApps\Directories\Component\Claiming\FieldWidget;

use SabaiApps\Directories\Component\Field\Widget\AbstractWidget;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class StatusFieldWidget extends AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Claim Status', 'directories-pro'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(IField $field, array $settings, $value = null, IEntity $entity = null, array $parents = [], $language = null)
    {
        if (isset($entity)
            && !$entity->isPublished()
        ) return;

        if (!$this->_application->HasPermission('entity_delete_others_' . $field->Bundle->name)) return;
        
        if ($value = isset($value) && in_array($value, array('approved', 'rejected')) ? $value : null) { // do not allow change if already set
            return array(
                '#type' => 'item',
                '#markup' => $this->_getLabel($value),
            );
        }
            
        return array(
            '#type' => 'radios',
            '#options' => array(
                'approved' => $this->_getLabel('approved'),
                'rejected' => $this->_getLabel('rejected'),
            ),
            '#option_no_escape' => true,
            '#value' => $value,
            '#disabled' => isset($value),
        );
    }
    
    protected function _getLabel($status)
    {
        return sprintf(
            '<span class="%1$sbadge %1$sbadge-%2$s">%3$s</span>',
            DRTS_BS_PREFIX,
            $status === 'approved' ? 'success' : 'danger',
            $this->_application->H($status === 'approved' ? __('Approved', 'directories-pro') : __('Rejected', 'directories-pro'))
        );
    }
}