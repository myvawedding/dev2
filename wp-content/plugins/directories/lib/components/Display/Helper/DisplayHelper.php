<?php
namespace SabaiApps\Directories\Component\Display\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display\Element\IElement;
use SabaiApps\Directories\Component\Display\Model\Display as DisplayModel;
use SabaiApps\Directories\Exception;

class DisplayHelper
{
    protected $_displays = []; // runtime cache
    
    public function help(Application $application, $entityOrBundleName, $displayName = 'detailed', $type = 'entity', $useCache = true, $create = false)
    {
        $bundle = $application->Entity_Bundle($entityOrBundleName);
        if ($displayName instanceof \SabaiApps\Directories\Component\Display\Model\Display) {
            $display = $displayName;
            $displayName = $displayName->name;
            $type = $displayName->type;
        } else {
            $display = null;
        }
        
        if (!$useCache
            || !isset($this->_displays[$bundle->name][$type][$displayName])
        ) {
            if ($display = $this->_getDisplay($application, $bundle, $displayName, $type, $useCache, $create, $display)) {
                $display = $application->Filter('display_display', $display, array($bundle, $type, $displayName));
            }
            $this->_displays[$bundle->name][$type][$displayName] = $display;
        }
        
        return $this->_displays[$bundle->name][$type][$displayName];
    }
    
    protected function _getDisplay(Application $application, Entity\Model\Bundle $bundle, $displayName, $type, $useCache, $create, $display = null)
    {        
        $cache_id = $this->_getCacheId($bundle->name, $type, $displayName);
        if (!$useCache ||
            (!$ret = $application->getPlatform()->getCache($cache_id))
        ) {
            if (!isset($display)) {
                if (!$display = $application->getModel('Display', 'Display')
                    ->name_is($displayName)
                    ->type_is($type)
                    ->bundleName_is($bundle->name)
                    ->fetchOne()
                ) {
                    if (!$create) return;
                        
                    $display = $application->getModel('Display', 'Display')->create()->markNew();
                    $display->name = $displayName;
                    $display->type = $type;
                    $display->bundle_name = $bundle->name;
                    $display->commit();
                }
            }

            $ret = array(
                'id' => $display->id,
                'name' => $display->name,
                'elements' => [],
                'type' => $display->type,
                'pre_render' => false,
                'bundle_name' => $display->bundle_name,
                'class' => implode(' ', $css_classes = $display->getCssClasses()),
                'css' => isset($display->data['css']) && strlen($display->data['css']) ? str_replace('%class%', '.' . $css_classes[0], $display->data['css']) : null,
                'is_amp' => $display->isAmp(),
                'label' => isset($display->data['label']) && strlen($display->data['label']) ? $display->data['label'] : null,
            );
            
            $elements = [];
            foreach ($display->Elements as $element) {
                if ((!$element_impl = $application->Display_Elements_impl($bundle, $element->name, true))
                    || !$element_impl->displayElementSupports($bundle, $display)
                ) continue;

                // Create element ID if none
                if (!$element->element_id) {
                    try {
                        $element->element_id = $application->getModel(null, 'Display')
                            ->getGateway('Element')
                            ->getElementId($element->display_id, $element->name);
                        $element->commit();
                    } catch (Exception\IException $e) {
                        $application->logError($e);
                    }
                }

                try {
                    if (!$element_data = $this->_getElementData($display, $bundle, $element, $element_impl)) continue;
                } catch (Exception\IException $e) {
                    $application->logError($e);
                    continue;
                }

                $element_data = $application->Filter('display_element_data', $element_data, array($bundle, $type, $displayName, $element->data));
                if (!$element_impl->displayElementIsEnabled($bundle, $element_data, $display)) continue;
                
                $elements[$element->parent_id][$element->id] = $element_data;
            }
            $this->_getElementTree($ret['elements'], $elements);
            
            // Cache which elements should be pre-rendered required for render
            foreach (array_keys($ret['elements']) as $id) {
                $element =& $ret['elements'][$id];
                $element_impl = $application->Display_Elements_impl($bundle, $element['name']);
                if ($element_impl->displayElementIsPreRenderable($bundle, $element, $display->type)) {
                    $ret['pre_render'] = true; // pre render display
                    $element['pre_render'] = true; // pre render element
                }
            }

            $ret = $application->Filter('display_cache_display', $ret, array($bundle, $type, $displayName));
            $application->getPlatform()->setCache($ret, $cache_id);

            // Extract and cache path for each element for easy access using element ID
            $paths = [];
            $this->_extractPaths($paths, $ret['elements']);
            $application->getPlatform()->setCache($paths, $cache_id . '_paths');
        }
        
        return $ret;
    }

    protected function _extractPaths(&$paths, $tree, array $elementIds = [])
    {
        foreach (array_keys($tree) as $id) {
            $element_ids = $elementIds;
            $element_ids[] = $id;
            $paths[$tree[$id]['element_id']] = implode('-', $element_ids);
            if (!empty($tree[$id]['children'])) {
                $this->_extractPaths($paths, $tree[$id]['children'], $element_ids);
            }
        }
    }
    
    protected function _getElementTree(&$tree, array $elements, $parentId = 0)
    {
        if (empty($elements[$parentId])) return;
        
        uasort($elements[$parentId], function ($a, $b) { return $a['weight'] < $b['weight'] ? -1 : 1; });
        foreach ($elements[$parentId] as $id => $element) {
            $tree[$id] = $element;
            $this->_getElementTree($tree[$id]['children'], $elements, $id);
        }
    }
    
    protected function _getElementData(DisplayModel $display, Entity\Model\Bundle $bundle, $element, IElement $impl)
    {
        $info = $impl->displayElementInfo($bundle);
        $element_id = $element->name . '-' . $element->element_id;

        $data = $element->data;
        if (!isset($data['settings'])
            || !is_array($data['settings'])
        ) {
            $data['settings'] = [];
        }
        $data['settings'] += $info['default_settings'];
        $classes = [
            'drts-display-element',
            'drts-display-element-' . $element_id,
        ];
        if ($inlineable = $impl->displayElementIsInlineable($bundle, $data['settings'])) {
            $classes[] = 'drts-display-element-inlineable';
        }
        if (isset($info['class'])) {
            $classes[] = $info['class'];
        }

        return array(
            'id' => $element->id,
            'element_id' => $element_id,
            '_element_id' => $element->element_id,
            'display' => $display->name,
            'name' => $element->name,
            'label' => $info['label'],
            'settings' => $data['settings'],
            'title' => $impl->displayElementAdminTitle($bundle, $data),
            'admin_attr' => $impl->displayElementAdminAttr($bundle, $data['settings']),
            'dimmed' => $impl->displayElementIsDimmed($bundle, $data['settings'])
                || (!empty($data['visibility']['globalize']) && !empty($data['visibility']['globalize_remove'])),
            'type' => $info['type'],
            'class' => implode(' ', $classes),
            'containable' => !empty($info['containable']),
            'weight' => $element->weight,
            'children' => [],
            'child_element_type' => isset($info['child_element_type']) ? $info['child_element_type'] : null,
            'child_element_name' => isset($info['child_element_name']) ? $info['child_element_name'] : null,
            'add_child_label' => isset($info['add_child_label']) ? $info['add_child_label'] : __('Add Element', 'directories'),
            'parent_element_name' => isset($info['parent_element_name']) ? $info['parent_element_name'] : null,
            'heading' => empty($data['heading']) ? [] : $data['heading'],
            'visibility' => empty($data['visibility']) ? [] : $data['visibility'],
            'advanced' => empty($data['advanced']) ? [] : $data['advanced'],
            'system' => $element->system ? true : false,
            'inlineable' => $inlineable,
            'icon' => isset($info['icon']) ? $info['icon'] : null,
            'info' => $impl->displayElementReadableInfo($bundle, $element),
        );
    }
    
    protected function _getStyle(Application $application, array $design)
    {
        $styles = [];
        if (!empty($design['add_border'])) {
            $styles['border'] = sprintf(
                '%01.1fpx %s %s',
                empty($design['border']['width']) ? 0 : $design['border']['width'],
                $design['border']['style'],
                $design['border']['color'][1]
            );        
            if (!empty($design['border']['radius'])) {
                $styles['border-radius'] = sprintf('%01.1fpx', $design['border']['radius']);
            }
        }
        
        foreach (array('margin', 'padding') as $key) {
            if (empty($design['add_' . $key])) continue;
            
            if (isset($design[$key]['top'])
                || isset($design[$key]['right'])
                || isset($design[$key]['bottom'])
                || isset($design[$key]['left'])
            ) {
                $styles[$key] = sprintf(
                    '%2$01.1f%1$s %3$01.1f%1$s %4$01.1f%1$s %5$01.1f%1$s',
                    isset($design[$key . '_unit']) && $design[$key . '_unit'] === 'em' ? 'em' : 'px',
                    $design[$key]['top'],
                    $design[$key]['right'],
                    $design[$key]['bottom'],
                    $design[$key]['left']
                );
            }
        }
        
        if (!empty($design['set_bg'])) {
            if (isset($design['bg_color']) && strlen($design['bg_color'])) {
                $styles['background-color'] = $design['bg_color'];
            }
        }
        
        if (!empty($design['align']['enable'])) {
            $align = false;
            if ($design['align']['value'] === 'center') {
                $align = 'center';
            } else {
                if ($application->getPlatform()->isRtl() ? $design['align']['value'] === 'left' : $design['align']['value'] === 'right') {
                    $align = $design['align']['value'];
                }
            }
            if ($align) {
                $styles['text-align'] = $align;
            }
        }
        
        if (!empty($design['position']['enable'])) {
            $styles['z-index'] = 1001;
            $styles['position'] = 'absolute';
            foreach (array('top', 'bottom', 'left', 'right') as $pos) {
                if (isset($design['position']['value'][$pos])
                    && strlen($design['position']['value'][$pos])
                ) {
                    $styles[$pos] = sprintf('%01.1fpx', $design['position']['value'][$pos]);
                }
            }
        }
        
        if (!empty($design['set_font'])) {
            if (!empty($design['font']['color'])) {
                $styles['color'] = $design['font']['color'];
            }
            if ($design['font']['size_unit'] === 'em') {
                if (!empty($design['font']['size_em'])) {
                    $styles['font-size'] = sprintf('%01.2fem', $design['font']['size_em']);
                }
            } else {
                if (!empty($design['font']['size_px'])) {
                    $styles['font-size'] = sprintf('%01.1fpx', $design['font']['size_px']);
                }
            }
            if (!empty($design['font']['weight'])) {
                $styles['font-weight'] = $design['font']['weight'];
            }
            if (!empty($design['font']['style'])) {
                $styles['font-style'] = $design['font']['style'];
            }
        }
        
        if (!empty($design['overflow'])) {
            $styles['overflow'] = $design['overflow'];
        }
        
        if (empty($styles)) return '';
        
        $ret = [];
        foreach ($styles as $key => $value) {
            $ret[] = $key . ':' . $application->H($value);
        }
        
        return implode(';', $ret);
    }
    
    protected function _getCacheId($bundleName, $type, $displayName)
    {
        return 'display_display_' . $bundleName . '_' . $type . '_' . $displayName;
    }
    
    public function clearCache(Application $application, $bundleName, $type = null, $displayName = null)
    {
        if ($bundleName instanceof \SabaiApps\Directories\Component\Display\Model\Display) {
            $type = $bundleName->type;
            $displayName = $bundleName->name;
            $bundleName = $bundleName->bundle_name;
        }
        $application->getPlatform()->deleteCache($cache_id = $this->_getCacheId($bundleName, $type, $displayName))
            ->deleteCache($cache_id . '_paths');
    }

    public function element(Application $application, array $display, $elementId)
    {
        if (empty($display['elements'])
            || (!$paths = $application->getPlatform()->getCache($this->_getCacheId($display['bundle_name'], $display['type'], $display['name']) . '_paths'))
            || !isset($paths[$elementId])
            || (!$path = explode('-', $paths[$elementId]))
            || (!$id = array_shift($path))
            || !isset($display['elements'][$id])
        ) return;

        $element = $display['elements'][$id];
        while ($id = array_shift($path)) {
            if (!isset($element['children'][$id])) return;

            $element = $element['children'][$id];
        }

        return $element;
    }

    public function export(Application $application, DisplayModel $display, \Closure $elementCallback = null)
    {
        if ($display->type === 'form') return;

        if (!$bundle = $application->Entity_Bundle($display->bundle_name)) return;

        $display_arr = [
            'elements' => [],
        ];
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

            $element_arr = array(
                'id' => (int)$element->id,
                'name' => $element->name,
                'data' => $data,
                'parent_id' => (int)$element->parent_id,
                'weight' => (int)$element->weight,
                'system' => (bool)$element->system,
            );
            if (isset($elementCallback)) {
                $element_arr = $elementCallback($element_arr);
            }
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
        if (empty($display_arr['elements'])) return;

        return [
            'name' => $display->name,
            'type' => $display->type,
            'data' => $display->data,
        ] + $display_arr;
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