<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class PageSettingsFormHelper
{    
    public function help(Application $application, array $slugs, array $parents = [])
    {        
        $form = array(
            '#js_ready' => array('$("#__FORM_ID__ select").toggleClass("' . DRTS_BS_PREFIX . 'form-control", true);'),
            '#submit' => array(
                9 => array( // weight
                    array(array($this, 'submitForm'), array($application, $slugs, $parents)),
                ),
            ),
        );
        
        $page_slugs = $application->getPlatform()->getPageSlugs();
        $weight = 0;
        uasort($slugs, function($a, $b) { return empty($a['parent']) && empty($b['parent']) ? 0 : (empty($a['parent']) ? -1 : 1);});
        foreach (array_keys($slugs) as $slug_name) {
            $slug = $slugs[$slug_name];
            if (!empty($slug['parent'])) continue;

            $current_id = (null !== $current_slug = @$page_slugs[1][$slug['component']][$slug_name]) && isset($page_slugs[2][$current_slug]) ? $page_slugs[2][$current_slug] : null;
            $form[$slug_name]['#title'] = $slug['admin_title'];
            $form[$slug_name]['#horizontal'] = true;
            $form[$slug_name]['#weight'] = isset($slug['weight']) ? $slug['weight'] : $weight + 10;
            $form[$slug_name]['id'] = array(
                '#type' => 'item',
                '#markup' => wp_dropdown_pages(array(
                    'depth' => 1,
                    'echo' => 0,
                    'show_option_none' => __('— Select page —', 'directories'),
                    'name' => $application->Form_FieldName(array_merge($parents, array($slug_name, 'id'))),
                    'selected' => $current_id,
                )),
            );
            if (!isset($slug['required']) || $slug['required']) {
                $form[$slug_name]['#display_required'] = true;
                $form[$slug_name]['id']['#element_validate'] = [
                    [[$this, '_validatePageSettings'], [$slug_name, empty($slug['parent']), $parents]],
                ];
            }
            if (!empty($slug['wp_shortcode'])) {
                if (is_array($slug['wp_shortcode'])) {
                    $shortcode = '[' . $slug['wp_shortcode'][0] . ' ' . $application->Attr($slug['wp_shortcode'][1]) . ']';
                } else {
                    $shortcode = '[' . $slug['wp_shortcode'] . ']';
                }
                $form[$slug_name]['id']['#description'] = sprintf(
                    $application->H(__('Shortcode %s can be used to customize the content of the page.', 'directories')),
                    '<code>' . $shortcode . '</code>'
                );
                $form[$slug_name]['id']['#description_no_escape'] = true;
            }
        }
        
        return $form;
    }
    
    public function _validatePageSettings(Form\Form $form, &$value, $element, $slug, $isRoot, $parents)
    {
        if ($isRoot
            && !$form->getValue(array_merge($parents, array($slug, 'id')))
        ) {
            $form->setError(__('Please select a page', 'directories'), $element);
        }
    }
    
    public function submitForm(Form\Form $form, Application $application, $slugs, $parents)
    {
        $values = $form->getValue($parents);
        $page_slugs = $application->getPlatform()->getPageSlugs();
        
        // Save pages
        $home_url = strtok($application->getPlatform()->getHomeUrl(), '?');
        foreach ($slugs as $slug_name => $slug) {
            if (isset($page_slugs[1][$slug['component']][$slug_name])) {
                $old_slug = $page_slugs[1][$slug['component']][$slug_name];
                unset($page_slugs[1][$slug['component']][$slug_name]);
            }

            if (!empty($slug['parent'])) {
                if (!isset($page_slugs[1][$slug['component']][$slug['parent']]) // no valid parent
                    || empty($slug['bundle_type'])
                    || (!$bundle = $application->Entity_Bundle($slug['bundle_type'], $slug['component'], isset($slug['bundle_group']) ? $slug['bundle_group'] : ''))
                ) {
                    if (isset($old_slug)) {
                        unset($page_slugs[0][$old_slug], $page_slugs[5][$old_slug]);
                    }
                    continue;
                }

                // Save taxonomy or post type and slug
                $new_slug = $page_slugs[1][$slug['component']][$slug['parent']] . '/' . $slug['slug'];
                $this->saveSingle($application, $bundle, $slug_name, $new_slug, $page_slugs);
                continue;
            }

            if (empty($values[$slug_name])) continue;

            $value = $values[$slug_name];
            if (empty($value['id'])
                || (!$page = get_page($value['id']))
            ) {
                // No page, save slug info only
                if (isset($old_slug)) {
                    unset($page_slugs[0][$old_slug], $page_slugs[2][$old_slug]);
                }
                $new_slug = $slug['slug'];
                $page_slugs[1][$slug['component']][$slug_name] = $new_slug;
                continue;
            }
            $new_slug = str_replace($home_url, '', strtok(get_permalink($page->ID), '?'));
            if (strpos($new_slug, 'index.php') === 0) {
                $new_slug = substr($new_slug, strlen('index.php'));
            }
            $new_slug = trim($new_slug, '/');
            // Set post name as slug if the selected page is the front page
            if ($new_slug === '') {
                $new_slug = $page->post_name;
            }
            $page_slugs[0][$new_slug] = $new_slug;
            $page_slugs[1][$slug['component']][$slug_name] = $new_slug;
            $page_slugs[2][$new_slug] = $value['id'];
        }
        
        // Clear slugs that do not exist or no londer a sabai page slug
        $valid_slugs = [];
        if (!empty($page_slugs[1])) {
            foreach ($page_slugs[1] as $_slugs) {
                foreach ($_slugs as $slug) {
                    $valid_slugs[$slug] = $slug;
                }
            }
        }
        $page_slugs[0] = empty($page_slugs[0]) ? [] : array_intersect_key($page_slugs[0], $valid_slugs); // slugs
        $page_slugs[2] = empty($page_slugs[2]) ? [] : array_intersect_key($page_slugs[2], $valid_slugs); // ids
        if (!empty($page_slugs[5])) {
            $page_slugs[5] = array_intersect_key($page_slugs[5], $valid_slugs); // post type slugs
        }
        
        $application->getPlatform()->setPageSlugs($page_slugs);
        
        // Updrade all ISlug components since slugs have been updated
        $application->System_Component_upgradeAll(array_keys($application->System_Slugs()));
        
        // Reload main routes
        $application->getComponent('System')->reloadAllRoutes(true);
    }

    public function saveSingle(Application $application, $bundle, $slugName, $slug, array &$pageSlugs, $page = null)
    {
        // Save taxonomy or post type and slug
        $pageSlugs[5][$slug] = array(
            !empty($bundle->info['is_taxonomy']) ? 'taxonomy' : 'post_type' => $bundle->name,
            'bundle_type' => $bundle->type,
            'bundle_group' => $bundle->group,
            'component' => $bundle->component,
            'is_child' => !empty($bundle->info['parent']),
        ) + (array)@$pageSlugs[5][$slug];
        $pageSlugs[0][$slug] = $slug;
        $pageSlugs[1][$bundle->component][$slugName] = $slug;

        if (isset($page)) {
            $home_url = strtok($application->getPlatform()->getHomeUrl(), '?');
            $page_name = str_replace($home_url, '', strtok(get_permalink($page->ID), '?'));
            if (strpos($page_name, 'index.php') === 0) {
                $page_name = substr($page_name, strlen('index.php'));
            }
            $page_name = trim($page_name, '/');
            $pageSlugs[2][$slug] = $page->ID;
            $pageSlugs[5][$slug]['page_name'] = $page_name;
        }
    }
}