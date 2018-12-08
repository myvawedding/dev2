<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;

interface IMode
{
    public function viewModeInfo($key = null);
    public function viewModeSupports(Entity\Model\Bundle $bundle);
    public function viewModeSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function viewModeNav(Entity\Model\Bundle $bundle, array $settings);
    public function viewModeOnView(Entity\Model\Bundle $bundle, Entity\Type\Query $query, Context $context);
}