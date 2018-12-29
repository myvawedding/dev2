<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

abstract class AbstractType implements IType
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }

    public function fieldTypeInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_fieldTypeInfo() + ['cacheable' => true];
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = []){}
    
    public function fieldTypeSchema(){}

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = []){}

    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity){}
    
    public function fieldTypeIsModified($field, $valueToSave, $currentLoadedValue)
    {   
        return array_values($currentLoadedValue) !== array_values($valueToSave);
    }

    public function validateMinMaxSettings($form, &$value, $element, $decimalField = false)
    {
        $integer = $decimalField && empty($value[$decimalField]);
        if (isset($value['min'])) {
            if (!strlen($value['min'])) {
                unset($value['min']);
            } elseif ($integer) {
                $value['min'] = intval($value['min']);
            }
        }
        if (isset($value['max'])) {
            if (!strlen($value['max'])) {
                unset($value['max']);
            } elseif ($integer) {
                $value['max'] = intval($value['max']);
            }
        }
        if (isset($value['min']) && isset($value['max'])) {
            if ($value['min'] >= $value['max']) {
                $form->setError(__('The value must be greater than the first value.', 'directories'), $element['#name'] . '[max]');
            }
        }
    }
    
    public function fieldQueryableInfo(IField $field)
    {
        return array('example' => '', 'tip' => '');
    }

    abstract protected function _fieldTypeInfo();
    
    protected function _queryableParams($paramStr, $trim = true)
    {
        if ($trim) $paramStr = trim($paramStr, ',');
        $params = explode(',', $paramStr);
        return empty($params) ? [] : array_map('trim', $params);
    }
}
