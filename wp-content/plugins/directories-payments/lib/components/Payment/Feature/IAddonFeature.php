<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Component\Entity;

interface IAddonFeature
{
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle);
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function paymentAddonFeatureCurrentSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []);
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings);
    public function paymentAddonFeatureIsOrderable(array $currentFeatures);
}