<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IEmail
{
    public function fieldEmailAddress(IField $field, Entity\Type\IEntity $entity);
}