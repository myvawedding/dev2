<?php
namespace SabaiApps\Framework\Criteria;

abstract class AbstractStringCriteria extends AbstractCriteria
{
    private $_field, $_string;

    public function __construct($field, $string)
    {
        $this->_field = $field;
        $this->_string = strval($string);
    }

    public function getField()
    {
        return $this->_field;
    }

    public function getString()
    {
        return $this->_string;
    }
}
