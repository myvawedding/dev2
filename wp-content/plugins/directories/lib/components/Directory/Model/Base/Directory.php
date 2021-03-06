<?php
/* This file has been auto-generated. Do not edit this file directly. */
namespace SabaiApps\Directories\Component\Directory\Model\Base;

use SabaiApps\Framework\Model\Model;
use SabaiApps\Framework\Criteria\AbstractCriteria;
use SabaiApps\Framework\DB\AbstractRowset;
use SabaiApps\Framework\Model\AbstractEntity;
use SabaiApps\Framework\Model\AbstractEntityRepository;

abstract class Directory extends AbstractEntity
{
    public function __construct(Model $model)
    {
        parent::__construct('Directory', $model);
        $this->_vars = ['id' => null, 'directory_name' => null, 'directory_type' => null, 'directory_data' => null, 'directory_created' => 0, 'directory_updated' => 0];
    }

    public function __clone()
    {
        $this->_vars = ['id' => null, 'directory_name' => null, 'directory_created' => 0, 'directory_updated' => 0] + $this->_vars;
    }

    public function __toString()
    {
        return $this->__get('name');
    }

    public function initVars(array $arr)
    {
        parent::initVars($arr);
        $this->_vars['id'] = $this->_vars['directory_name'];
    }

    public function __get($name)
    {
        if ($name === 'id')
            return $this->_vars['id'];
        elseif ($name === 'name')
            return $this->_vars['directory_name'];
        elseif ($name === 'type')
            return $this->_vars['directory_type'];
        elseif ($name === 'data')
            return $this->_vars['directory_data'];
        elseif ($name === 'created')
            return $this->_vars['directory_created'];
        elseif ($name === 'updated')
            return $this->_vars['directory_updated'];
        else
            return $this->fetchObject($name);
    }

    public function __set($name, $value)
    {
        if ($name === 'id')
            $this->_setVar('id', $value);
        elseif ($name === 'name')
            $this->_setVar('directory_name', $value);
        elseif ($name === 'type')
            $this->_setVar('directory_type', $value);
        elseif ($name === 'data')
            $this->_setVar('directory_data', $value);
        else
            $this->assignObject($name, $value);
    }

    protected function _initVar($name, $value)
    {
        if ($name === 'directory_data')
            $this->_vars['directory_data'] = @unserialize($value);
        elseif ($name === 'directory_created')
            $this->_vars['directory_created'] = (int)$value;
        elseif ($name === 'directory_updated')
            $this->_vars['directory_updated'] = (int)$value;
        else
            $this->_vars[$name] = $value;
    }
}

abstract class DirectoryRepository extends AbstractEntityRepository
{
    public function __construct(Model $model)
    {
        parent::__construct('Directory', $model);
    }

    protected function _getCollectionByRowset(AbstractRowset $rs)
    {
        return new DirectoriesByRowset($rs, $this->_model->create('Directory'), $this->_model);
    }

    public function createCollection(array $entities = [])
    {
        return new Directories($this->_model, $entities);
    }
}

class DirectoriesByRowset extends \SabaiApps\Framework\Model\EntityCollection\RowsetEntityCollection
{
    public function __construct(AbstractRowset $rs, Directory $emptyEntity, Model $model)
    {
        parent::__construct('Directories', $rs, $emptyEntity, $model);
    }
}

class Directories extends \SabaiApps\Framework\Model\EntityCollection\ArrayEntityCollection
{
    public function __construct(Model $model, array $entities = [])
    {
        parent::__construct($model, 'Directories', $entities);
    }
}