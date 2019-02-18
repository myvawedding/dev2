<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class PlansHelper
{
    protected $_plans = [];
    
    public function help(Application $application, $bundleName, $type = null, $lang = null, $throwError = false)
    {        
        if (!isset($this->_plans[$bundleName])) {
            $this->_plans[$bundleName] = [];
            
            if ($payment_component = $application->getComponent('Payment')->getPaymentComponent($throwError)) {
                foreach ($payment_component->paymentGetPlanIds($bundleName, $lang) as $plan_id) {
                    if (!$plan = $payment_component->paymentGetPlan($plan_id)) continue;
                
                    $this->_plans[$bundleName][$plan_id] = $plan;
                }
            }
        }
        if (!isset($type)) return $this->_plans[$bundleName];
        
        $ret = [];
        settype($type, 'array');
        foreach (array_keys($this->_plans[$bundleName]) as $plan_id) {
            $plan = $this->_plans[$bundleName][$plan_id];
            if (in_array($plan->paymentPlanType(), $type)) {
                $ret[$plan_id] = $plan;
            }
        }
        return $ret;
    }
    
    public function orderableAddons(Application $application, $entityOrPlan)
    {   
        $current_features = [];
        if ($entityOrPlan instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            if (!$plan = $application->Payment_Plan($entityOrPlan)) {
                throw new Exception\RuntimeException('Could not fetch payment plan from entity');
            }
            $current_features = (array)$entityOrPlan->getSingleFieldValue('payment_plan', 'addon_features');
        } elseif ($entityOrPlan instanceof \SabaiApps\Directories\Component\Payment\IPlan) {
            $plan = $entityOrPlan;
        } else {
            throw new Exception\InvalidArgumentException('Invalid 2nd argument for ' . __CLASS__ . '::' . __METHOD__);
        }
        $current_features += $plan->paymentPlanFeatures();
        
        $ret = [];
        if ($plans = $this->help($application, $plan->paymentPlanBundleName(), 'addon')) {
            foreach ($plans as $plan_id => $addon_plan) {
                foreach ((array)$addon_plan->paymentPlanFeatures() as $feature_name => $feature_settings) {                
                    if (($feature = $application->Payment_Features_impl($feature_name, true))
                        && $feature instanceof \SabaiApps\Directories\Component\Payment\Feature\IAddonFeature                    
                        && $feature->paymentAddonFeatureIsOrderable($current_features)
                    ) {
                        $ret[$plan_id] = $addon_plan;
                        continue 2; // the plan is orderable, so skip other features
                    }
                }
            }
        }
        
        return $ret;
    }
    
    public function form(Application $application, $entityOrBundleName, $type = 'base', $excludeCurrent = false, $name = 'plan', $horizontal = true)
    {
        $plan_descriptions = $plans_disabled = [];
        $current_plan_id = null;
        if (!is_array($type)
            && $type === 'addon'
        ) {
            try {
                if (!$entityOrBundleName instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
                    throw new Exception\InvalidArgumentException('Invalid 1st argument for ' . __CLASS__ . '::' . __METHOD__);
                }
                $plans = $this->orderableAddons($application, $entityOrBundleName);
            } catch (Exception\IException $e) {
                $plans = [];
                $application->logError($e);
            }
            foreach (array_keys($plans) as $plan_id) {
                $plan = $plans[$plan_id];
                $plan_descriptions[$plan_id] = $plan->paymentPlanDescription();
                $plans[$plan_id] = $application->H($plan->paymentPlanTitle()) . ' - ' . $plan->paymentPlanPrice(true);
            }
        } else {
            if ($entityOrBundleName instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
                $bundle_name = $entityOrBundleName->getBundleName();
                $current_plan_id = $entityOrBundleName->getSingleFieldValue('payment_plan', 'plan_id');
            } else {
                $bundle_name = $entityOrBundleName;
            }
            $plans = $this->help($application, $bundle_name, $type);
            foreach (array_keys($plans) as $plan_id) {                
                $plan_descriptions[$plan_id] = $plans[$plan_id]->paymentPlanDescription();
                $title = $application->H($plans[$plan_id]->paymentPlanTitle());
                if ($current_plan_id
                    && $current_plan_id === $plan_id
                ) {
                    if ($excludeCurrent) {
                        $plans_disabled[] = $plan_id;
                    }
                    $title .= '<strong> — <span class="drts-payment-select-plan-state">'
                        . $application->H(__('Current plan', 'directories-payments'))
                        . '</span></strong>';
                } else {
                    $title .= ' - ' . $plans[$plan_id]->paymentPlanPrice(true);
                }
                if ($plans[$plan_id]->paymentPlanIsFeatured()) {
                    $title = '<span class="drts-payment-select-plan-featured">' . $title . '</span>';
                }
                $plans[$plan_id] = $title;
            }
        }
        
        if (empty($plans)) {
            return array(
                '#header' => array(
                    '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                        . $application->H(__('There are currently no payment plans available.', 'directories-payments'))
                        . '</div>'
                ),
            );
        }
        
        return array($name => array(
            '#type' => 'radios',
            '#columns' => 1,
            '#title' => __('Select Plan', 'directories-payments'),
            '#required' => true,
            '#options' => $plans,
            '#options_description' => $plan_descriptions,
            '#options_disabled' => $plans_disabled,
            '#option_no_escape' => true,
            '#default_value' => $current_plan_id,
            '#default_value_auto' => empty($current_plan_id),
            '#class' => 'drts-payment-select-plan',
            '#horizontal' => $horizontal,
        ));
    }
}