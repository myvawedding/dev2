<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity;

interface IPersonalDataIdentifier
{
    public function fieldPersonalDataQuery(Query $query, $fieldName, $email, $userId);
    public function fieldPersonalDataAnonymize(IField $field, Entity\Type\IEntity $entity);
}
