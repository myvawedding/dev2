<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Display;

class AdminElementHelper
{
    public function create(Application $application, $bundle, Display\Model\Display $display, $name, $parentId = 0, array $data = null)
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) {
            throw new Exception\RuntimeException('Invalid bundle');
        }
        
        $element_impl = $application->Display_Elements_impl($bundle, $name);
        $info = $element_impl->displayElementInfo($bundle);
        $element = $application->Display_Create_element($bundle, $display, array(
            'name' => $name,
            'parent_id' => $parentId,
            'data' => $data,
        ));
        $element_types = $application->Display_Elements_types($bundle);
        
        $ret = array(
            'id' => $element->id,
            'type' => $info['type'],
            'name' => $element->name,
            'parent_id' => $element->parent_id,
            'display_id' => $element->display_id,
            'title' => $title = $element_impl->displayElementAdminTitle($bundle, $element->data),
            'label' => $info['label'],
            'attr' => $element_impl->displayElementAdminAttr($bundle, $element->data['settings']),
            'system' => $element->system,
            'icon' => isset($info['icon']) ? $info['icon'] : null,
            'dimmed' => $element_impl->displayElementIsDimmed($bundle, $element->data['settings'])
                || (!empty($element->data['visibility']['globalize']) && !empty($element->data['visibility']['globalize_remove'])),
            'data' => $this->getDataArray(
                $application,
                $bundle->name,
                $element->element_id,
                $element->name,
                $element_types[$info['type']],
                $info['label'],
                $title,
                (array)$element_impl->displayElementReadableInfo($bundle, $element),
                (array)$element->data['advanced']
            ),
            'containable' => !empty($info['containable']),
            'sortable_connect' => true,
        );

        // Auto-generate child elements
        if ($ret['containable']) {
            $ret['children'] = [];
            if (!empty($info['child_element_name'])) {
                if (!empty($info['child_element_create'])) {
                    if (is_callable(array($element_impl, 'displayElementCreateChildren'))) {
                        if ($children = $element_impl->displayElementCreateChildren($bundle, $display, $element->data['settings'], $element->id)) {
                            $ret['children'] = $children;
                        }
                    } else {
                        for ($i = 0; $i < $info['child_element_create']; $i++) {
                            if ($child = $application->Display_AdminElement_create($bundle, $display, $info['child_element_name'], $element->id)) {
                                $ret['children'][] = $child;
                            }
                        }
                    }
                }
                $ret += array(
                    'child_element_name' => @$info['child_element_name'],
                );
                $ret['sortable_connect'] = false;
            } elseif (!empty($info['child_element_type'])) {
                $ret += array(
                    'child_element_type' => @$info['child_element_type'],
                );
            }
            $ret['add_child_label'] = isset($info['add_child_label']) ? $info['add_child_label'] : __('Add Element', 'directories');
        }

        return $ret;
    }
    
    public function update(Application $application, $bundle, Display\Model\Element $element, array $values)
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) {
            throw new Exception\RuntimeException('Invalid bundle');
        }
        
        // Allow element implementation class to modify settings before update
        $element_impl = $application->Display_Elements_impl($bundle, $element->name);
        $settings = isset($values['general']['settings']) ? $values['general']['settings'] : [];
        if (isset($values['settings'])) $settings += $values['settings'];
        $settings += (array)$element_impl->displayElementInfo($bundle, 'default_settings');
        $data = array(
            'settings' => $settings,
            'heading' => isset($values['heading']) ? $values['heading'] : null,
            'advanced' => isset($values['advanced']) ? $values['advanced'] : null,
            'visibility' => isset($values['visibility']) ? $values['visibility'] : null,
        );
        $element_impl->displayElementOnUpdate($bundle, $data, $element->weight);
        $element->data = $data;
        $element->commit();
        $element_impl->displayElementOnSaved($bundle, $element);

        // Clear element cache
        $application->Display_Render_clearElementCache($bundle, $element->id);
        
        $element_types = $application->Display_Elements_types($bundle);
        
        return array(
            'id' => $element->id,
            'type' => $type = $element_impl->displayElementInfo($bundle, 'type'),
            'name' => $element->name,
            'parent_id' => $element->parent_id,
            'display_id' => $element->display_id,
            'title' => $title = $element_impl->displayElementAdminTitle($bundle, $element->data),
            'label' => $label = $element_impl->displayElementInfo($bundle, 'label'),
            'attr' => $element_impl->displayElementAdminAttr($bundle, $element->data['settings']),
            'system' => $element->system,
            'icon' => $element_impl->displayElementInfo($bundle, 'icon'),
            'dimmed' => $element_impl->displayElementIsDimmed($bundle, $element->data['settings'])
                || (!empty($element->data['visibility']['globalize']) && !empty($element->data['visibility']['globalize_remove'])),
            'data' => $this->getDataArray(
                $application,
                $bundle->name,
                $element->element_id,
                $element->name,
                $element_types[$type],
                $label,
                $title,
                (array)$element_impl->displayElementReadableInfo($bundle, $element),
                (array)$element->data['advanced']
            ),
        );
    }
    
    public function delete(Application $application, $bundle, $elementId, $notify = true)
    {
        if (!$bundle = $application->Entity_Bundle($bundle)) {
            throw new Exception\RuntimeException('Invalid bundle');
        }
        
        if (!$element = $application->getModel('Element', 'Display')->fetchById($elementId)) {
            $application->logWarning('Trying to delete a non-existent display element. Element ID: ' . $elementId);
            return;
        }
        
        if ($notify) {
            // Fetch settings to be passed when notifying
            $element_impl = $application->Display_Elements_impl($bundle, $element->name);
            $settings = (array)@$element->data['settings'] + (array)$element_impl->displayElementInfo($bundle, 'default_settings');
            // Delete element
            $element->markRemoved()->commit();
            // Notify
            $element_impl->displayElementOnRemoved($bundle, $settings);
        } else {
            // Delete element
            $element->markRemoved()->commit();
        }
    }
    
    public function getDataArray(Application $application, $bundleName, $id, $name, $type, $label, $title, array $info = [], array $advanced = [])
    {
        $data = [
            'general' => [
                'label' => __('General', 'directories'),
                'value' => [
                    'id' => [
                        'label' => __('Element ID', 'directories'),
                        'value' => $name . '-' . (empty($id) ? 1 : $id),
                    ],
                    'type' => [
                        'label' => __('Element type', 'directories'),
                        'value' => $label . ' (' . $type . ')',
                    ],
                ],
            ],
            'settings' => [
                'label' => __('Settings', 'directories'),
                'value' => [],
            ],
            'css' => [
                'label' => __('CSS', 'directories'),
                'value' => [
                    'class' => [
                        'label' => __('CSS class', 'directories'),
                        'value' => '<code>.drts-display-element-' . $id . '</code>',
                        'is_html' => true,
                    ],
                ],
            ],
        ];
        if (strlen($title)) {
            $data['general']['value']['title'] = [
                'label' => __('Label', 'directories'),
                'value' => $title,
                'is_html' => true,
            ];
        }
        if (isset($advanced['css_class']) && strlen($advanced['css_class'])) {
            $data['css']['value']['class_custom'] = [
                'label' => __('CSS class', 'directories'),
                'value' => '<code>.' . $application->H($advanced['css_class']) . '</code>',
                'is_html' => true,
            ];
        }
        if (isset($advanced['css_id']) && strlen($advanced['css_id'])) {
            $data['css']['value']['id'] = [
                'label' => __('CSS ID', 'directories'),
                'value' => '<code>.' . $application->H($advanced['css_id']) . '</code>',
                'is_html' => true,
            ];
        }
        if (!empty($advanced['cache'])) {
            if ($advanced['cache'] >= 86400) {
                $value = sprintf(_n('%d day', '%d days', $day = $advanced['cache'] / 86400, 'directories'), $day);
            } elseif ($advanced['cache'] >= 3600) {
                $value = sprintf(_n('%d hour', '%d hours', $hour = $advanced['cache'] / 3600, 'directories'), $hour);
            } else {
                $value = sprintf(_n('%d minute', '%d minutes', $min = $advanced['cache'] / 60, 'directories'), $min);
            }
            $data['settings']['value'] += [
                'cache' => [
                    'label' => __('Cache output', 'directories'),
                    'value' => $value,
                ],
            ];
        }
        if (!empty($info)) {
            $data = array_replace_recursive($data, $info);
        }
        return $application->Filter('display_admin_element_data', $data, [$bundleName, $id, $name]);
    }
}