<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class PlanHelper
{
    public function help(Application $application, $entityOrBundle, $planId = false)
    {
        if (!$bundle = $application->Entity_Bundle($entityOrBundle)) {
            throw new Exception\RuntimeException('Invalid bundle.');
        }
        if (empty($bundle->info['payment_enable'])) return;
        
        $plan = null;
        if ($entityOrBundle instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            $entity = $entityOrBundle;
            $application->Entity_LoadFields($entity);
            if ($plan_id = $entity->getSingleFieldValue('payment_plan', 'plan_id')) {
                if ($planId) {
                    $plan = $plan_id;
                } else {
                    if ($_plan = $this->_getPlan($application, $entity->getBundleName(), $plan_id)) {
                        $plan = $_plan;
                    }
                }
            }
        } else {
            if (!empty($planId)) {
                if (!$planId instanceof \SabaiApps\Directories\Component\Payment\IPlan) {
                    if ($_plan = $this->_getPlan($application, $bundle->name, $planId)) {
                        $plan = $_plan;
                    }
                } else {
                    $plan = $planId;
                }
                if ($plan
                    && $plan->paymentPlanBundleName() !== $bundle->name
                ) {
                    $plan = null;
                }
            }
        }

        return $plan;
    }
    
    protected function _getPlan(Application $application, $bundleName, $planId)
    {
        $plans = $application->Payment_Plans($bundleName, null, false);
        return isset($plans[$planId]) ? $plans[$planId] : false;
    }
    
    public function features(Application $application, $entityOrBundle)
    {
        if ($entityOrBundle instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            if ($plan = $this->help($application, $entityOrBundle)) {
                return array(
                    (array)$plan->paymentPlanFeatures(),
                    (array)$entityOrBundle->getSingleFieldValue('payment_plan', 'addon_features')
                );
            }
            $entityOrBundle = $entityOrBundle->getBundleName();
        }
        if (!$bundle = $application->Entity_Bundle($entityOrBundle)) {
            throw new Exception\RuntimeException('Invalid bundle: ' . $entityOrBundle);
        }
        
        return array(
            empty($bundle->info['payment_default_features']['enabled']) ? [] : $bundle->info['payment_default_features']['enabled'],
            [] // add-on features
        );
    }
    
    public function hasFeature(Application $application, Entity\Type\IEntity $entity, $featureName)
    {
        $features = $this->features($application, $entity);
        return array_key_exists($featureName, $features[0])
            || array_key_exists($featureName, $features[1]);
    }
}
