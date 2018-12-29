<?php
namespace SabaiApps\Directories\Component\Entity\Type;

abstract class AbstractEntity implements IEntity
{
    public $data = [];
    protected $_bundleName, $_bundleType, $_properties, $_fieldValues = [], $_fieldTypes = [], $_fieldsLoaded = false, $_fromCache = false;
    
    public function __construct($bundleName, $bundleType, array $properties)
    {
        $this->_bundleName = $bundleName;
        $this->_bundleType = $bundleType;
        $this->_properties = $properties;
    }
    
    public function getBundleName()
    {
        return $this->_bundleName;
    }
    
    public function getBundleType()
    {
        return $this->_bundleType;
    }
    
    public function getProperties()
    {
        return $this->_properties;
    }
    
    public function addFieldValue($name, $value)
    {
        $this->_fieldValues[$name][] = $value;
        return $this;
    }
    
    public function getFieldValue($name)
    {
        return isset($this->_fieldValues[$name]) ? $this->_fieldValues[$name] : (isset($this->_properties[$name]) ? [$this->_properties[$name]] : null);
    }
    
    public function setFieldValue($name, $value)
    {
        $this->_fieldValues[$name] = $value;
        return $this;
    }
    
    public function getSingleFieldValue($name, $key = null, $index = 0)
    {
        if (isset($key)) return isset($this->_fieldValues[$name][$index][$key]) ? $this->_fieldValues[$name][$index][$key] : null;
        
        return isset($this->_fieldValues[$name][$index]) ? $this->_fieldValues[$name][$index] : null;
    }

    public function getFieldValues($withProperty = false)
    {
        return $withProperty ? $this->_properties + $this->_fieldValues : $this->_fieldValues;
    }
    
    public function getFieldType($fieldName)
    {
        return $this->_fieldTypes[$fieldName];
    }

    public function getFieldTypes()
    {
        return $this->_fieldTypes;
    }
    
    public function getFieldNamesByType($type)
    {
        return array_keys($this->_fieldTypes, $type);
    }
    
    public function initFields(array $values, array $types, $markLoaded = true)
    {
        $this->_fieldValues = $values;
        $this->_fieldTypes = $types;
        if ($markLoaded) $this->_fieldsLoaded = true;

        return $this;
    }
    
    public function isFieldsLoaded()
    {
        return $this->_fieldsLoaded;
    }
    
    public function __get($name)
    {
        return $this->getFieldValue($name);
    }
    
    public function __isset($name)
    {
        return isset($this->_fieldValues[$name]);
    }
    
    public function __unset($name)
    {
        unset($this->_fieldValues[$name]);
    }
    
    public function __toString()
    {
        return $this->getTitle();
    }
        
    final public function serialize()
    {
        return serialize($this->_onSerialize());
    }
    
    final public function unserialize($serialized)
    {
        $this->_onUnserialize(unserialize($serialized));
        $this->_fromCache = true;
    }
    
    protected function _onSerialize()
    {
        return array($this->_bundleName, $this->_bundleType, $this->_properties);
    }
    
    protected function _onUnserialize($values)
    {
        $this->_bundleName = $values[0];
        $this->_bundleType = $values[1];
        $this->_properties = $values[2];
    }
    
    public function isFromCache()
    {
        return $this->_fromCache;
    }
    
    public function getUniqueId($prefix = null)
    {
        if (!isset($prefix)) $prefix = 'drts-entity';
        return $prefix . '-' . $this->getType() . '-' . $this->getId();
    }
    
    public function setCustomProperty($name, $value)
    {
        $this->_properties['_' . $name] = $value;
        return $this;
    }
    
    public function getCustomProperty($name)
    {
        $name = '_' . $name;
        return isset($this->_properties[$name]) ? $this->_properties[$name] : null;
    }
    
    public function isPropertyModified($name, $value)
    {
        return array_key_exists($name, $this->_properties)
            && $value != $this->_properties[$name];
    }
    
    public function getStatus(){}

    public function getModified()
    {
        return $this->getTimestamp();
    }
        
    public function isFeatured()
    {
        return (int)$this->getSingleFieldValue('entity_featured', 'value');
    }
    
    public function isOnParentPage()
    {
        return isset($GLOBALS['drts_entity']) && $this->getParentId() === $GLOBALS['drts_entity']->getId();
    }
}
