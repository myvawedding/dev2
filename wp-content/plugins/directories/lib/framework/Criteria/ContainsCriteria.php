<?php
namespace SabaiApps\Framework\Criteria;

class ContainsCriteria extends AbstractStringCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaContains($this, $valuePassed);
    }
}
