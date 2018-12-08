<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

trait ConditionableStringTrait
{
    public function fieldConditionableInfo(IField $field)
    {
        return [
            '' => [
                'compare' => ['value', '!value', '^value', '$value', '*value', 'empty', 'filled'],
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case '^value':
            case '$value':
            case '*value':
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
                return;
        }
    }
}