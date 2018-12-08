<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class EditDirectory extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {   
        $directory = $this->_getDirectory($context);
        return array(
            '#directory' => $directory,
            'general' => array(
                '#tree' => false,
                'label' => array(
                    '#type' => 'textfield',
                    '#title' => __('Directory label', 'directories'),
                    '#description' => __('Enter a label used for administration purpose only.'),
                    '#max_length' => 255,
                    '#required' => true,
                    '#horizontal' => true,
                    '#default_value' => $directory->data['label'],
                    '#weight' => 1,
                ),
                'icon' => array(
                    '#type' => 'iconpicker',
                    '#title' => __('Directory icon', 'directories'),
                    '#iconset' => 'dashicons',
                    '#horizontal' => true,
                    '#default_value' => $directory->data['icon'],
                    '#weight' => 5,
                ),
            ),
            'settings' => array(
                '#tree' => true,
                '#weight' => 20,
            ) + $this->Directory_Types_settingsForm(
                $directory,
                (array)$directory->data['settings'],
                array('settings'),
                $this->_getSubimttedValues($context, $formStorage)
            ),
        );
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return $this->Url('/directories/' . $this->_getDirectory($context)->name);
    }
    
    protected function _saveConfig(Context $context, array $values, Form\Form $form)
    {
        // Update directory
        $directory = $this->_getDirectory($context);
        $directory->data = array('label' => $values['label'], 'icon' => $values['icon'], 'settings' => $values['settings']);
        $directory->commit();

        // Update bundles
        $bundles = [];
        $directory_type = $this->Directory_Types_impl($directory->type);
        foreach ($directory_type->directoryInfo('content_types') as $content_type) {
            if (!$content_type_info = $this->_application->Filter(
                'directory_content_type_info',
                $directory_type->directoryContentTypeInfo($content_type),
                array($content_type, empty($values['settings'][$content_type]) ? [] : $values['settings'][$content_type])
            )) continue;
            
            $bundle_type = $directory->type . '__' . $content_type;
            // Convert content type info to bundle type info
            $bundles[$bundle_type] = $this->Directory_Types_entityBundleTypeInfo($directory->type, $content_type_info);
        }
        $this->getComponent('Entity')->updateEntityBundles('Directory', $bundles, $directory->name);
        
        $this->Action('directory_admin_directory_edited', array($directory, $values));
    }
    
    protected function _getDirectory(Context $context)
    {
        return $context->directory;
    }
}