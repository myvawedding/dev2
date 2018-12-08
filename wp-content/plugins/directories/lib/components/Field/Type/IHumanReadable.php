<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IHumanReadable
{
    public function fieldHumanReadableText(IField $field, Entity\Type\IEntity $entity, $separator = null, $key = null);
}