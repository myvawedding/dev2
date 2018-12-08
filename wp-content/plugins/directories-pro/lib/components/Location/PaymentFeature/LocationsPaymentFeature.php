<?php
namespace SabaiApps\Directories\Component\Location\PaymentFeature;

use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity;

class LocationsPaymentFeature extends Payment\Feature\AbstractFeature implements Payment\Feature\IAddonFeature
{    
    protected function _paymentFeatureInfo()
    {
        return array(
            'label' => __('Location Settings', 'directories-pro'),
            'weight' => 7,
            'default_settings' => array(
                'unlimited' => false,
                'num' => 1,
            ),
        );
    }
    
    public function paymentFeatureSupports(Entity\Model\Bundle $bundle, $planType = 'base')
    {
        return !empty($bundle->info['location_enable']);
    }
    
    public function paymentFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, $planType = 'base', array $parents = [])
    {
        $label = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group)->getLabel();
        return array( 
            'unlimited' => array(
                '#title' => $this->_maxNumAllowedLabel($label),
                '#on_label' => __('Unlimited', 'directories-pro'),
                '#type' => 'checkbox',
                '#switch' => false,
                '#default_value' => !empty($settings['unlimited']),
                '#horizontal' => true,
            ),
            'num' => array(
                '#type' => 'slider',
                '#default_value' => isset($settings['num']) ? $settings['num'] : 1,
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 50,
                '#states' => array(
                    'invisible' => array(
                        sprintf('input[name="%s[unlimited][]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),                       
                ),
                '#horizontal' => true,
            ),
        );
    }
    
    public function paymentFeatureApply(Entity\Type\IEntity $entity, Payment\Model\Feature $feature, array &$values)
    {        
        return $this->_applyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureUnapply(Entity\Type\IEntity $entity, Payment\Model\Feature $feature, array &$values)
    {
        return $this->_unapplyAddonFeature($entity, $feature, $values);
    }

    public function paymentFeatureOnEntityForm(Entity\Model\Bundle $bundle, array $settings, array &$form, Entity\Type\IEntity $entity = null, $isAdmin = false, $isEdit = false)
    {
        if ($isAdmin && $this->_application->IsAdministrator()) return; // do not restrict for administrators
        
        if (empty($bundle->info['location_enable'])) return;
               
        if (!isset($form['location_address'][0])) return; // Field does not exist
            
        if (empty($settings[0]['unlimited'])) {
            $limit = $settings[0]['num'];
            if (!empty($settings[1]['num'])) {
                $limit += $settings[1]['num'];
            }

            if (empty($limit)) {
                // No locations allowed
                unset($form['location_address']);
                return;
            }
                
            $form['#max_num_items']['location_address'] = $limit;
            
            // Remove fields over limit
            $current_num = 0;
            foreach (array_keys($form['location_address']) as $key) {
                if (is_numeric($key)) {
                    ++$current_num;
                    if ($current_num > $limit) {
                        // over limit
                        unset($form['location_address'][$key]);
                    }
                }
            }
            // Add add more button
            $form['location_address']['_add'] = array(
                '#type' => 'addmore',
                '#next_index' => $current_num,
                '#max_num' => $limit,
                '#hidden' => $current_num >= $limit,
            );
        } else {
            $form['#max_num_items']['location_address'] = 0;
            
            $current_num = 0;
            foreach (array_keys($form['location_address']) as $key) {
                if (is_numeric($key)) {
                    ++$current_num;
                }
            }
            // Add add more button
            $form['location_address']['_add'] = array(
                '#type' => 'addmore',
                '#next_index' => $current_num,
            );
        }
    }
    
    public function paymentFeatureRender(Entity\Model\Bundle $bundle, array $settings)
    {
        if (!$location_bundle = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group)) {
            return;
        }
        if (empty($settings['unlimited'])) {
            $label = sprintf($this->_application->H($location_bundle->getLabel($settings['num'] > 1 ? 'count2' : 'count')), '<em>' . $settings['num'] . '</em>');
        } else {
            $label = sprintf($this->_application->H($location_bundle->getLabel('count2')), '<em>' . __('Unlimited', 'directories-pro') . '</em>');
        }
        return array(array(
            'icon' => $this->_application->Entity_BundleTypeInfo($location_bundle, 'icon'),
            'html' => $label,
        ));
    }
    
    public function paymentAddonFeatureSupports(Entity\Model\Bundle $bundle)
    {
        return $this->paymentFeatureSupports($bundle);
    }
    
    protected function _getAddonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [], $horizontal = true)
    {
        $label = $this->_application->Entity_Bundle('location_location', $bundle->component, $bundle->group)->getLabel();
        return array( 
            'num' => array(
                '#title' => $this->_additionalNumAllowedLabel($label),
                '#type' => 'slider',
                '#default_value' => isset($settings['num']) ? $settings['num'] : 0,
                '#integer' => true,
                '#min_value' => 0,
                '#max_value' => 20,
                '#horizontal' => $horizontal,
            ),
        );
    }
    
    public function paymentAddonFeatureSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $settings, $parents);
    }
    
    public function paymentAddonFeatureCurrentSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getAddonSettingsForm($bundle, $settings, $parents, false);
    }
        
    public function paymentAddonFeatureIsEnabled(Entity\Model\Bundle $bundle, array $settings)
    {
        return !empty($settings['num']) && intval($settings['num']) > 0;
    }
    
    public function paymentAddonFeatureIsOrderable(array $currentFeatures)
    {
        return empty($currentFeatures[$this->_name]['unlimited']);
    }
}