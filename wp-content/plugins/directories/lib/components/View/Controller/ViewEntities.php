<?php
namespace SabaiApps\Directories\Component\View\Controller;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class ViewEntities extends Controller
{
    protected $_settings, $_settingsCacheId;
    protected static $_defaultSettings = array(
        'push_state' => true,
        'container_template' => 'view_entities_container',
        'disable_filters' => false, // for conveniently disabling filters from outside view settings
        'hide_empty' => false,
        'is_default_view' => false,
    );
    
    protected function _doExecute(Context $context)
    {
        // Init 
        if (!$bundle = $this->_getBundle($context)) {
            $context->setError();
            return;
        }
        $context->bundle = $bundle;
        
        // Init settings and view
        $this->_settings = $this->_getSettings($context, $bundle) + self::$_defaultSettings;
        $view_mode_name = isset($this->_settings['mode']) ? $this->_settings['mode'] : 'list';
        if ((!$view = $this->View_Modes_impl($view_mode_name))
            || !$view->viewModeSupports($bundle)
        ) {
            $context->setError();
            return;
        }
        
        if (!isset($this->_settings['settings'])) {
            $this->_settings['settings'] = [];
        }
        $this->_settings['settings'] += $view->viewModeInfo('default_settings');
        $view_settings = $this->Filter('view_entities_settings', $this->_settings['settings'], array($bundle, $view));
        unset($this->_settings['settings']);
        $view_nav = $this->Filter('view_entities_nav', $view->viewModeNav($bundle, $view_settings), array($bundle, $view, $view_settings));
        
        // Init URL params
        $url_params = $this->_getUrlParams($context, $bundle);
        
        // Init sorts
        $current_sort = null;
        if (isset($view_settings['sort'])
            && ($sorts = (array)$this->Filter('view_entities_sorts', $this->_getSorts($context, $bundle, $view_settings['sort']), array($bundle, $context->getRequest()->getParams(), $view_settings)))
        ) {
            $sort_keys = array_keys($sorts);
            $default_sort = isset($view_settings['sort']['default']) && isset($sorts[$view_settings['sort']['default']]) ? $view_settings['sort']['default'] : array_shift($sort_keys);
            $url_params['sort'] = $current_sort = $context->getRequest()->asStr('sort', $default_sort, $sort_keys);
        } else {
            $sorts = [];
        }
        
        // Init pagination
        $perpage = 0;
        if (empty($view_settings['pagination']['no_pagination'])) {
            if (isset($view_settings['pagination']['perpage'])) {
                $perpage = (int)$view_settings['pagination']['perpage'];
            }
            if (empty($perpage)) $perpage = 20;
            if (!empty($view_settings['pagination']['allow_perpage'])
                && !empty($view_settings['pagination']['perpages'])
            ) {
                $url_params['num'] = $perpage = $context->getRequest()->asInt('num', $perpage, $view_settings['pagination']['perpages']);
            }
        }

        // Init display
        if (!empty($view_settings['display'])) {
            if ($pos = strpos($view_settings['display'], '-')) { // is it a custom display?
                if (!$this->Display_Display($context->bundle->name, $view_settings['display'])) {
                    // Specified custom display does not exist, fallback to default
                    $view_settings['display'] = substr($view_settings['display'], 0, $pos);
                }
            }
        } else {
            $view_settings['display'] = 'summary';
        }
        
        // Init context
        $context->addTemplate('view_entities')->setAttributes(array(
            'container_template' => $this->_settings['container_template'],
            'push_state' => $this->_settings['push_state'],
            'hide_empty' => $this->_settings['hide_empty'],
            'view' => $view,
            'bundle' => $bundle,
            'url_params' => $url_params,
            'settings' => $view_settings,
            'nav' => $view_nav,
            'sorts' => $sorts,
            'sort' => $current_sort, 
            'entities' => [],
            'filter' => [],
            'paginator' => null,
            'perpage' => $perpage,
        ));

        // Create query
        $query_settings = $this->Filter(
            'view_entities_query_settings',
            isset($view_settings['query']) ? $view_settings['query'] : [],
            [$bundle, $context]
        );
        $query = $this->_createQuery($bundle, $query_settings);
        // Showing child items?
        if (!empty($bundle->info['parent'])
            && isset($context->entity)
            && $bundle->info['parent'] === $context->entity->getBundleName()
        ) {
            $query->fieldIs('parent', $context->entity->getId());
        }
        // Show featured first?
        if (!empty($view_settings['sort']['stick_featured'])) {
            $query->sortByField('entity_featured', 'DESC');
        }
        
        // Notify
        $this->Action('view_entities', array($bundle, $query, $context));
        $view->viewModeOnView($bundle, $query, $context);
        
        // Filter
        $context->url_params_before_filter = $context->url_params;
        if (empty($this->_settings['disable_filters'])
            && isset($view_settings['filter'])
            && $this->getComponent('View')->isFilterable($bundle)
        ) {
            $this->_filter($context, $bundle, $query);
            $render_filters = !empty($view_settings['filter']['show'])
                || ($context->getContentType() === 'json' && $this->_isFilterRequested($context)); // filter submitted from outside container
        } else {
            $render_filters = false;
        }
        
        // Sort
        if (isset($context->sort)) {
            $query->sort($context->sort, $context->sorts, $context->getContainer());
        }
        
        // Do query
        $save_found_entity_ids = $render_filters;
        $query_limit = isset($view_settings['query']['limit']) ? $view_settings['query']['limit'] : 0;
        if ($perpage) {
            $page = $context->getRequest()->asInt($this->getPlatform()->getPageParam(), 1);
            $context->paginator = $paginator = $query->paginate($perpage, $query_limit, true, $save_found_entity_ids)->setCurrentPage($page);
            $context->entities = $paginator->getElements();
            $context->num_found = $paginator->getElementCount();
            $context->num_shown = $paginator->getElementLimit();
            $context->num_start = $context->num_found ? $paginator->getElementOffset() + 1 : 0;
        } else {
            $context->entities = $query->fetch($query_limit, 0, true, $save_found_entity_ids);
            $context->num_found = $context->num_shown = count($context->entities);
            $context->num_start = $context->num_found ? 1 : 0;
        }
        
        // Show filter form?
        $enable_facet_count = (bool)$this->getComponent('View')->getConfig('filters', 'facet_count');
        if ($render_filters) {
            $context->filter['form'] = $this->_getFilterForm($context, $bundle, $query, $enable_facet_count);
            $context->filter['is_external'] = $context->getContentType() === 'json';
        } else {
            $context->filter['form'] = null;
        }
        
        // Set template
        if ($is_ajax = $context->getRequest()->isAjax()) {
            if (strpos($is_ajax, '.drts-view-entities-container')) {
                $context->addTemplate($this->_settings['container_template']);
                if (isset($view_settings['ajax_container_template'])) {
                    $context->addTemplate($view_settings['ajax_container_template']);
                }
                //if ('html' !== $context->getContentType()) { // make sure html content was not requested
                    $context->setContentType('json'); 
                //}
            }
        } else {
            // Load view specific assets if any
            if ($assets = $view->viewModeInfo('assets')) {
                if (!empty($assets['js'])) {
                    foreach ($assets['js'] as $handle => $file) {
                        $this->getPlatform()->addJsFile(
                            $file[0],
                            $handle,
                            isset($file[1]) ? $file[1] : null, // dependency
                            isset($file[2]) ? $file[2] : null, // package
                            !empty($file[3]), // in footer
                            !empty($file[4]) // vendor
                        );
                    }
                }
                if (!empty($assets['css'])) {
                    foreach ($assets['css'] as $handle => $file) {
                        $this->getPlatform()->addCssFile(
                            $file[0],
                            $handle,
                            isset($file[1]) ? $file[1] : null, // dependency
                            isset($file[2]) ? $file[2] : null, // package
                            null, // media
                            !empty($file[3]) // vendor
                        );
                    }
                }
            }
        }
        
        if ((!empty($this->_settings['is_default_view']) || $context->getContainer() === '#drts-content')
            && get_class($this) === __CLASS__
        ) {
            // For widgets, though there may be a better way to pass data
            $GLOBALS['drts_view_entites_context'] = array(
                'container' => $context->getContainer(),
                'route' => $context->getRoute(),
                'bundle' => $bundle,
                'url_params' => $context->url_params_before_filter,
                'query' => $enable_facet_count ? $query : null,
                'filters' => isset($context->filter['filters']) ? $context->filter['filters'] : null,
                'filter_values' => isset($context->filter['filter_values']) ? $context->filter['filter_values'] : null,
            );
        }
    }
    
    protected function _getFilterForm(Context $context, Entity\Model\Bundle $bundle, Entity\Type\Query $query, $enableFacetCount)
    {
        // Create or rebuild form with query
        if (isset($context->filter['form'])) {
            if ($enableFacetCount) {
                // Form already exists, but regenerate with query to enable faceted naviations
                $filter_form_settings = $this->_getFilterFormSettings(
                    $context,
                    $bundle,
                    $context->url_params_before_filter,
                    $context->filter['filters'],
                    $context->filter['filter_values'],
                    $query,
                    $context->filter['form']->settings
                );
            } else {
                $filter_form_settings = $context->filter['form']->settings;
            }
            $requested_filters = $this->_getRequestedFilters($context);
        } else {
            $filter_form_settings = $this->_getFilterFormSettings(
                $context,
                $bundle,
                $context->url_params_before_filter,
                null,
                null,
                $enableFacetCount ? $query : null,
                null
            );
            $requested_filters = null;
        }
        
        return $filter_form_settings ? $this->Form_Build($filter_form_settings, true, $requested_filters) : null;
    }
    
    protected function _getSettings(Context $context, Entity\Model\Bundle $bundle)
    {     
        // Any custom settings?
        if (!empty($context->settings)) {
            $settings = $context->settings;
            $settings['push_state'] = false;
            // Preserve entity if inside another entity page
            if (isset($GLOBALS['drts_entity'])
                && $GLOBALS['drts_entity'] instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity
            ) {
                $settings['_entity'] = $GLOBALS['drts_entity'];
            }
            // Cache custom settings
            $this->_settingsCacheId = md5(get_class($this) . serialize($settings));
            $this->getPlatform()->setCache($settings, $this->_settingsCacheId);
        } elseif ($this->_settingsCacheId = $context->getRequest()->asStr('settings_cache_id', $this->_settingsCacheId)) {
            if ($settings = $this->getPlatform()->getCache($this->_settingsCacheId)) {
                // Reconstruct entity if inside another entity page
                if (isset($settings['_entity'])
                    && $settings['_entity'] instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity
                ) {
                    $this->Entity_LoadFields($settings['_entity']);
                    $GLOBALS['drts_entity'] = $settings['_entity'];
                }
            }
        }
        // Use default view settings if no settings
        if (empty($settings)) {
            if ($view = $this->getModel('View', 'View')->bundleName_is($bundle->name)->default_is(true)->fetchOne()) {
                $settings = ['mode' => $view->mode, 'settings' => $view->data['settings'], 'is_default_view' => true];
            } else {
                $this->logError('No view defined for bundle: ' . $bundle->name);
                exit;
            }
        } else {
            // Merge settings of a specific view
            if (isset($settings['view'])
                && ($view = $this->getModel('View', 'View')->bundleName_is($bundle->name)->name_is($settings['view'])->fetchOne())
            ) {
                $settings = array_replace_recursive(array('mode' => $view->mode, 'settings' => $view->data['settings']), $settings);
            }
        }
        
        return $settings;
    }
        
    protected function _createQuery(Entity\Model\Bundle $bundle, array $settings = [])
    {
        $query = $this->Entity_Query($bundle->entitytype_name)->fieldIs('bundle_name', $bundle->name);
        if (empty($bundle->info['is_taxonomy'])) {
            if (!empty($bundle->info['public'])) {
                $statuses = [];
                if (!isset($settings['status'])) {
                    $settings['status'] = array('publish');
                }
                foreach ($settings['status'] as $_status) {
                    $statuses[] = $this->Entity_Status($bundle->entitytype_name, $_status);
                }
                if ($this->getUser()->isAnonymous()
                    || empty($bundle->info['privatable'])
                    || !empty($settings['exclude_private'])
                ) {            
                    $query->fieldIsIn('status', $statuses);
                } else {
                    $query->startCriteriaGroup('OR')->fieldIsIn('status', $statuses);
                    if ($this->HasPermission('entity_read_private_' . $bundle->name)) { // allowed to read any private posts?
                        $query->fieldIs('status', $this->Entity_Status($bundle->entitytype_name, 'private'));
                    } else {
                        // Fetch only own private posts
                        $query->startCriteriaGroup('AND')
                            ->fieldIs('status', $this->Entity_Status($bundle->entitytype_name, 'private'))
                            ->fieldIs('author', $this->getUser()->id)
                            ->finishCriteriaGroup();
                    }
                    $query->finishCriteriaGroup();
                }
            } else {
                $query->fieldIs('status', $this->Entity_Status($bundle->entitytype_name, 'publish'))
                    ->fieldIs('author', $this->getUser()->id);
            }
        }
        
        // Query specific entities by field?
        if (!empty($settings['fields'])) {
            $field_query = $query->getFieldQuery();
            foreach ($settings['fields'] as $field_name => $query_str) {
                if (!is_int($field_name)) {
                    $this->_queryField($bundle, $field_query, $field_name, $query_str);
                } else {
                    if (is_array($query_str)) {
                        // Grouped query
                        $field_query->startCriteriaGroup('OR');
                        foreach ($query_str as $_field_name => $_query_str) {
                            $this->_queryField($bundle, $field_query, $_field_name, $_query_str);
                        }
                        $query->finishCriteriaGroup();
                    }
                }
            }
        }
        
        return $query;
    }
    
    protected function _queryField(Entity\Model\Bundle $bundle, Field\Query $query, $fieldName, $queryStr)
    {
        if (($field = $this->Entity_Field($bundle->name, $fieldName))
            && ($field_type = $this->Field_Type($field->getFieldType(), true))
            && $field_type instanceof \SabaiApps\Directories\Component\Field\Type\IQueryable
        ) {
            $field_type->fieldQueryableQuery($query, ($property = $field->isPropertyField()) ? $property : $fieldName, $queryStr, $bundle);
        }
    }
    
    protected function _filter(Context $context, Entity\Model\Bundle $bundle, Entity\Type\Query $query)
    {   
        // Do filter?
        if (!$this->_isFilterRequested($context)
            || (!$request_params = $this->_getRequestedFilters($context))
        ) return;
        
        $filters_filterable = $filter_requests = $filters = $filter_values = $filter_labels = [];
        foreach ($this->getModel('Filter', 'View')->bundleName_is($bundle->name)->fetch()->with('Field', 'FieldConfig') as $filter) {
            $filters[$filter->name] = $filter;
            if (isset($request_params[$filter->name])
                && ($ifilter = $this->Field_Filters_impl($filter->type, true))
                && ($field = $filter->getField())
                && $ifilter->fieldFilterIsFilterable($field, $filter->data['settings'], $request_params[$filter->name], $request_params)
            ) {
                $filters_filterable[$filter->type][$filter->name] = $filter->name;
                $filter_requests[$filter->name] = $request_params[$filter->name];
            }
        }
        if (!empty($filters_filterable)) {
            // Create and submit filter form
            $filter_form_settings = $this->_getFilterFormSettings($context, $bundle, $context->url_params_before_filter, $filters, $filter_requests);
            $context->filter['form'] = $this->Form_Build($filter_form_settings);
            if ($context->filter['form']->submit($filter_requests, true)) { // force submit since there is no form build ID
                $filter_values = $context->filter['form']->values;
                foreach (array_keys($filters_filterable) as $filter_type) {
                    foreach ($filters_filterable[$filter_type] as $filter_name) {
                        if (!isset($filter_values[$filter_name])) { // form validation failed
                            unset($filters_filterable[$filter_type][$filter_name], $filter_requests[$filter_name]);
                        }
                    }
                }
                if (!empty($filter_requests)) {
                    $context->url_params['filter'] = 1;
                    $context->url_params += $filter_requests;
                }
            } else {
                if ($context->filter['form']->hasError() && $context->getRequest()->isAjax()) {
                    $errors = $context->filter['form']->getError();
                    foreach (array_keys($errors) as $key) {
                        $errors[$key] = ($label = $context->filter['form']->getLabel($key)) ? $label . ': ' . $errors[$key] : $errors[$key];
                    }
                    $context->setValidateFormError(null, implode(' ', $errors));
                    return;
                }
                $filters_filterable = [];
            }

            // Apply filters and add remove filter links
            foreach (array_keys($filters_filterable) as $filter_type) {
                $filter_impl = $this->Field_Filters_impl($filter_type);
                foreach ($filters_filterable[$filter_type] as $filter_name) {
                    $filter = $filters[$filter_name];
                    if (!$field = $filter->getField()) continue;
                    
                    $filter_impl->fieldFilterDoFilter(
                        $query->getFieldQuery(),
                        $field,
                        $filter->data['settings'],
                        $filter_values[$filter_name],
                        $context->sorts
                    );
                    $default_label = $field->getFieldLabel();
                    if (!$_filter_labels = $filter_impl->fieldFilterLabels(
                        $field,
                        $filter->data['settings'],
                        $filter_values[$filter_name],
                        $filter_form_settings[$filter_name],
                        $default_label
                    )) {
                        $_filter_labels = array('' => $default_label);
                    }
                    $filter_labels[$filter_name] = $_filter_labels;
                }
            }
            
            $context->filter['filters'] = $filters;
            $context->filter['filter_values'] = $filter_requests;
            $context->filter['filters_applied'] = $filters_filterable;
            $context->filter['filters_applied_labels'] = $filter_labels;
        }
    }
        
    protected function _getFilterFormSettings(Context $context, Entity\Model\Bundle $bundle, array $urlParams = [], array $filters = null, array $filterRequests = null, Entity\Type\Query $query = null, array $currentForm = null)
    {
        return $this->View_FilterForm(
            $bundle->name,
            array(
                'url' => $this->Url($context->getRoute(), $urlParams),
                'container' => $context->getContainer(),
                'target' => '.drts-view-entities-container',
                'filters' => $filters,
                'values' => $filterRequests,
                'push_state' => $this->_settings['push_state'],
                'query' => $query,
                'current' => $currentForm,
            )
        );
    }
    
    protected function _isFilterRequested(Context $context)
    {
        return $context->getRequest()->asBool('filter', false);
    }
    
    protected function _getRequestedFilters(Context $context)
    {
        return $context->getRequest()->getParams();
    }
    
    protected function _getSorts(Context $context, Entity\Model\Bundle $bundle, array $settings)
    {
        $possible_sorts = $this->_getAvailableSorts($context, $bundle);   
        if (!empty($settings['options'])) {
            $ret = [];
            foreach ($settings['options'] as $sort_name) {
                if (!isset($possible_sorts[$sort_name])) continue;
                
                $ret[$sort_name] = $possible_sorts[$sort_name];
            }
        } else {
            $ret = $possible_sorts;
        }
        
        return $ret;
    }
    
    protected function _getAvailableSorts(Context $context, Entity\Model\Bundle $bundle)
    {
        return $this->Entity_Sorts($bundle->name);
    }

    protected function _getUrlParams(Context $context, Entity\Model\Bundle $bundle)
    {
        $ret = isset($this->_settings['url_params']) ? (array)$this->_settings['url_params'] : [];
        if ($this->_settingsCacheId) {
            $ret['settings_cache_id'] = $this->_settingsCacheId;
        }
        return $ret;
    }
    
    /*
     * @return Entity\Model\Bundle 
     */
    protected function _getBundle(Context $context)
    {
        return $context->child_bundle ?: ($context->taxonomy_bundle ?: $context->bundle);
    }
}