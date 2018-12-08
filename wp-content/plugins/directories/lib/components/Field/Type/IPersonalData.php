<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IPersonalData
{
    public function fieldPersonalDataExport(IField $field, Entity\Type\IEntity $entity);
    public function fieldPersonalDataErase(IField $field, Entity\Type\IEntity $entity);
}
