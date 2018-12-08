<?php
namespace SabaiApps\Framework\Criteria;

class EndsWithCriteria extends AbstractStringCriteria
{
    /**
     * Accepts a Visitor object
     *
     * @param IVisitor $visitor
     * @param mixed $valuePassed
     */
    public function acceptVisitor(IVisitor $visitor, &$valuePassed)
    {
        return $visitor->visitCriteriaEndsWith($this, $valuePassed);
    }
}
