<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

interface ISchemable
{
    public function fieldSchemaProperties();
    public function fieldSchemaRenderProperty(IField $field, $property, Entity\Type\IEntity $entity);
}