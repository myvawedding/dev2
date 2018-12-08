<?php
namespace SabaiApps\Directories\Component\Entity\Type;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception;

class FieldQuery extends Field\Query
{
    protected $_bundleName;

    public function taxonomyTermIdIn($taxonomyBundleType, array $ids, $ignoreAuto = false)
    {
        $this->fieldIsIn($taxonomyBundleType, $ids);
        // whether or not to ignore terms automatically added by the system, such as parent terms that were not selected by the user explicitly 
        if ($ignoreAuto) {
            $this->fieldIsNot($taxonomyBundleType, true, 'auto');
        }
    }
    
    public function taxonomyTermIdIs($taxonomyBundleType, $id, $ignoreAuto = true)
    {
        $this->fieldIs($taxonomyBundleType, $id);
        // whether or not to ignore terms automatically added by the system, such as parent terms that were not selected by the user explicitly 
        if (!$ignoreAuto) {
            $this->fieldIsNot($taxonomyBundleType, true, 'auto');
        }
    }
    
    public function taxonomyTermIdNotIn($taxonomyBundleType, array $ids, $ignoreAuto = true)
    {
        $this->fieldIsNotIn($taxonomyBundleType, $ids);
        // whether or not to ignore terms automatically added by the system, such as parent terms that were not selected by the user explicitly 
        if ($ignoreAuto) {
            $this->fieldIsNot($taxonomyBundleType, true, 'auto');
        }
    }
    
    public function taxonomyTermTitleContains($taxonomy, $string)
    {
        throw new Exception\RuntimeException('Call to unsupported method: ' . __METHOD__);
    }

    public function fieldIs($field, $value, $column = 'value', $alias = null, $on = null)
    {
        if ($field === 'bundle_name') {
            $this->_bundleName = $value;
        }
        return parent::fieldIs($field, $value, $column, $alias, $on);
    }

    public function fieldIsIn($field, array $values, $column = 'value', $alias = null, $on = null)
    {
        if ($field === 'bundle_name') {
            $this->_bundleName = current(array_values($values));
        }
        return parent::fieldIsIn($field, $values, $column, $alias, $on);
    }

    public function getQueriedBundleName()
    {
        return $this->_bundleName;
    }
}