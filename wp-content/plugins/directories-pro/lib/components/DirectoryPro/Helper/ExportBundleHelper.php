<?php
namespace SabaiApps\Directories\Component\DirectoryPro\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;

class ExportBundleHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, $makeTranslatable = false, $domain = null)
    {  
        $ret = array(
            'info' => ['title' => 'Info', 'data' => $bundle->info, 'placeholders' => []],
            'fields' => ['title' => 'Fields', 'data' => [], 'placeholders' => []],
            'displays' => ['title' => 'Displays', 'data' => [], 'placeholders' => []],
            'views' => ['title' => 'Views', 'data' => [], 'placeholders' => []],
        );
        
        if ($makeTranslatable
            && !isset($domain)
        ) {
            if (($component = $application->Entity_BundleTypeInfo($bundle, 'component'))
                && $application->isComponentLoaded($component)
            ) {        
                $domain = $application->getComponent($component)->getPackage();
            } else {
                $domain = 'directories-pro';
            }
        }
        
        // Info
        if ($makeTranslatable) {
            foreach (['label', 'label_singular', 'label_add', 'label_all', 'label_select',
                'label_count', 'label_count2', 'label_page', 'label_search'
            ] as $data_key) {
                if (isset($ret['info']['data'][$data_key])
                    && strlen($ret['info']['data'][$data_key])
                ) {
                    $ret['info']['data'][$data_key] = $this->_makeTranslatable($ret['info']['data'][$data_key], $domain, $ret['info']);
                }
            }
        }
            
        // Fields
        $fields = $application->Entity_Field($bundle);
        // List of fields to exclude that should be created by the system
        $exclude_field_types = [
            'entity_terms',
            'entity_featured',
            'voting_vote',
            'payment_plan',
            'payment_orders',
            'location_address',
            'frontendsubmit_guest',
            'entity_term_content_count',
            'entity_child_count',
        ];
        foreach (array_keys($fields) as $field_name) {
            $field = $fields[$field_name];
            if (!$field instanceof \SabaiApps\Directories\Component\Entity\Model\Field
                || $field->isPropertyField()
                || in_array($field->getFieldType(), $exclude_field_types)
            ) continue;
            
            $field_data = $field->getFieldData();
            
            // Make label translatable?
            if ($makeTranslatable
               && isset($field_data['label'])
               && strlen($field_data['label'])
            ) {
                $field_data['label'] = $this->_makeTranslatable($field_data['label'], $domain, $ret['fields']);
            }
                
            $ret['fields']['data'][$field_name] = array(
                'type' => $field->getFieldType(),
                'settings' => $field->getFieldSettings(),
                'realm' => strpos($field_name, 'field_') === 0 ? Entity\EntityComponent::FIELD_REALM_ALL : Entity\EntityComponent::FIELD_REALM_BUNDLE_DEFAULT,
                'data' => $field_data,
            );
        }
            
        // Displays
        $displays = $application->getModel('Display', 'Display')
            ->bundleName_is($bundle->name)
            ->fetch();
        foreach ($displays as $display) {
            if ($display->type === 'form') continue;
            
            $display_arr = array(
                'name' => $display->name,
                'type' => $display->type,
                'data' => $display->data,
            );
            $_elements = [];
            foreach ($display->Elements as $element) {
                if (!$element_impl = $application->Display_Elements_impl($bundle, $element->name, true)) continue;
                    
                try {
                    // Let element implementation modify data which will also be used when importing
                    $data = $element->data;
                    $element_impl->displayElementOnExport($bundle, $data);
                } catch (Exception\IException $e) {
                    $application->logError($e);
                    continue;
                }
                
                // Make label translatable?
                if ($makeTranslatable) {
                    foreach (['settings', 'heading'] as $data_key) {
                        if (isset($data[$data_key]['label_custom'])
                            && strlen($data[$data_key]['label_custom'])
                        ) {
                            $data[$data_key]['label_custom'] = $this->_makeTranslatable($data[$data_key]['label_custom'], $domain, $ret['displays']);
                        }
                    }
                }
                    
                $element_arr = array(
                    'id' => (int)$element->id,
                    'name' => $element->name,
                    'data' => $data,
                    'parent_id' => (int)$element->parent_id,
                    'weight' => (int)$element->weight,
                    'system' => (bool)$element->system,
                );
                if (!empty($element_arr['parent_id'])
                    && !isset($display_arr['elements'][$element_arr['parent_id']]) // parent element not yet added
                ) {
                    // wait until parent element is added
                    $_elements[$element_arr['parent_id']][$element_arr['id']] = $element_arr;
                } else {
                    $display_arr['elements'][$element_arr['id']] = $element_arr;
                    if (isset($_elements[$element_arr['id']])) {
                        $this->_addChildElements($display_arr['elements'], $element_arr['id'], $_elements);
                    }
                }
            }
            if (empty($display_arr['elements'])) continue;

            $ret['displays']['data'][$display->type][$display->name] = $display_arr;
        }
            
        // Views
        $views = $application->getModel('View', 'View')
            ->bundleName_is($bundle->name)
            ->fetch();
        foreach ($views as $view) {
            $ret['views']['data'][$view->name] = array(
                'mode' => $view->mode,
                'label' => $makeTranslatable ? $this->_makeTranslatable($view->data['label'], $domain, $ret['views']) : $view->data['label'],
                'settings' => $view->data['settings'],
                'default' => $view->default,
            );
        }
        
        return $ret;
    }
    
    protected function _makeTranslatable($string, $domain, &$container)
    {
        $placeholder = '%%%' . $string . '%%%';
        $container['placeholders']["'" . $placeholder . "'"] = sprintf('__(\'%s\', \'%s\')', str_replace("'", "\\'", $string), $domain);
        return $placeholder;
    }
    
    protected function _addChildElements(array &$elements, $parentId, array &$children)
    {
        foreach (array_keys($children[$parentId]) as $element_id) {
            $elements[$element_id] = $children[$parentId][$element_id];
            unset($children[$parentId][$element_id]);
            if (isset($children[$element_id])) {
                $this->_addChildElements($elements, $element_id, $children);
            }
        }
    }
}