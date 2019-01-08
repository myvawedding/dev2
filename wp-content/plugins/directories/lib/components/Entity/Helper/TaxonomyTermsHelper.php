<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\Query;

class TaxonomyTermsHelper
{
    public function help(Application $application, $bundleName, $num = null, $parent = null, $lang = null, $force = false)
    {
        if (!isset($num)) {
            if (defined('DRTS_ENTITY_TAXONOMY_TERMS_CACHE_NUM')) {
                $num = DRTS_ENTITY_TAXONOMY_TERMS_CACHE_NUM;
            } else {
                $num = 1000;
            }
        }
        $num = intval($num);
        $cache_id = $this->_getCacheId($application, $bundleName, $num, $parent, $lang);
        if ($force
            || (!$list = $application->getPlatform()->getOption($cache_id))
        ) {
            $list = [];
            if (!$bundle = $application->Entity_Bundle($bundleName)) return $list;

            $sort = $application->Filter(
                'entity_terms_sort',
                ['field' => 'title', 'order' => 'ASC', 'column' => null, 'is_custom' => false],
                [$bundle]
            );
            if (empty($bundle->info['is_hierarchical'])) {
                $list = $this->_getTermList($application, $bundle, $num, $sort, null, $lang);
            } else {
                if (isset($parent)) {
                    $list = $this->_getTermList($application, $bundle, $num, $sort, $parent, $lang);
                } else {
                    $list = $this->_getHierarchicalTermList($application, $bundle, $num, $sort, $lang);
                }
            }
            $application->getPlatform()->setOption($cache_id, $list, false);
        }

        return $list;
    }

    protected function _getTermList(Application $application, $bundle, $num, array $sort, $parent = null, $lang = null)
    {
        $terms = $application->Entity_Query($bundle->entitytype_name)
            ->fieldIs('bundle_name', $bundle->name);
        $this->_sortTerms($terms, $sort);
        if (isset($parent)) {
            $terms->fieldIs('parent', $parent_id = intval($parent))->sortByField('parent');
        } else {
            $parent_id = 0;
        }
        $list = [];
        foreach ($terms->fetch($num) as $term) {
            $term_id = $term->getId();
            $list[$parent_id][$term_id] = array(
                'id' => $term_id,
                'name' => $term->getSlug(),
                'title' => $application->Entity_Title($term),
                'url' => (string)$application->Entity_PermalinkUrl($term),
                'count' => $term->getSingleFieldValue('entity_term_content_count'),
            );
            if ($icon = $application->Entity_Image($term, 'icon')) {
                $list[$parent_id][$term_id]['icon_src'] = $icon;
            } elseif ($icon = $application->Entity_Icon($term, false)) {
                $list[$parent_id][$term_id]['icon'] = $icon;
            }
            if ($color = $application->Entity_Color($term)) {
                $list[$parent_id][$term_id]['color'] = $color;
            }
        }

        return $list;
    }

    protected function _getHierarchicalTermList(Application $application, $bundle, $num, array $sort, $lang = null)
    {
        $term_parent_ids = [];
        $terms = $application->Entity_Query($bundle->entitytype_name)
            ->fieldIs('bundle_name', $bundle->name)
            ->sortByField('parent');
        $this->_sortTerms($terms, $sort);
        $filter_func = function ($value) { return $value !== null; }; // filter null value
        $list = [];
        foreach ($terms->fetch(isset($num) ? $num : 0) as $term) {
            $parent_id = (int)$term->getParentId();
            $term_id = $term->getId();
            $list[$parent_id][$term_id] = array(
                'id' => $term_id,
                'name' => $term->getSlug(),
                'title' => $application->Entity_Title($term),
                'url' => (string)$application->Entity_PermalinkUrl($term),
                'pt' => null, // parent titles
                'depth' => 0,
                'count' => $term->getSingleFieldValue('entity_term_content_count'),
            );
            if (!empty($bundle->info['entity_image'])) {
                $list[$parent_id][$term_id]['icon_src'] = $application->Entity_Image($term, 'icon', $bundle->info['entity_image']);
            } elseif ($icon = $application->Entity_Icon($term, false)) {
                $list[$parent_id][$term_id]['icon'] = $icon;
            }
            $list[$parent_id][$term_id]['color'] = $application->Entity_Color($term);
            $term_parent_ids[$term_id] = $parent_id;

            if ($parent_id && isset($term_parent_ids[$parent_id])) {
                $parent_titles = [];
                $get_parent_icon = !isset($list[$parent_id][$term_id]['icon_src']) && !isset($list[$parent_id][$term_id]['icon']);
                $get_parent_color = !isset($list[$parent_id][$term_id]['color']);
                $parent_icon = $parent_color = null;
                $_parent_id = $parent_id;
                do {
                    $parent_titles[] = $list[$term_parent_ids[$_parent_id]][$_parent_id]['title'];
                    if ($get_parent_icon
                        && !isset($parent_icon)
                        && isset($list[$term_parent_ids[$_parent_id]][$_parent_id]['icon'])
                    ) {
                        $parent_icon = $list[$term_parent_ids[$_parent_id]][$_parent_id]['icon'];
                    }
                    if ($get_parent_color
                        && !isset($parent_color)
                        && isset($list[$term_parent_ids[$_parent_id]][$_parent_id]['color'])
                    ) {
                        $parent_color = $list[$term_parent_ids[$_parent_id]][$_parent_id]['color'];
                    }
                    $_parent_id = $term_parent_ids[$_parent_id];
                } while ($_parent_id && isset($term_parent_ids[$_parent_id]));
                if (!empty($parent_titles)) {
                    $list[$parent_id][$term_id]['pt'] = array_reverse($parent_titles);
                    $list[$parent_id][$term_id]['depth'] = count($parent_titles);
                }
                if (isset($parent_icon)) {
                    $list[$parent_id][$term_id]['parent_icon'] = $parent_icon;
                }
                if (isset($parent_color)) {
                    $list[$parent_id][$term_id]['color'] = $parent_color;
                }
            }
            // Unset null values
            $list[$parent_id][$term_id] = array_filter($list[$parent_id][$term_id], $filter_func);
        }

        return $list;
    }

    protected function _sortTerms(Query $query, array $sort)
    {
        if (empty($sort['field'])) return;

        if (empty($sort['is_custom'])) {
            $query->sortByField($sort['field'], $sort['order'], $sort['column']);
        } else {
            $query->sortByCustom($sort['field'], $sort['order']);
        }
    }

    protected function _getCacheId(Application $application, $bundleName, $num, $parent = null, $lang = null)
    {
        if (!isset($lang)) {
            $lang = $application->getPlatform()->getCurrentLanguage();
        }

        return 'entity_taxonomy_terms_' . $bundleName . '_' . $lang . '_' . $num . '_' . (string)$parent;
    }

    public function clearCache(Application $application, $bundleName = null, $lang = null)
    {
        $prefix = 'entity_taxonomy_terms_';
        if (isset($bundleName)) {
            $prefix .= $bundleName . '_';
            if (isset($lang)) {
                $prefix .= $lang . '_';
            }
        }
        $application->getPlatform()->clearOptions($prefix);
    }

    public function html(Application $application, $bundleName, array $options = [], array $list = [])
    {
        $options += array(
            'parent' => 0,
            'depth' => 0,
            'content_bundle' => null,
            'hide_empty' => false,
            'hide_count' => false,
            'merge_count' => true,
            'prefix' => '',
            'init_depth' => 0,
            'limit' => null,
            'link' => false,
            'icon' => false,
            'icon_size' => '',
            'count_no_html' => false,
            'return_array' => false,
            'language' => null,
        );
        $terms = $application->Entity_TaxonomyTerms(
            $bundleName,
            $options['limit'],
            $options['depth'] === 1 ? $options['parent'] : null,
            $options['language']
        );
        if (!empty($terms[$options['parent']])) {
            $this->_listTerms($application, $terms, $list, $options, $options['parent'], $options['init_depth']);
        }

        return $list;
    }

    protected function _listTerms(Application $application, array $terms, array &$list, array $options, $parentId = 0, $depth = 0)
    {
        if (isset($options['content_bundle'])) {
            foreach (array_keys($terms[$parentId]) as $term_id) {
                $term = $terms[$parentId][$term_id];
                if ($options['hide_empty'] && empty($term['count']['_' . $options['content_bundle']])) {
                    continue;
                }
                if (!$options['hide_count']) {
                    $count_key = $options['merge_count'] ? '_' . $options['content_bundle'] : $options['content_bundle'];
                    $content_count = isset($term['count'][$count_key]) ? $term['count'][$count_key] : 0;
                } else {
                    $content_count = null;
                }
                $list[$term_id] = $this->_renderTerm($application, $term, $options, $depth, $content_count);
                // Add sub-lists if any child terms
                if (!empty($terms[$term_id])
                    && (empty($options['depth']) || $depth + 1 < $options['depth'])
                ) {
                    $this->_listTerms($application, $terms, $list, $options, $term_id, $depth + 1);
                }
            }
        } else {
            foreach (array_keys($terms[$parentId]) as $term_id) {
                $term = $terms[$parentId][$term_id];
                $list[$term_id] = $this->_renderTerm($application, $term, $options, $depth, null);
                // Add sub-lists if any child terms
                if (!empty($terms[$term_id])
                    && (empty($options['depth']) || $depth + 1 < $options['depth'])
                ) {
                    $this->_listTerms($application, $terms, $list, $options, $term_id, $depth + 1);
                }
            }
        }
    }

    protected function _renderTerm(Application $application, array $term, array $options, $depth, $count)
    {
        $ret = $options['link'] ? sprintf('<a href="%s">%s</a>', $term['url'], $application->H($term['title'])) : $application->H($term['title']);
        if (!empty($options['icon'])) {
            $size_class = $options['icon_size'] === 'sm' ? ' drts-icon-sm' : '';
            if (!empty($term['icon_src'])) {
                $ret = '<img src="' . $application->H($term['icon_src']) . '" alt="" class="drts-icon' . $size_class . '" /><span>' . $ret . '</span>';
            } elseif (!empty($term['icon'])) {
                $style = empty($term['color']) ? '' : 'background-color:' .  $application->H($term['color']) . ';color:#fff;';
                $ret = '<i style="' . $style . '" class="' . $application->H($term['icon']) . ' drts-icon' . $size_class . '"></i><span>' . $ret . '</span>';
            }
        }
        if ($options['return_array']) {
            return array(
                '#title' => $ret,
                '#depth' => $depth,
                '#count' => isset($count) ? $count : null,
                '#title_prefix' => $options['prefix'],
                '#attributes' => ['data-alt-value' => $term['name']],
            );
        }

        if (isset($count)) {
            if ($options['count_no_html']) {
                $ret .= ' (' . $count . ')';
            } else {
                $ret .= ' <span>(' . $count . ')</span>';
            }
        }
        if (isset($options['prefix']) && strlen($options['prefix']) && $depth) {
            $ret = str_repeat($options['prefix'], $depth) . ' ' . $ret;
        }

        return $ret;
    }
}
