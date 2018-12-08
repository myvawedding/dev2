<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Context;

class Template
{
    private static $_instance;
    private $_context, $_checkLoop, $_headHtml, $_jsHtml, $_pageIds;
    
    public function __construct(Platform $platform)
    {
        // Fetch now otherwise will be cleared when widgets are rendered
        $this->_headHtml = $platform->getHeadHtml();
        $this->_jsHtml = $platform->getJsHtml();
        
        $page_slugs = $platform->getPageSlugs();
        $this->_pageIds = (array)$page_slugs[2];
        
        $this->_checkLoop = defined('DRTS_WORDPRESS_TEMPLATE_FIX_MENU') && DRTS_WORDPRESS_TEMPLATE_FIX_MENU;
    }
    
    public static function getInstance(Platform $platform)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self($platform);
        }
        return self::$_instance;
    }
    
    public function setContext(Context $context)
    {
        $this->_context = $context;
        return $this;
    }

    public function render()
    {        
        add_action('wp_head', array($this, 'onWpHeadAction'));
        add_action('wp_footer', array($this, 'onWpFooterAction'), 99);
        add_filter('body_class', array($this, 'onBodyClassFilter'));
        
        // Hook with 3rd party breadcrumb plugins
        if (defined('WC_VERSION')) {
            add_filter('woocommerce_get_breadcrumb', array($this, 'onWoocommerceGetBreadcrumbFilter'));
        }
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_breadcrumb_links', array($this, 'onWpseoBreadcrumbLinksFilter'));
        }
        if (class_exists('\breadcrumb_navxt', false)) {
            add_action('bcn_after_fill', array($this, 'onBcnAfterFillAction'));
        }

        if (isset($this->_context)) {                
            add_filter('the_title', array($this, 'onTheTitleFilter'), PHP_INT_MAX - 1, 2);
        }
        return $this;
    }

    public function onWpHeadAction()
    {
        echo $this->_headHtml;
    }
    
    public function onWpFooterAction()
    {
        echo $this->_jsHtml;
    }
    
    public function onBodyClassFilter($classes)
    {
        if (isset($this->_context)) {
            $route = $this->_context->getRoute();
            $classes[] = 'drts-' . strtolower($route->controller_component . '-' . $route->controller);
        }
        return $classes;
    }
    
    public function onWoocommerceGetBreadcrumbFilter($crumbs)
    {
        if (isset($GLOBALS['drts_entity'])
            && ($page_name = get_query_var('drts_parent_pagename'))
            && ($page = get_page_by_path($page_name))
        ) {
            $home = array_shift($crumbs);
            
            // WC does not seem to include parent post in crumbs, so add it if any
            if ($GLOBALS['drts_entity']->getType() === 'post'
                && ($parent_post_id = $GLOBALS['drts_entity']->getParentId())
                && ($parent_post = get_post($parent_post_id))
            ) {
                $current_post = array_pop($crumbs);
                $crumbs[] = [$parent_post->post_title, get_permalink($parent_post)];
                $crumbs[] = $current_post;
            }
            // Add link to post permalink if on an action page
            if (get_query_var('drts_action')) {
                $action_crumb = array_pop($crumbs);
                $crumbs[] = [
                    $this->_getEntityTitle($GLOBALS['drts_entity']),
                    get_permalink($GLOBALS['drts_entity']->getId())
                ];
                $crumbs[] = $action_crumb;
            }
            // Add custom archive page
            array_unshift($crumbs, array($page->post_title, get_permalink($page)));
            // Add back home
            array_unshift($crumbs, $home);
        }
        return $crumbs;
    }

    protected function _getEntityTitle($entity)
    {
        $title = $entity->getTitle();
        return strlen($title) ? $title : __('(no title)', 'directories');
    }
    
    
    public function onWpseoBreadcrumbLinksFilter($crumbs)
    {
        if (isset($GLOBALS['drts_entity'])
            && ($page_name = get_query_var('drts_parent_pagename'))
            && ($page = get_page_by_path($page_name))
        ) {
            $home = array_shift($crumbs);
            // Add custom archive page
            array_unshift($crumbs, ['url' => get_permalink($page), 'text' => $page->post_title]);
            // Add back home
            array_unshift($crumbs, $home);
            // Add link to post permalink if on an action page
            if (get_query_var('drts_action')) {
                $action_crumb = array_pop($crumbs);
                array_push($crumbs, [
                    'url' => get_permalink($GLOBALS['drts_entity']->getId()),
                    'text' => $this->_getEntityTitle($GLOBALS['drts_entity']),
                ]);
                array_push($crumbs, $action_crumb);
            }
        }
        return $crumbs;
    }
    
    public function onBcnAfterFillAction($bcnBreadcrumbTrail)
    {
        if (isset($GLOBALS['drts_entity'])
            && ($page_name = get_query_var('drts_parent_pagename'))
            && ($page = get_page_by_path($page_name))
        ) {
            $home = array_pop($bcnBreadcrumbTrail->breadcrumbs);
            // Add custom archive page
            $bcnBreadcrumbTrail->add(new \bcn_breadcrumb(
                $page->post_title,
                $bcnBreadcrumbTrail->opt['Hpost_page_template'],
                array('post', 'post-page'),
                get_permalink($page),
                $page->ID
            ));
            // Add back home
            $bcnBreadcrumbTrail->add($home);
            // Add link to post permalink if on an action page
            if (get_query_var('drts_action')) {
                $post_crumb = array_shift($bcnBreadcrumbTrail->breadcrumbs);
                $action_title = $post_crumb->get_title();
                $post_crumb->set_title($this->_getEntityTitle($GLOBALS['drts_entity']));
                $post_crumb->set_url(get_permalink($GLOBALS['drts_entity']->getId()));
                array_unshift($bcnBreadcrumbTrail->breadcrumbs, $post_crumb);
                array_unshift($bcnBreadcrumbTrail->breadcrumbs, new \bcn_breadcrumb($action_title));
            }
        }
    }

    public function onTheTitleFilter($title, $pageId = null)
    {
        return $this->_isFilteringSabaiPage($pageId) ? $this->_context->getTitle(true) : $title;
    }
    
    private function _isFilteringSabaiPage($pageId)
    {
        if (empty($pageId)) return false;
        
        if (is_page() || is_tax()) {        
            if (!defined('DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK')
                || !DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK
            ) {
                if (!in_the_loop()) return false;
            }
            return in_array($pageId, $this->_pageIds);
        }
        return isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->getId() === $pageId;
    }
}