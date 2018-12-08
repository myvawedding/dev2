<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IOpenGraph
{
    public function fieldOpenGraphProperties();
    public function fieldOpenGraphRenderProperty(IField $field, $property, Entity\Type\IEntity $entity);
}