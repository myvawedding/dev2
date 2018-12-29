<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

abstract class AbstractValueType extends AbstractType
{
    protected $_valueColumn = 'value';

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $settings = (array)$field->getFieldSettings();
        $ret = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                if (empty($value) || !isset($value[$this->_valueColumn])) {
                    continue;
                }
                $value = (string)$value[$this->_valueColumn];
            }
            $value = $this->_onSaveValue($value, $settings);
            if (strlen($value) === 0) continue;

            $ret[][$this->_valueColumn] = $value;
        }

        return $ret;
    }

    protected function _onSaveValue($value, array $settings)
    {
        $value = (string)$value;
        return strlen($value) === 0 ? null : $value;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity)
    {
        foreach ($values as $key => $value) {
            $values[$key] = $value[$this->_valueColumn];
        }
    }
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {   
        $new = [];
        foreach ($valueToSave as $value) {
            $new[] = $value[$this->_valueColumn];
        }
        return $currentLoadedValue !== $new;
    }
}