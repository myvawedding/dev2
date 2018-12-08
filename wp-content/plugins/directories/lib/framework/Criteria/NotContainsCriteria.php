<?php
namespace SabaiApps\Framework\Criteria;

class NotContainsCriteria extends AbstractStringCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaNotContains($this, $valuePassed);
    }
}
