<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface ICoordinates
{
    public function fieldLatitude(IField $field, Entity\Type\IEntity $entity);
    public function fieldLongitude(IField $field, Entity\Type\IEntity $entity);
}