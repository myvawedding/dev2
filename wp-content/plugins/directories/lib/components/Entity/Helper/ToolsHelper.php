<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\System\Progress;

class ToolsHelper
{
    public function refreshFieldCache(Application $application, Progress $progress)
    {
        $application->Entity_FieldCache_clean();
        
        foreach ($application->Entity_Bundles() as $bundle) {
            //if (!empty($bundle->info['is_taxonomy'])) continue;
            
            $paginator = $application->Entity_Query($bundle->entitytype_name)
                ->fieldIs('bundle_name', $bundle->name)
                ->sortById()
                ->paginate(50);
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Reloading field cache for %s (%d - %d)',
                    $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                $application->Entity_LoadFields($bundle->entitytype_name, $paginator->getElements(), true);
            }
        }
    }
    
    public function refreshTermCache(Application $application, Progress $progress)
    {
        $langs = $application->getPlatform()->getLanguages();
        foreach ($application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['is_taxonomy'])) continue;
            
            $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
            if ($langs) {
                foreach ($langs as $lang) {
                    $progress->set(sprintf('Reloading term cache for %s (%s)', $label, $lang));
                    $application->Entity_TaxonomyTerms($bundle->name, null, null, $lang, true);
                }
            } else {
                $progress->set(sprintf('Reloading term cache for %s', $label));
                $application->Entity_TaxonomyTerms($bundle->name, null, null, null, true);
            }
        }
    }
    
    public function recountTermPosts(Application $application, Progress $progress)
    {
        $application->Entity_TaxonomyContentBundleTypes_clear(); // clear cache
        foreach ($application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['is_taxonomy'])) continue;
                    
            $progress->set(sprintf('Recounting posts for taxonomy: %s', $bundle->getGroupLabel() . ' - ' . $bundle->getLabel()));
                    
            foreach ($application->Entity_TaxonomyContentBundleTypes($bundle->type) as $content_bundle_type) {
                if (!$content_bundle = $application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)) continue;
           
                $paginator = $application->Entity_Query($bundle->entitytype_name)
                    ->fieldIs('bundle_name', $bundle->name)
                    ->sortById()
                    ->paginate(100);
                foreach ($paginator as $page) {
                    $paginator->setCurrentPage($page);
                    $offset = $paginator->getElementOffset();
                    $progress->set(sprintf(
                        'Recounting %s for taxonomy: %s (%d - %d)',
                        $content_bundle->getLabel(),
                        $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                        $offset + 1,
                        $offset + $paginator->getElementLimit()
                    ));
                    if ($terms = $paginator->getElements()) {
                        $application->Entity_UpdateTermContentCount($bundle->name, $terms, $content_bundle);
                    }
                }
            }
        }
    }
    
    public function recountChildPosts(Application $application, Progress $progress)
    {
        foreach ($application->Entity_BundleTypes_children() as $bundle_type => $child_bundle_types) {      
            $bundle_type_info = $application->Entity_BundleTypeInfo($bundle_type);
            $published_status = $application->Entity_Status($bundle_type_info['entity_type'], 'publish');
            $paginator = $application->Entity_Query($bundle_type_info['entity_type'])
                ->fieldIs('bundle_type', $bundle_type)
                ->fieldIs('status', $published_status)
                ->paginate(100);
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Recounting child posts for %s (%d - %d)',
                    $bundle_type_info['label'],
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                if ($posts = $paginator->getElements()) {
                    $parent_ids = array_keys($posts);
                    $children_count = [];
                    // Count the total number of published child posts grouped by parent post ID and child bundle type
                    $count = $application->Entity_Query($bundle_type_info['entity_type'])
                        ->fieldIsIn('parent', $parent_ids)
                        ->fieldIs('status', $published_status)
                        ->fieldIsIn('bundle_type', $child_bundle_types)
                        ->groupByField(array('parent', 'bundle_type'))
                        ->count();
                    if (!empty($count)) {
                        foreach (array_keys($count) as $parent_id) {
                            foreach ($count[$parent_id] as $child_bundle_type => $_count) {
                                if (!empty($_count)) {
                                    $children_count[(int)$parent_id][] = array('value' => $_count, 'child_bundle_type' => $child_bundle_type);
                                }
                            }
                        }
                    }
                    foreach (array_keys($posts) as $parent_id) {
                        $application->Entity_Save(
                            $posts[$parent_id],
                            array('entity_child_count' => isset($children_count[$parent_id]) ? $children_count[$parent_id] : false)
                        );
                    }
                }
            }
        }
    }

    public function syncTerms(Application $application, Progress $progress)
    {
        foreach ($application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['taxonomies'])) continue;

            $paginator = $application->Entity_Query($bundle->entitytype_name)
                ->fieldIs('bundle_name', $bundle->name)
                ->sortById()
                ->paginate(50);
            // Skip modification check for taxonomy term fields to force update
            $extra_args = ['skip_is_modified_check' => []];
            foreach (array_keys($bundle->info['taxonomies']) as $bundle_type) {
                $extra_args['skip_is_modified_check'][$bundle_type] = true;
            }
            foreach ($paginator as $page) {
                $paginator->setCurrentPage($page);
                $offset = $paginator->getElementOffset();
                $progress->set(sprintf(
                    'Syncing terms for %s (%d - %d)',
                    $bundle->getGroupLabel() . ' - ' . $bundle->getLabel(),
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                ));
                foreach ($paginator->getElements() as $entity) {
                    $values = [];
                    foreach ($bundle->info['taxonomies'] as $bundle_type => $taxonomy) {
                        $term_ids = wp_get_object_terms($entity->getId(), $taxonomy, ['fields' => 'ids']);
                        if (!is_array($term_ids)) continue;
                
                        $values[$bundle_type] = $term_ids;
                    }
                    $application->Entity_Save($entity, $values, $extra_args);
                }
            }
        }
    }
}
