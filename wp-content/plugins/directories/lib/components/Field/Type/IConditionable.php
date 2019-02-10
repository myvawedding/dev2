<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;

interface IConditionable
{
    public function fieldConditionableInfo(IField $field);
    public function fieldConditionableRule(IField $field, $compare, $value = null, $name = '');
    public function fieldConditionableMatch(IField $field, array $rule, array $values = null);
}