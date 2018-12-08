<?php
namespace SabaiApps\Framework\Model\EntityCollection;

abstract class AbstractEntityCollectionDecorator extends AbstractEntityCollection
{
    /**
     * @var AbstractEntityCollection
     */
    protected $_collection;

    /**
     * Constructor
     *
     * @param AbstractEntityCollection $collection
     */
    public function __construct(AbstractEntityCollection $collection)
    {
        parent::__construct($collection->getModel(), $collection->getName());
        $this->_collection = $collection;
    }

    public function count()
    {
        return $this->_collection->count();
    }

    public function rewind()
    {
        $this->_collection->rewind();
    }

    public function valid()
    {
        return $this->_collection->valid();
    }

    public function next()
    {
        $this->_collection->next();
    }

    public function current()
    {
        return $this->_collection->current();
    }

    public function key()
    {
        return $this->_collection->key();
    }
    
    public function getCurrent($index)
    {
        return $this->_collection->getCurrent($index);
    }
}