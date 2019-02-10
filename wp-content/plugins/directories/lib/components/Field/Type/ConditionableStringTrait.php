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

    public function fieldConditionableMatch(IField $field, array $rule, array $values = null)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if ($input == $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            continue 2;
                        }
                    }
                    // One of rule values did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All matched or did not match.
                return true;
            case '^value':
            case '$value':
            case '*value':
                if (empty($values)) return false;

                foreach ($values as $input) {
                    foreach ((array)$rule['value'] as $rule_value) {
                        if (false === $pos = strpos($input, $rule_value)) return false;

                        if ($rule['type'] === '^value') {
                            if ($pos !== 0) return false;
                        } elseif ($rule['type'] === '$value') {
                            if (substr($input, $pos) !== $rule_value) return false;
                        }
                    }
                }
                return true;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }
}