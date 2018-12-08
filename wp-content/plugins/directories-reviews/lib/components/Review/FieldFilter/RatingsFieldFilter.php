<?php
namespace SabaiApps\Directories\Component\Review\FieldFilter;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Voting;

class RatingsFieldFilter extends Voting\FieldFilter\RatingFieldFilter
{
    public function fieldFilterSupports(Field\IField $field)
    {
        return $field->getFieldName() === 'review_ratings';
    }
    
    protected function _getVoteName(array $settings)
    {
        return '_all';
    }
}