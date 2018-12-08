<?php
namespace SabaiApps\Directories\Component\View\Model;

class Filter extends Base\Filter
{
    public function isCustomFilter()
    {
        return strpos($this->name, 'field_') === 0 || !empty($this->data['is_custom']);
    }
    
    public function getField()
    {
        return $this->_model->getComponentEntity('Entity', 'Field', $this->field_id);
    }
    
    public function toDisplayElementArray()
    {
        if (!$field = $this->getField()) return; // field does not exist
        
        return array(
            'name' => 'view_filter_' . $field->getFieldName(),
            'system' => false,
            'data' => array(
                'settings' => array(
                    'filter_id' => $this->id,
                    'filter' => $this->type,
                    'filter_name' => $this->name,
                    'filter_settings' => [
                        $this->name => $this->data['settings']
                    ],
                ),
            ),
        );
    }
}

class FilterRepository extends Base\FilterRepository
{
}