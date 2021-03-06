<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\View\Model\Base;

use SabaiApps\Framework\Model\Model;
use SabaiApps\Framework\Criteria\AbstractCriteria;
use SabaiApps\Framework\DB\AbstractRowset;
use SabaiApps\Framework\Model\AbstractEntity;
use SabaiApps\Framework\Model\AbstractEntityRepository;

abstract class View extends AbstractEntity
{
    public function __construct(Model $model)
    {
        parent::__construct('View', $model);
        $this->_vars = ['view_name' => null, 'view_mode' => null, 'view_data' => null, 'view_bundle_name' => null, 'view_default' => false, 'view_id' => 0, 'view_created' => 0, 'view_updated' => 0];
    }

    public function __clone()
    {
        $this->_vars = ['view_id' => 0, 'view_created' => 0, 'view_updated' => 0] + $this->_vars;
    }

    public function __toString()
    {
        return $this->__get('name');
    }

    public function __get($name)
    {
        if ($name === 'name')
            return $this->_vars['view_name'];
        elseif ($name === 'mode')
            return $this->_vars['view_mode'];
        elseif ($name === 'data')
            return $this->_vars['view_data'];
        elseif ($name === 'bundle_name')
            return $this->_vars['view_bundle_name'];
        elseif ($name === 'default')
            return $this->_vars['view_default'];
        elseif ($name === 'id')
            return $this->_vars['view_id'];
        elseif ($name === 'created')
            return $this->_vars['view_created'];
        elseif ($name === 'updated')
            return $this->_vars['view_updated'];
        else
            return $this->fetchObject($name);
    }

    public function __set($name, $value)
    {
        if ($name === 'name')
            $this->_setVar('view_name', $value);
        elseif ($name === 'mode')
            $this->_setVar('view_mode', $value);
        elseif ($name === 'data')
            $this->_setVar('view_data', $value);
        elseif ($name === 'bundle_name')
            $this->_setVar('view_bundle_name', $value);
        elseif ($name === 'default')
            $this->_setVar('view_default', $value);
        elseif ($name === 'id')
            $this->_setVar('view_id', $value);
        else
            $this->assignObject($name, $value);
    }

    protected function _initVar($name, $value)
    {
        if ($name === 'view_data')
            $this->_vars['view_data'] = @unserialize($value);
        elseif ($name === 'view_default')
            $this->_vars['view_default'] = (bool)$value;
        elseif ($name === 'view_id')
            $this->_vars['view_id'] = (int)$value;
        elseif ($name === 'view_created')
            $this->_vars['view_created'] = (int)$value;
        elseif ($name === 'view_updated')
            $this->_vars['view_updated'] = (int)$value;
        else
            $this->_vars[$name] = $value;
    }
}

abstract class ViewRepository extends AbstractEntityRepository
{
    public function __construct(Model $model)
    {
        parent::__construct('View', $model);
    }

    protected function _getCollectionByRowset(AbstractRowset $rs)
    {
        return new ViewsByRowset($rs, $this->_model->create('View'), $this->_model);
    }

    public function createCollection(array $entities = [])
    {
        return new Views($this->_model, $entities);
    }
}

class ViewsByRowset extends \SabaiApps\Framework\Model\EntityCollection\RowsetEntityCollection
{
    public function __construct(AbstractRowset $rs, View $emptyEntity, Model $model)
    {
        parent::__construct('Views', $rs, $emptyEntity, $model);
    }
}

class Views extends \SabaiApps\Framework\Model\EntityCollection\ArrayEntityCollection
{
    public function __construct(Model $model, array $entities = [])
    {
        parent::__construct($model, 'Views', $entities);
    }
}