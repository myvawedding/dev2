<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Component\Entity;

interface IField
{
    public function searchFieldInfo($key = null);
    public function searchFieldSupports(Entity\Model\Bundle $bundle);
    public function searchFieldSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);  
    public function searchFieldForm(Entity\Model\Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = []);
    public function searchFieldIsSearchable(Entity\Model\Bundle $bundle, array $settings, &$value, array $requests = null);
    public function searchFieldSearch(Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $settings, $value, array &$sorts);
    public function searchFieldLabel(Entity\Model\Bundle $bundle, array $settings, $value);
}