<?php
namespace SabaiApps\Directories\Component\Payment\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment\PaymentComponent;
use SabaiApps\Directories\Component\Payment\Model\FeatureGroup;
use SabaiApps\Directories\Component\Payment\IPlan;
use SabaiApps\Directories\Exception;

class FeaturesHelper
{
    private $_impls = [];

    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$features = $application->getPlatform()->getCache('payment_features'))
        ) {
            $features = [];
            foreach ($application->InstalledComponentsByInterface('Payment\IFeatures') as $component_name) {
                if (!$application->isComponentLoaded($component_name)
                    || (!$feature_names = $application->getComponent($component_name)->paymentGetFeatureNames())
                ) continue;

                foreach ($feature_names as $feature_name) {
                    if ($feature = $application->getComponent($component_name)->paymentGetFeature($feature_name)) {
                        if (!$weight = $feature->paymentFeatureInfo('weight')) {
                            $weight = 99;
                        }
                        $features[$weight][$feature_name] = $component_name;
                    }
                }
            }
            if (!empty($features)) {
                ksort($features);
                $_features = [];
                foreach (array_keys($features) as $weight) {
                    $_features += $features[$weight];
                }
                $features = $_features;
            }
            $application->getPlatform()->setCache($features, 'payment_features');
        }

        return $features;
    }

    /**
     * Gets an implementation of IFeature interface for a given feature name
     * @param Application $application
     * @param string $featureName
     * @return SabaiApps\Directories\Component\Payment\Feature\IFeature
     */
    public function impl(Application $application, $featureName, $returnFalse = false)
    {
        if (!isset($this->_impls[$featureName])) {
            if ((!$features = $this->help($application))
                || !isset($features[$featureName])
                || !$application->isComponentLoaded($features[$featureName])
            ) {
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid feature: %s', $featureName));
            }
            $this->_impls[$featureName] = $application->getComponent($features[$featureName])->paymentGetFeature($featureName);
        }

        return $this->_impls[$featureName];
    }

    public function form(Application $application, Entity\Model\Bundle $bundle, $type = 'base', array $settings = [], array $parents = [])
    {
        $form = array(
            '#tree' => true,
            '#element_validate' => array(
                array(array($this, '_validateForm'), array($application, $bundle, $type)),
            ),
        );
        $method = $type === 'addon' ? 'paymentAddonFeatureSettingsForm' : 'paymentFeatureSettingsForm';
        foreach (array_keys($this->help($application)) as $feature_name) {
            if (!$feature = $this->impl($application, $feature_name, true)) continue;

            if ($type === 'addon') {
                if (!$feature instanceof \SabaiApps\Directories\Component\Payment\Feature\IAddonFeature
                    || !$feature->paymentAddonFeatureSupports($bundle)
                ) continue;
            } else {
                if (!$feature->paymentFeatureSupports($bundle, $type)) continue;
            }

            $feature_info = $feature->paymentFeatureInfo();
            $feature_settings = isset($settings[$feature_name]) ? $settings[$feature_name] : [];
            if (false === $feature_default_settings = $feature->paymentFeatureSettings($bundle, $type)) {
                $feature_default_settings = isset($feature_info['default_settings']) ? (array)$feature_info['default_settings'] : [];
            }
            $params = [$bundle, $feature_settings + $feature_default_settings];
            if ($type !== 'addon') {
                $params[] = $type;
            }
            $params[] = array_merge($parents, array($feature_name));
            $feature_settings_form = call_user_func_array([$feature, $method], $params);
            if (!$feature_settings_form) continue;

            $form[$feature_name] = $feature_settings_form;
            $form[$feature_name]['#weight'] = isset($feature_info['weight']) ? $feature_info['weight'] : 10;
            $form[$feature_name]['#title'] = $feature_info['label'];
            $form[$feature_name]['#class'] = 'drts-form-label-lg';
        }
        return $form;
    }

    public function _validateForm($form, &$value, $element, Application $application, Entity\Model\Bundle $bundle, $type)
    {
        // Extract settings for both features enabled and disabled as separate arrays
        $enabled = $type === 'addon' ? [] : ['payment_plan' => []];
        $disabled = [];
        foreach ($value as $feature_name => $feature_settings) {
            if (!$feature = $application->Payment_Features_impl($feature_name, true)) continue;

            if ($type === 'addon') {
                if (!$feature instanceof \SabaiApps\Directories\Component\Payment\Feature\IAddonFeature) continue;

                $_feature_settings = $feature->paymentAddonFeatureIsEnabled($bundle, $feature_settings);
            } else {
                $_feature_settings = $feature->paymentFeatureIsEnabled($bundle, $feature_settings);
            }
            if (false !== $_feature_settings) {
                $enabled[$feature_name] = is_array($_feature_settings) ? $_feature_settings : $feature_settings;
            } else {
                $disabled[$feature_name] = $feature_settings;
            }
        }
        $value = array('enabled' => $enabled, 'disabled' => $disabled);
    }

    public function create(Application $application, Entity\Type\IEntity $entity, IPlan $plan, $orderId = null)
    {
        $application->Entity_LoadFields($entity);

        $model = $application->getModel(null, 'Payment');

        // Create feature group
        $feature_group = $model->create('FeatureGroup')->markNew();
        $feature_group->bundle_name = $entity->getBundleName();
        $feature_group->order_id = $orderId;
        $feature_group->commit();

        // Add features to feature group
        $all_feature_settings = [];
        $features = $plan->paymentPlanFeatures();
        if ($plan->paymentPlanType() !== 'addon') {
            if (!isset($features['payment_plan'])) {
                $features['payment_plan'] = [];
            }
        } else {
            unset($features['payment_plan']);
        }

        foreach ($features as $feature_name => $feature_settings) {
            if (!$application->Payment_Features_impl($feature_name, true)) continue;

            $feature = $feature_group->createFeature()->markNew();
            $feature->feature_name = $feature_name;
            $feature->status = PaymentComponent::FEATURE_STATUS_PENDING;
            $all_feature_settings[$feature_name] = $feature_settings;
        }
        $model->commit();

        // Notify features have been added
        $values_to_save = [];
        foreach ($model->Feature->featuregroupId_is($feature_group->id)->fetch() as $feature) {
            $application->Payment_Features_impl($feature->feature_name)
                ->paymentFeatureOnAdded($entity, $feature, $all_feature_settings[$feature->feature_name], $plan, $values_to_save);
        }
        // Save entity and features
        if (!empty($values_to_save)) {
            $application->Entity_Save($entity, $values_to_save);
        }
        $model->commit();

        return $feature_group->reload();
    }

    public function group(Application $application, $featureGroupId = null, $orderId = null)
    {
        if (!isset($featureGroupId)) {
            if (empty($orderId)) {
                throw new Exception\InvalidArgumentException('Invalid order ID for feature group.');
            }
            if (!$featureGroup = $application->getModel('FeatureGroup', 'Payment')->orderId_is($orderId)->fetchOne()) {
                throw new Exception\RuntimeException('Failed fetching feature group by order ID: ' . $orderId);
            }
        } else {
            if (!$featureGroup = $application->getModel('FeatureGroup', 'Payment')->fetchById($featureGroupId)) {
                throw new Exception\RuntimeException('Failed fetching feature group by ID: ' . $featureGroupId);
            }
        }
        return $featureGroup;
    }

    public function apply(Application $application, Entity\Type\IEntity $entity, $featureGroupId = null, $orderId = null, $unapplyCurrent = true)
    {
        // Features are applied to claimed item (when claim approved), not to claim itself
        if ($entity->getBundleType() === 'claiming_claim') return;

        // Unapply currently applied features?
        if ($unapplyCurrent) {
            $extra_data = $entity->getSingleFieldValue('payment_plan', 'extra_data');
            if (!empty($extra_data['featuregroup_id'])) {
                try {
                    $current_feature_group = $this->group($application, $extra_data['featuregroup_id']);
                    $this->_unapplyFeatures($application, $entity, $current_feature_group, $features_updated = [], false);
                } catch (\Exception $e) {
                    $application->logError($e);
                }
            }
        }

        // Init
        $feature_group = $this->group($application, $featureGroupId, $orderId);
        $features_updated = [];

        // Apply features
        $entity = $this->_applyFeatures($application, $entity, $feature_group, $features_updated);

        // Notify
        if (!empty($features_updated)) {
            // Notify that the status of one or more features have changed
            $application->Action('payment_features_status_change', array($entity, $features_updated));
        }
    }

    public function unapply(Application $application, Entity\Type\IEntity $entity, $featureGroupId = null, $orderId = null)
    {
        // Features are unapplied to claimed item (when claim rejected), not to claim itself
        if ($entity->getBundleType() === 'claiming_claim') return;

        // Init
        $feature_group = $this->group($application, $featureGroupId, $orderId);
        $features_updated = [];

        // Unapply features
        $entity = $this->_unapplyFeatures($application, $entity, $feature_group, $features_updated);

        // Notify
        if (!empty($features_updated)) {
            // Notify that the status of one or more features have changed
            $application->Action('payment_features_status_change', array($entity, $features_updated));
        }
    }

    protected function _applyFeatures(Application $application, Entity\Type\IEntity $entity, FeatureGroup $featureGroup, array &$featuresUpdated)
    {
        $values_to_save = [
            'payment_plan' => [
                'addon_features' => (array)$entity->getSingleFieldValue('payment_plan', 'addon_features'),
            ],
        ];

        // Apply features in feature group
        $features = $featureGroup->Features->getArray(null, 'feature_name');
        foreach (array_keys($features) as $feature_name) {
            if ((!$ifeature = $application->Payment_Features_impl($feature_name, true))
                || $features[$feature_name]->status === PaymentComponent::FEATURE_STATUS_APPLIED
            ) continue;

            switch ($features[$feature_name]->status) {
                case PaymentComponent::FEATURE_STATUS_UNAPPLIED:
                case PaymentComponent::FEATURE_STATUS_PENDING:
                    if (false !== $ifeature->paymentFeatureApply($entity, $features[$feature_name], $values_to_save)) {
                        if ($features[$feature_name]->status === PaymentComponent::FEATURE_STATUS_PENDING) {
                            $features[$feature_name]->addLog(__('Feature applied.', 'directories-payments'));
                        } else {
                            $features[$feature_name]->addLog(__('Feature re-applied.', 'directories-payments'));
                        }
                        $features[$feature_name]->status = PaymentComponent::FEATURE_STATUS_APPLIED;
                        $featuresUpdated[] = $features[$feature_name];
                    } else {
                        $features[$feature_name]->addLog(__('Failed applying feature.', 'directories-payments'), true);
                    }
                    continue;
                default:
                    continue;
            }
        }

        // Save entity and feature logs
        if (!empty($values_to_save)) {
            $entity = $application->Entity_Save($entity, $values_to_save);
        }
        $application->getModel(null, 'Payment')->commit();

        return $entity;
    }

    protected function _unapplyFeatures(Application $application, Entity\Type\IEntity $entity, FeatureGroup $featureGroup, array &$featuresUpdated, $removePlan = true)
    {
        $values_to_save = [
            'payment_plan' => [
                'addon_features' => (array)$entity->getSingleFieldValue('payment_plan', 'addon_features'),
            ],
        ];

        // Unapply each feature in feature group
        $features = $featureGroup->Features->getArray(null, 'feature_name');
        foreach (array_keys($features) as $feature_name) {
            if (!$removePlan
                && $feature_name === 'payment_plan'
            ) continue;

            if (!$ifeature = $application->Payment_Features_impl($feature_name, true)) continue;

            switch ($features[$feature_name]->status) {
                case PaymentComponent::FEATURE_STATUS_APPLIED:
                    if (false !== $ifeature->paymentFeatureUnapply($entity, $features[$feature_name], $values_to_save)) {
                        $features[$feature_name]->status = PaymentComponent::FEATURE_STATUS_UNAPPLIED;
                        $features[$feature_name]->addLog(__('Feature unapplied.', 'directories-payments'));
                        $featuresUpdated[] = $features[$feature_name];
                    } else {
                        $features[$feature_name]->addLog(__('Failed unapplying feature.', 'directories-payments'), true);
                    }
                    continue;
                case PaymentComponent::FEATURE_STATUS_PENDING:
                    $features[$feature_name]->status = PaymentComponent::FEATURE_STATUS_UNAPPLIED;
                    $featuresUpdated[] = $features[$feature_name];
                    continue;
                default:
                    continue;
            }
        }

        // Save entity and feature logs
        if (!empty($values_to_save)) {
            $entity = $application->Entity_Save($entity, $values_to_save);
        }
        $application->getModel(null, 'Payment')->commit();

        return $entity;
    }
    
    public function render(Application $application, array $features, Entity\Model\Bundle $bundle)
    {
        $ret = [];
        foreach ($features as $feature_name => $feature_settings){
            if ((!$feature = $application->Payment_Features_impl($feature_name, true))
                || !$feature->paymentFeatureIsEnabled($bundle, $feature_settings)
                || (!$rendered = $feature->paymentFeatureRender($bundle, $feature_settings))
            ) continue;
                        
            foreach ($rendered as $_rendered) {
                $html = $_rendered['html'];
                if (isset($_rendered['icon'])) {
                    $html = '<i class="fa-fw ' . $_rendered['icon'] . '"></i> ' . $html;
                }
                $ret[] = $html;
            }
        }
        
        if (!empty($ret)) {
            // Load default CSS files
            $application->getPlatform()->loadDefaultAssets(false, true);
        }
        
        return $ret;
    }
}