<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IType
{
    public function fieldTypeInfo($key = null);
    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function fieldTypeSchema();
    public function fieldTypeOnSave(IField $field, array $values);
    public function fieldTypeOnLoad(IField $field, array &$values, Entity\Type\IEntity $entity);
}