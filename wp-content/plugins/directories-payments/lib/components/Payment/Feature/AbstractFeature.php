<?php
namespace SabaiApps\Directories\Component\Payment\Feature;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\Model\Feature;
use SabaiApps\Directories\Component\Payment\IPlan;

abstract class AbstractFeature implements IFeature
{
    protected $_application, $_name;
    
    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    abstract protected function _paymentFeatureInfo();
    
    public function paymentFeatureInfo($key = null)
    {
        $info = $this->_paymentFeatureInfo();
        return isset($key) ? (isset($info[$key]) ? $info[$key] : null) : $info;
    }
    
    public function paymentFeatureSettings(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return false; // use default_settings in info array
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return true;
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = []){}
        
    public function paymentFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return true;
    }
    
    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false, $isEdit = false){}
    
    public function paymentFeatureOnClaimEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity){}
    
    public function paymentFeatureOnAdded(Entity\Type\IEntity $entity, Feature $feature, array $settings, IPlan $plan, array &$values)
    {
        $feature->addMetas($settings);
    }
        
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Feature $feature, array &$values)
    {
        return true;
    }
    
    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Feature $feature, array &$values)
    {
        return true;
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings){}
        
    public function isFieldRequired($form, $parents, $dependee = 'enable')
    {
        $values = $form->getValue($parents);
        return !empty($values[$dependee]);
    }
    
    protected function _applyAddonFeature(Entity\Type\IEntity $entity, Feature $feature, array &$values)
    {
        if (!$entity->getSingleFieldValue('payment_plan', 'plan_id') // needs a plan associated in order to apply add-ons
            || $feature->FeatureGroup->plan_name // not an add-on feature
        ) return;

        $metas = $feature->getMetas();
        if (isset($values['payment_plan']['addon_features'][$this->_name])) {
            if (!empty($values['payment_plan']['addon_features'][$this->_name]['num'])) {
                if (empty($metas['num'])) {
                    $metas['num'] = 0;
                }
                $metas['num'] += $values['payment_plan']['addon_features'][$this->_name]['num'];
            }
            if (!empty($values['payment_plan']['addon_features'][$this->_name]['unlimited'])) {
                $metas['unlimited'] = true;
            } else {
                if (!empty($metas['unlimited'])) {
                    $metas['unlimited_by'] = $feature->id;
                }
            }
        }
        $values['payment_plan']['addon_features'][$this->_name] = $metas;
        
        return true;
    }

    protected function _unapplyAddonFeature(Entity\Type\IEntity $entity, Feature $feature, array &$values)
    {
        if (!$entity->getSingleFieldValue('payment_plan', 'plan_id') // needs a plan associated in order to apply add-ons
            || $feature->FeatureGroup->plan_name // not an add-on feature
        ) return;

        if (isset($values['payment_plan']['addon_features'][$this->_name])) {
            $metas = $feature->getMetas();
            if (!empty($values['payment_plan']['addon_features'][$this->_name]['num'])
                && !empty($metas['num'])
            ) {
                $values['payment_plan']['addon_features'][$this->_name]['num'] -= $metas['num'];
                if ($values['payment_plan']['addon_features'][$this->_name]['num'] < 0) {
                    unset($values['payment_plan']['addon_features'][$this->_name]['num']);
                }
            }
            if (!empty($values['payment_plan']['addon_features'][$this->_name]['unlimited'])
                && !empty($metas['unlimited'])
            ) {
                if (!empty($values['payment_plan']['addon_features'][$this->_name]['unlimited_by'])) {
                    if ($values['payment_plan']['addon_features'][$this->_name]['unlimited_by'] == $feature->id) {
                        unset($values['payment_plan']['addon_features'][$this->_name]['unlimited']);
                    }
                } else {
                    unset($values['payment_plan']['addon_features'][$this->_name]['unlimited']);
                }
            }
        }

        return true;
    }
    
    protected function _maxNumAllowedLabel($label)
    {
        return sprintf(__('Max number of %s allowed', 'directories-payments'), strtolower($label), $label);
    }
    
    protected function _additionalNumAllowedLabel($label)
    {
        return sprintf(__('Additional number of %s allowed', 'directories-payments'), strtolower($label), $label);
    }
}