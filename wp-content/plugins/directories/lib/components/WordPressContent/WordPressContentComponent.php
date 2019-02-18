<?php
namespace SabaiApps\Directories\Component\WordPressContent;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\CSV;

class WordPressContentComponent extends AbstractComponent implements
    Entity\ITypes,
    System\IAdminRouter,
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    Field\IFilters,
    Display\IElements,
    Display\IStatistics
{
    const VERSION = '1.2.24', PACKAGE = 'directories';

    protected $_system = true;

    private $_postTypes = [], $_taxonomies = [];

    public static function description()
    {
        return 'Provides functions and features to manage WordPress pages, permissions (capabilities), and notifications.';
    }

    public static function events()
    {
        return [
            'CorePlatformWordPressInit' => 1, // run early so that others can access post types and taxonomies
            'EntityCreateBundlesCommitted' => 99,
            'EntityUpdateBundlesCommitted' => 99,
            'EntityDeleteBundlesCommitted' => 99,
        ];
    }

    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Entity_Url', [new Helper\EntityUrlHelper(), 'help'])
            ->setHelper('Entity_PermalinkUrl', [$this, 'entityPermalinkUrlHelper'])
            ->setHelper('Entity_AdminUrl', [$this, 'entityAdminUrlHelper']);
    }

    public static function entityPermalinkUrlHelper(Application $application, Entity\Type\IEntity $entity, $fragment = '', $lang = null)
    {
        if ($entity->getType() === 'term') {
            if (!$term = $entity->term()) return;

            $term->_entity = $entity; // attach entity so that page_slugs custom property can be used if any
            $url = get_term_link($term);
        } else {
            $url = get_permalink((int)$entity->getId());
        }
        switch ($application->getPlatform()->getI18n()) {
            case 'wpml':
                if (isset($lang)) {
                    $url = apply_filters('wpml_permalink', $url, $lang);
                }
                break;
            //case 'polylang':
            //    break;
            default:
                break;
        }
        return $fragment ? $url . '#' . $fragment : $url;
    }

    public static function entityAdminUrlHelper(Application $application, Entity\Type\IEntity $entity, $separator = '&amp;')
    {
        switch ($entity->getType()) {
            case 'post':
                return get_edit_post_link((int)$entity->getId(), $separator === '&amp;' ? 'display' : $separator);
            case 'term':
                return get_edit_term_link((int)$entity->getId(), $entity->getBundleName());
            default:
                return '';
        }
    }

    public function systemAdminRoutes()
    {
        return [
            '/directories/:directory_name/permissions' => [
                'controller' => 'DirectoryPermissions',
                'title_callback' => true,
                'access_callback' => true,
                'callback_path' => 'directory_permissions',
                'type' => Application::ROUTE_TAB,
                'weight' => 89,
            ],
        ];
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'directory_permissions':
                foreach ($this->_application->Entity_Bundles(null, 'Directory', $context->directory->name) as $bundle) {
                    if ($this->_application->Entity_BundleTypeInfo($bundle, 'entity_permissions') !== false) {
                        return true;
                    }
                }
                return false;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'directory_permissions':
                return _x('Permissions', 'admin tab', 'directories');
        }
    }

    public function fieldGetTypeNames()
    {
        return ['wp_post_status', 'wp_post_parent', 'wp_post_content', 'wp_term_description', 'wp_file', 'wp_image'];
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'wp_file':
                return new FieldType\FileFieldType($this->_application, $name);
            case 'wp_image':
                return new FieldType\ImageFieldType($this->_application, $name);
            case 'wp_post_parent':
                return new FieldType\PostParentFieldType($this->_application, $name);
            case 'wp_post_content':
                return new FieldType\PostContentFieldType($this->_application, $name);
            case 'wp_post_status':
                return new FieldType\PostStatusFieldType($this->_application, $name);
            case 'wp_term_description':
                return new FieldType\TermDescriptionFieldType($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return ['wp_post_content', 'wp_file', 'wp_image', 'wp_editor'];
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'wp_file':
                return new FieldWidget\FileFieldWidget($this->_application, $name);
            case 'wp_image':
                return new FieldWidget\ImageFieldWidget($this->_application, $name);
            case 'wp_post_content':
                return new FieldWidget\PostContentFieldWidget($this->_application, $name);
            case 'wp_editor':
                return new FieldWidget\EditorFieldWidget($this->_application, $name);
        }
    }

    public function fieldGetRendererNames()
    {
        return ['wp_post_content', 'wp_term_description', 'wp_file', 'wp_gallery'];
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'wp_post_content':
                return new FieldRenderer\PostContentFieldRenderer($this->_application, $name);
            case 'wp_term_description':
                return new FieldRenderer\TermDescriptionFieldRenderer($this->_application, $name);
            case 'wp_file':
                return new FieldRenderer\FileFieldRenderer($this->_application, $name);
            case 'wp_gallery':
                return new FieldRenderer\GalleryFieldRenderer($this->_application, $name);
            case 'text':
                return new FieldRenderer\TextFieldRenderer($this->_application, $name);
        }
    }

    public function fieldGetFilterNames()
    {
        return ['wp_image', 'wp_file'];
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'wp_file':
                return new FieldFilter\FileFieldFilter($this->_application, $name);
            case 'wp_image':
                return new FieldFilter\ImageFieldFilter($this->_application, $name);
        }
    }

    public function onFieldRenderersFilter(&$renderers)
    {
        $renderers['text'] = $this->_name;
    }

    public function onCorePlatformWordPressInit()
    {
        $this->_registerContentTypes();

        // Post/Term hooks
        add_filter('post_type_link', [$this, 'postTypeLinkFilter'], 10, 4);
        add_filter('term_link', [$this, 'termLinkFilter'], 10, 3);
        add_action('trashed_post', [$this, 'trashedPostAction']);
        add_action('untrashed_post', [$this, 'untrashedPostAction']);

        // Enable fetching custom field values through get_xxx_meta() functions
        add_filter('get_post_metadata', [$this, 'getPostMetadataFilter'], 10, 4);
        add_filter('get_term_metadata', [$this, 'getTermMetadataFilter'], 10, 4);

        // Enable querying posts by custom fields through WP_Query
        new Query($this->_application);

        // Hide comments related functions for drts post types
        add_filter('comments_open', [$this, 'commentsOpenFilter'], 10, 2);
        add_filter('comments_template', [$this, 'commentsTemplateFilter']);

        // Add image size
        add_image_size('drts_icon', 32, 32, true);
        add_image_size('drts_icon_lg', 48, 48, true);
        add_image_size('drts_icon_xl', 80, 80, true);
        add_image_size('drts_thumbnail', 240, 180, true);
        add_image_size('drts_thumbnail_scaled', 240, 180, false);

        // Add field values to Relevanssi index
        add_filter('relevanssi_content_to_index', [$this, 'relevanssiContentToIndex'], 10, 2);

        // AMP
        add_filter('amp_skip_post', [$this, 'ampSkipPostFilter'], 10, 3);

        // BNFW
        add_filter('bnfw_notification_name', [$this->_application, 'WordPressContent_Bnfw_notificationName'], 10, 2);
        add_filter('bnfw_shortcodes', [$this->_application, 'WordPressContent_Bnfw_shortcodes'], 10, 4);

        // Post views
        add_action('pvc_after_count_visit', [$this, 'updatePostViewCountAction']);
        add_action('wpp_post_update_views', [$this, 'updatePostViewCountAction']);

        // Body tag classes
        add_filter('body_class', [$this, 'onBodyClassFilter']);

        if (!function_exists('is_plugin_active')) {
            require ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (is_admin()) {
            // WP All Import
            if (is_plugin_active('wp-all-import/plugin.php')
                || is_plugin_active('wp-all-import-pro/wp-all-import-pro.php')
            ) {
                new CSV\WPAllImport\Importer($this->_application, $this->getPostTypeNames());
            }
        } else {
            add_shortcode('drts-entity', [$this->_application, 'WordPressContent_DoShortcode']);
        }

        // Category Order and Taxonomy Terms Order plugin
        if (is_plugin_active('taxonomy-terms-order/taxonomy-terms-order.php')) {
            add_filter('drts_entity_terms_sort', function($sort) {
                $sort['field'] = [__CLASS__, 'ttoEntityTermsSortFilter'];
                $sort['is_custom'] = true;
                return $sort;
            });
            add_action('tto/update-order', function () {
                $this->_application->Entity_TaxonomyTerms_clearCache();
            });
        }
    }

    public static function ttoEntityTermsSortFilter($order, $tableName)
    {
        return $tableName . '.term_order ' . $order;
    }

    public function entityGetTypeNames()
    {
        return ['post', 'term'];
    }

    public function entityGetType($name)
    {
        switch ($name) {
            case 'post':
                return new EntityType\PostEntityType($this->_application, $name);
            case 'term':
                return new EntityType\TermEntityType($this->_application, $name);
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        if ($slugs = $this->_application->System_Slugs(null, 'Directory')) {
            $_slugs = [];
            foreach (array_keys($slugs) as $component_name) {
                $_slugs += $slugs[$component_name];
            }
            $form['#tabs'][$this->_name] = [
                '#title' => __('Pages', 'directories'),
                '#weight' => 95,
            ];
            $form[$this->_name] = [
                '#tree' => true,
                '#tab' => $this->_name,
                '#title' => __('Page Settings', 'directories'),
            ] + $this->_application->WordPress_PageSettingsForm($_slugs, ['wordpress_pages']);
        }
    }

    protected function _registerContentTypes($force = false)
    {
        $content_types = $this->_application->WordPressContent_ContentTypes($force);
        foreach ($content_types['post_types'] as $post_type_name => $post_type) {
            $result = register_post_type($post_type_name, $post_type);
            if (is_wp_error($result)) {
                $this->_application->logError($result->get_error_message());
                continue;
            }
            $this->_postTypes[$post_type_name] = ['parent' => null, 'children' => []];
            if ($post_type['parent']) {
                $this->_postTypes[$post_type['parent']]['children'][] = $post_type_name;
                $this->_postTypes[$post_type_name]['parent'] = $post_type['parent'];
            }
        }
        foreach ($content_types['taxonomies'] as $taxonomy_type => $_taxonomies) {
            foreach ($_taxonomies as $taxonomy_name => $taxonomy) {
                $post_type_name = $taxonomy['post_type'];
                if (!isset($this->_postTypes[$post_type_name])) continue;

                unset($taxonomy['post_type']); // not required with register_taxonomy
                $result = register_taxonomy($taxonomy_name, $post_type_name, $taxonomy);
                if (is_wp_error($result)) {
                    $this->_application->logError($result->get_error_message());
                    continue;
                }
                $this->_taxonomies[$taxonomy_name] = $taxonomy_type;
            }
        }
    }

    public function getPostTypeNames()
    {
        return array_keys($this->_postTypes);
    }

    public function getTaxonomyNames()
    {
        return array_keys($this->_taxonomies);
    }

    public function hasPostType($postType)
    {
        return isset($this->_postTypes[$postType]) ? $this->_postTypes[$postType] : false;
    }

    public function hasTaxonomy($taxonomy)
    {
        return isset($this->_taxonomies[$taxonomy]) ? $this->_taxonomies[$taxonomy] : false;
    }

    public function commentsOpenFilter($open, $postId)
    {
        if ($open
            && !isset($_POST['comment']) // make sure not submitting a comment
            && ($post_type = get_post_type($postId))
            && $this->hasPostType($post_type)
            && !Display\Helper\RenderHelper::isRendering($post_type)
        ) $open = false;

        return $open;
    }

    public function commentsTemplateFilter($file)
    {
        // Disable comments if on drts content paeg and outside the current display
        if (isset($GLOBALS['drts_entity'])
            && !Display\Helper\RenderHelper::isRendering($GLOBALS['drts_entity']->getBundleName())
        ) {
            $file = __DIR__ . '/comments_template.php';
        }
        return $file;
    }

    public function onCorePlatformWordPressAdminInit()
    {
        new AdminContent($this->_application, $this->_postTypes, $this->_taxonomies);
        // BNFW plugin
        add_action('bnfw_after_notification_options', [$this->_application, 'WordPressContent_Bnfw_afterNotificationOptions'], 10, 3);
        // Personal data exporter/eraser
        add_filter('wp_privacy_personal_data_exporters', [$this, 'wpPrivacyPersonalDataExportersFilter']);
        add_filter('wp_privacy_personal_data_erasers', [$this, 'wpPrivacyPersonalDataErasersFilter']);
    }

    public function getPostMetadataFilter($value, $postId, $metaKey, $single)
    {
        if ((!$post_type = get_post_type($postId))
            || !$this->hasPostType($post_type)
        ) return $value;

        if (!empty($metaKey)) {
            if (in_array($metaKey, ['_wp_page_template',
                '_et_pb_page_layout', '_et_pb_use_builder', '_et_pb_post_hide_nav', // Divi theme
                '_extra_sidebar', '_extra_sidebar_location', // Extra theme
                'sbg_selected_sidebar_replacement', 'sbg_selected_sidebar_2_replacement', 'pyre_sidebar_position', 'pyre_sidebar_sticky' // Avada theme
            ])) {
                // Fetch page layout setting configured for the custom single item page.
                if (isset($GLOBALS['drts_entity'])
                    && $postId === $GLOBALS['drts_entity']->getId()
                    && ($page_id = $this->getBundleSingleItemPageId($GLOBALS['drts_entity']->getBundleName()))
                ) {
                    remove_filter('get_post_metadata', [$this, 'getPostMetadataFilter'], 10); // prevent loop
                    $value = get_post_meta($page_id, $metaKey, $single);
                    if ($single) $value = [$value];  // WordPress looks for index 0 if single value returned by get_metadata filter is array
                    add_filter('get_post_metadata', [$this, 'getPostMetadataFilter'], 10, 4);
                }
                return $value;
            }
        }

        return $this->_getMetadata('post', $postId, $metaKey, $single, $value);
    }

    public function getTermMetadataFilter($value, $termId, $metaKey, $single)
    {
        if ((!$taxonomy = self::getTermTaxonomy($termId))
            || !$this->hasTaxonomy($taxonomy)
        ) return $value;

        return $this->_getMetadata('term', $termId, $metaKey, $single, $value);
    }

    protected function _getMetadata($entityType, $entityId, $metaKey, $single, $value)
    {
        if (!empty($metaKey)) {
            if (strpos($metaKey, '_drts_') !== 0
                || (!$field_name = substr($metaKey, strlen('_drts_')))
            ) {
                return $value; // meta key must start with _drts_ when requesting for a specific field
            }
        }

        if ($entity = $this->_application->Entity_Entity($entityType, $entityId)) {
            if (isset($field_name)) {
                if ($single) {
                    if (null === $value = $entity->getSingleFieldValue($field_name)) {
                        $value = '';
                    }
                    $value = [$value]; // WordPress looks for index 0 if single value returned by get_metadata filter is array
                } else {
                    $value = $entity->getFieldValue($field_name);
                    if ($value === null
                        || $value === false
                    ) {
                        $value = [];
                    }
                }
            } else {
                // Need to call get_post_meta() again since returning a non null value
                // with get_post_metadata filter will not include other meta values.
                remove_filter('get_post_metadata', [$this, 'getPostMetadataFilter'], 10); // prevent loop
                $value = get_post_meta($entity->getId());
                add_filter('get_post_metadata', [$this, 'getPostMetadataFilter'], 10, 4);

                // Add all field values
                $_value = $entity->getFieldValues();
                foreach (array_keys($_value) as $field_name) {
                    if (!isset($_value[$field_name])) continue;

                    $value['_drts_' . $field_name] = is_array($_value[$field_name]) ? $_value[$field_name] : [$_value[$field_name]];
                }
            }
        }
        return $value;
    }

    public function postTypeLinkFilter($url, $post, $leavename, $sample)
    {
        $post_type = get_post_type($post);
        if ($this->hasPostType($post_type)) {
            $permalinks = $this->_application->getPlatform()->getPermalinkConfig();
            if ($pos = strpos($url, '?')) {
                $query_args = substr($url, $pos);
                $url = substr($url, 0, $pos);
            }
            if (isset($permalinks[$post_type])) {
                if (false !== $pos = strpos($url, '%id%')) {
                    $url = substr($url, 0, $pos) . $post->ID; // remove parts after %id% added by WP for some reason then append actual ID
                    if ($this->_application->addUrlTrailingSlash()) {
                        $url .= '/';
                    }
                }
                if (!empty($permalinks[$post_type]['taxonomies'])) {
                    if ($entity = $this->_application->Entity_Entity('post', $post->ID)) {
                        foreach ($permalinks[$post_type]['taxonomies'] as $tag => $taxonomy_type) {
                            if ($terms = $entity->getFieldValue($taxonomy_type)) {
                                $term = $this->_getPrimaryTerm($post->ID, $terms);
                                $slug = '/' . $term->getSlug();
                                if ($parent_slugs = $term->getCustomProperty('parent_slugs')) {
                                    $slug = '/' . implode('/', $parent_slugs) . $slug;
                                }
                            } else {
                                $slug = '';
                            }
                            $url = str_replace('/' . $tag, $slug, $url);
                        }
                    }
                }
            } else {
                if ($bundle = $this->_application->Entity_Bundle($post_type)) {
                    if (!empty($bundle->info['parent'])) {
                        if (!empty($bundle->info['public'])) {
                            if ($parent = get_post($post->post_parent)) {
                                // Remove slug and append ID
                                $url = dirname(rtrim(str_replace(':slug', $parent->post_name, $url), '/')) . '/' . $post->ID;
                                if ($this->_application->addUrlTrailingSlash()) {
                                    $url .= '/';
                                }
                            } else {
                                $url = '#'; // parent not found
                            }
                        } else {
                            $url = '#'; // no permalink for private bundle entities
                        }
                    }
                } else {
                    $url = '#'; // could not fetch bundle
                }
            }
            if (isset($query_args)) $url .= $query_args;
        }

        return $url;
    }

    protected function _getPrimaryTerm($postId, array $terms)
    {
        $keys = array_keys($terms);
        if (count($keys) > 1
            && class_exists('\WPSEO_Primary_Term', false)
        ) {
            $wpseo_primary_term = new \WPSEO_Primary_Term($terms[$keys[0]]->getBundleName(), $postId);
            if ($primary_term_id = $wpseo_primary_term->get_primary_term()) {
                foreach ($keys as $k) {
                    if ($terms[$k]->getId() == $primary_term_id) {
                        return $terms[$k];
                    }
                }
            }
        }
        return $terms[$keys[0]];
    }

    public function termLinkFilter($url, $term, $taxonomy)
    {
        if ($this->hasTaxonomy($taxonomy)
            && ($permalinks = $this->_application->getPlatform()->getPermalinkConfig())
            && isset($permalinks[$taxonomy])
        ) {
            if (false !== $pos = strpos($url, '%id%')) {
                $url = substr($url, 0, $pos) . $term->term_id; // remove parts after %id% added by WP then append actual ID
            }
            if (strpos($url, '%parent_term%') !== false) {
                if ($term->parent) {
                    if (!isset($term->_entity)
                        || (!$parent_slugs = $term->_entity->getCustomProperty('parent_slugs'))
                    ) {
                        $parent_slugs = [];
                        if ($parent_ids = $this->_application->Entity_Types_impl('term')->entityTypeParentEntityIds($term->term_id, $taxonomy)) {
                            foreach ($this->_application->Entity_Entities('term', $parent_ids, false, true) as $parent_id => $parent_entity) {
                                $parent_slugs[$parent_id] = $parent_entity->getSlug();
                            }
                        }
                    }
                }
                $url = str_replace('/%parent_term%', empty($parent_slugs) ? '' : '/' . implode('/', $parent_slugs), $url);
            }
        }

        return $url;
    }

    public function onViewEntityFallbackTaxonomyFilter(&$taxonomy, $bundle)
    {
        if ($this->hasPostType($bundle->name)) {
            $permalinks = $this->_application->getPlatform()->getPermalinkConfig();
            if (!empty($permalinks[$bundle->name]['taxonomies'])) {
                $taxonomy = array_shift($permalinks[$bundle->name]['taxonomies']);
            }
        }
    }

    public function onEntityCreateBundlesCommitted($bundles, $bundlesInfo)
    {
        // Add default perms to all roles
        $default_caps = $all_caps = $default_guest_caps = $role_caps = [];
        foreach ($bundles as $bundle) {
            if (!$perms = $this->_application->Entity_Permissions($bundle)) continue;

            foreach ($perms as $perm => $perm_info) {
                $cap = 'drts_' . $perm . '_' . $bundle->name;
                $all_caps[$cap] = 1;
                if (!empty($perm_info['default'])) {
                    $default_caps[$cap] = 1;
                    if (!empty($perm_info['guest_allowed'])) {
                        $default_guest_caps[$cap] = 1;
                    }
                }
            }
            if (isset($bundlesInfo[$bundle->type]['permissions'])) {
                foreach (array_keys($bundlesInfo[$bundle->type]['permissions']) as $role_name) {
                    $role_caps[$role_name] = [];
                    foreach ($bundlesInfo[$bundle->type]['permissions'][$role_name] as $perm) {
                        $cap = 'drts_' . $perm . '_' . $bundle->name;
                        $role_caps[$role_name][$cap] = 1;
                    }
                }
                if (isset($role_caps['_guest_'])) {
                    if (!isset($guest_caps)) {
                        $guest_caps = [];
                    }
                    $guest_caps += $role_caps['_guest_'];
                    unset($role_caps['_guest_']);
                }
            }
        }
        if (!empty($all_caps)) {
            foreach (array_keys($this->_application->WordPress_Roles()) as $role_name) {
                $role = get_role($role_name);
                if ($role->has_cap(DRTS_WORDPRESS_ADMIN_CAPABILITY)
                    || $role->has_cap('manage_directories')
                ) {
                    $caps = array_keys($all_caps);
                } elseif (isset($role_caps[$role_name])) {
                    $caps = array_keys($role_caps[$role_name]);
                } else {
                    $caps = array_keys($default_caps);
                }
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
            if (!isset($guest_caps)) {
                $guest_caps = $default_guest_caps;
            }
            if (!empty($guest_caps)) {
                // Update guest perms
                $current_guest_caps = $this->_application->getPlatform()->getOption('guest_permissions', []);
                $this->_application->getPlatform()->setOption('guest_permissions', $guest_caps + $current_guest_caps);
            }
        }

        $this->_application->WordPressContent_ContentTypes_clearCache();
    }

    public function onEntityUpdateBundlesCommitted($bundles)
    {
        $this->_application->WordPressContent_ContentTypes_clearCache();
    }

    public function onEntityAfterCreatePostEntity($bundle, $entity, $values, $extraArgs)
    {
        $this->_maybeSetTerms($bundle, $entity, $values, $extraArgs);
        $this->_maybeAssociateEntityImage($bundle, $entity, $values, true);
    }

    public function onEntityAfterUpdatePostEntity($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        $this->_maybeSetTerms($bundle, $entity, $values, $extraArgs);
        $this->_maybeAssociateEntityImage($bundle, $entity, $values);
    }

    protected function _isPostTypeAdmin($bundle)
    {
        return is_admin()
            && $this->hasPostType($bundle->name)
            && isset($GLOBALS['pagenow'])
            && $GLOBALS['pagenow'] === 'post.php';
    }

    /**
     * Sync taxonomy terms selected with that of WordPress
     */
    protected function _maybeSetTerms($bundle, $entity, $values, $extraArgs)
    {
        if (empty($bundle->info['taxonomies'])) return;

        foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy) {
            if (!isset($values[$taxonomy_bundle_type])) continue;

            $term_ids = [];
            foreach ($values[$taxonomy_bundle_type] as $value) {
                if (!empty($value['auto'])) continue;

                $term_ids[] = (int)$value['value']; // must be int otherwise numeric terms are created
            }
            wp_set_object_terms($entity->getId(), $term_ids, $taxonomy);
        }
    }

    protected function _maybeAssociateEntityImage($bundle, $entity, $values, $isNew = false)
    {
        if (empty($bundle->info['entity_image'])) return;

        $image_field = $bundle->info['entity_image'];
        if (!isset($values[$image_field])) return;

        $meta_key = '_entity_id_' . $image_field;

        if (!$isNew) {
            $posts = get_posts([
                'post_type' => 'attachment',
                'meta_key' => $meta_key,
                'meta_value' => $entity->getId(),
            ]);
            foreach ($posts as $post) {
                // Unattach currently attached
                wp_update_post([
                    'ID' => $post->ID,
                    'post_parent' => 0,
                ]);
                delete_post_meta($post->ID, $meta_key);
            }
        }

        // Attach
        foreach ($values[$image_field] as $value) {
            wp_update_post([
                'ID' => $value['attachment_id'],
                'post_parent' => $entity->getId(),
            ]);
            update_post_meta($value['attachment_id'], $meta_key, $entity->getId());
        }
    }

    public function trashedPostAction($postId)
    {
        $this->_trashPost($postId);
    }

    public function untrashedPostAction($postId)
    {
        $this->_trashPost($postId, false);
    }

    protected function _trashPost($postId, $trash = true)
    {
        static $processing = [];

        if ((!$post = get_post($postId))
            || (!$post_type = $this->hasPostType($post->post_type))
        ) return;

        $processing[$postId] = true;

        if (!empty($post_type['children'])) {
            if ($trash) {
                $func = 'wp_trash_post';
                $child_post_status = 'any';
            } else {
                $func = 'wp_untrash_post';
                $child_post_status = 'trash';
            }
            foreach ($post_type['children'] as $child_post_type) {
                foreach (get_children(['post_parent' => $post->ID, 'post_type' => $child_post_type, 'post_status' => $child_post_status]) as $child_post) {
                    $func($child_post->ID);
                }
            }
            // Invoke entity updated events with new status and then update own status
            if ($entity = $this->_maybeInvokeEntityUpdatedEvents($post)) {
                $this->_application->getComponent('Entity')->updateParentPostStats($entity, true, true, true);
            }
        } elseif (!empty($post_type['parent'])) {
            // Invoke entity updated events with new status
            if ($entity = $this->_maybeInvokeEntityUpdatedEvents($post)) {
                // Update parent post status
                if (($parent_id = $entity->getParentId())
                    && empty($processing[$parent_id]) // do not update if trashing/untrashing parent
                    && ($parent_entity = $this->_application->Entity_Entity('post', $parent_id))
                ) {
                    // Update parent stats
                    $this->_application->getComponent('Entity')->updateParentPostStats($parent_entity, true, true, true);
                }
            }
        }
    }

    protected function _maybeInvokeEntityUpdatedEvents($post)
    {
        if ((!$entity = $this->_application->Entity_Entity('post', $post->ID, false))
            || (!$bundle = $this->_application->Entity_Bundle($entity))
        ) return;

        $this->_application->Entity_LoadFields($entity, null, true);
        // Create old entity with old status
        $old_entity = clone $entity;
        $old_entity->setStatus($post->post_status === 'trash' ? 'publish' : 'trash'); // @todo get real previous status
        // Notify that the status of entity has changed
        $this->_application->Entity_Save_invokeEvents(
            'post',
            $entity->getBundleType(),
            [$bundle, $entity, $old_entity, ['status' => $post->post_status], []],
            'update',
            'success'
        );
        return $entity;
    }

    public function onEntityFormTaxonomySelectorFilter(&$selector, $bundle, $taxonomyBundleType)
    {
        if ($bundle->entitytype_name === 'post') {
            // Add selector for admin add page
            $selector .= ',input[name="tax_input[' . $bundle->info['taxonomies'][$taxonomyBundleType] . '][]"]';
        }
    }

    public function onEntityFormTaxonomyValuesFilter(&$values, $bundle, $taxonomyBundleType)
    {
        if ($bundle->entitytype_name === 'post'
            && isset($bundle->info['taxonomies'][$taxonomyBundleType])
            && $this->_isPostTypeAdmin($bundle)
        ) {
            $taxonomy_name = $bundle->info['taxonomies'][$taxonomyBundleType];
            $values = empty($_POST['tax_input'][$taxonomy_name]) ? [] : $_POST['tax_input'][$taxonomy_name];
        }
    }

    public function onEntityStorageQueryFilter(&$parsed, $entityType, $fieldQuery, $isCount)
    {
        if (!defined('ICL_SITEPRESS_VERSION')
            || ($entityType !== 'post' && $entityType !== 'term')
            || (!$bundle_name = $fieldQuery->getQueriedBundleName())
            || !$this->_application->getPlatform()->isTranslatable($entityType, $bundle_name)
        ) return;

        $db = $this->_application->getDB();
        $parsed['table_joins'] .= sprintf(
            ' LEFT JOIN %sicl_translations icl_t ON icl_t.element_id = %s',
            $db->getConnection()->getWpdb()->prefix,
            $parsed['table_id_column']
        );
        $parsed['criteria'] .= ' AND icl_t.element_type = ' . $db->escapeString(($entityType === 'term' ? 'tax_' : 'post_') . $bundle_name)
            . ' AND (icl_t.language_code = ' . $db->escapeString(ICL_LANGUAGE_CODE) . ' OR icl_t.language_code IS NULL)';
    }

    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['public']) || $bundle->entitytype_name !== 'post' ? [] : ['wp_comments', 'wp_acf'];
    }

    public function displayGetElement($name)
    {
        switch ($name) {
            case 'wp_comments':
                return new DisplayElement\CommentsDisplayElement($this->_application, $name);
            case 'wp_acf':
                return new DisplayElement\AcfDisplayElement($this->_application, $name);
        }

    }

    public function displayGetStatisticNames(Entity\Model\Bundle $bundle)
    {
        if (empty($bundle->info['public']) || $bundle->entitytype_name !== 'post') return [];

        $ret = ['wp_comments'];
        if (DisplayStatistic\PostViewsDisplayStatistic::getFunc()) {
            $ret[] = 'wp_post_views';
        }

        return $ret;
    }

    public function displayGetStatistic($name)
    {
        switch ($name) {
            case 'wp_comments':
                return new DisplayStatistic\CommentsDisplayStatistic($this->_application, $name);
            case 'wp_post_views':
                return new DisplayStatistic\PostViewsDisplayStatistic($this->_application, $name);
        }
    }

    public function onEntityCreatePostEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        $this->_maybeSetPostThumbnail($bundle, $entity);
    }

    public function onEntityUpdatePostEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        $this->_maybeSetPostThumbnail($bundle, $entity);
    }

    protected function _maybeSetPostThumbnail($bundle, $entity)
    {
        if (is_admin()
            || !post_type_supports($bundle->name, 'thumbnail')
            || empty($bundle->info['wp_post_thumbnail'])
            || empty($bundle->info['wp_post_thumbnail_auto'])
            || empty($bundle->info['entity_image'])
        ) return;

        delete_post_thumbnail($entity->getId());
        if ($value = $entity->getSingleFieldValue($bundle->info['entity_image'])) {
            set_post_thumbnail($entity->getId(), $value['attachment_id']);
        }
    }

    protected function _hasRole(array $roles, array $current)
    {
        if (!empty($roles)
            && !empty($current)
        ) {
            foreach ($current as $current_role) {
                if (in_array($current_role, $roles)) return true;
            }
        }

        return false;
    }

    public function onFieldFieldDataFilter(&$fieldData, $bundle, &$displayElement)
    {
        if (!empty($displayElement['visibility']['wp_check_role'])
            && !empty($displayElement['visibility']['wp_roles'])
        ) {
            // Custom field data must start with underscore
            $fieldData['data']['_wp_roles'] = $displayElement['visibility']['wp_roles'];
        } else {
            $fieldData['data']['_wp_roles'] = null;
        }
    }

    public function onEntityFieldWidgetFilter(&$ele, $entity, $field)
    {
        if ($wp_roles = $field->getFieldData('_wp_roles')) {
            $current_user_roles = $this->_application->getUser()->isAnonymous() ? ['_guest_'] : (array)wp_get_current_user()->roles;
            if (!$this->_hasRole($wp_roles, $current_user_roles)) $ele = null;
        }
    }

    public function onDisplayDisplayFilter(&$display, $bundle, $displayType, $displayName)
    {
        // Check role to view the display?
        if (empty($display['wp_check_roles'])
            || empty($display['elements'])
            || is_admin()
        ) return;

        $current_user_roles = $this->_application->getUser()->isAnonymous() ? ['_guest_'] : (array)wp_get_current_user()->roles;
        $this->_checkDisplayElementRole($display['elements'], $current_user_roles);
    }

    protected function _checkDisplayElementRole(array &$elements, array $currentUserRoles)
    {
        foreach (array_keys($elements) as $element_id) {
            if (!empty($elements[$element_id]['visibility']['wp_check_role'])
                && !$this->_hasRole($elements[$element_id]['visibility']['wp_roles'], $currentUserRoles)
            ) {
                unset($elements[$element_id]);
            } else {
                if (!empty($elements[$element_id]['wp_check_children_roles'])) {
                    $this->_checkDisplayElementRole($elements[$element_id]['children'], $currentUserRoles);
                }
            }
        }
    }

    public function onDisplayCacheDisplayFilter(&$display, $bundle, $displayType, $displayName)
    {
        // Cache if display/row/column/element require role check so that we do not need to process those do not required at runtime
        foreach (array_keys($display['elements']) as $element_id) { // elements
            $this->_checkDisplayElementRoleSettings($display, $display['elements'][$element_id]);
        }
    }

    protected function _checkDisplayElementRoleSettings(&$display, &$element)
    {
        $ret = false;
        if (!empty($element['visibility']['wp_check_role'])) {
            $display['wp_check_roles'] = $ret = true;
        }
        if (!empty($element['children'])) {
            foreach (array_keys($element['children']) as $element_id) { // elements
                if ($this->_checkDisplayElementRoleSettings($display, $element['children'][$element_id])) {
                    $element['wp_check_children_roles'] = $ret = true;
                }
            }
        }

        return $ret;
    }

    public function onDisplayVisibilitySettingsFormFilter(&$form, $display, $element, $values, $options)
    {
        $element_name = is_object($element) ? $element->name : $element;
        if ((strpos($element_name, 'entity_form_entity_') === 0 && $element_name !== 'entity_form_entity_reference')
            || in_array($element_name, ['entity_form_wp_post_parent'])
        ) return;

        $roles = [];
        foreach ($this->_application->WordPress_Roles() as $role_name => $role_info) {
            $roles[$role_name] = $role_info['name'];
        }
        $roles['_guest_'] = __('Guest', 'directories');
        $form['wp_check_role'] = [
            '#type' => 'checkbox',
            '#title' => __('Visible to selected roles only', 'directories'),
            '#default_value' => !empty($values['wp_check_role']),
            '#horizontal' => true,
        ];
        $form['wp_roles'] = [
            '#type' => 'checkboxes',
            '#columns' => 3,
            '#options' => $roles,
            '#default_value' => empty($values['wp_roles']) ? null : $values['wp_roles'],
            '#horizontal' => true,
            '#states' => [
                'visible' => [
                    'input[name="visibility[wp_check_role]"]' => ['type' => 'checked', 'value' => true],
                ],
            ],
        ];
    }

    public function ampSkipPostFilter($skip, $postId, $post)
    {
        if ($this->hasPostType($post->post_type)) {
            if ((!$entity = $this->_application->Entity_Entity('post', $postId))
                || ($this->_application->isComponentLoaded('Payment') && !$this->_application->Payment_Plan_hasFeature($entity, 'payment_amp')) // is feature enabled?
            ) {
                // Stop WordPress AMP plugin from rendering AMP enabled page
                $skip = true;
            }
        }
        return $skip;
    }

    public function onEntityAdminBundleInfoEdited($bundle)
    {
        $this->_application->WordPressContent_ContentTypes_clearCache();
    }

    public function onFakerAdminGenerateTitleFilter($title, $context, $bundle, $titleType)
    {
        $this->_filterAdminTitle($context, $bundle, $titleType);
    }

    public function onCsvAdminImportTitleFilter($title, $context, $bundle, $titleType)
    {
        $this->_filterAdminTitle($context, $bundle, $titleType);
    }

    public function onCsvAdminExportTitleFilter($title, $context, $bundle, $titleType)
    {
        $this->_filterAdminTitle($context, $bundle, $titleType);
    }

    protected function _filterAdminTitle($context, $bundle, $titleType)
    {
        if ($titleType !== Application::ROUTE_TITLE_INFO) return;

        if (!empty($bundle->info['is_taxonomy'])) {
            if (!$taxonomy = get_taxonomy($bundle->name)) return;

            $url = admin_url('edit-tags.php?taxonomy=' . $bundle->name . '&post_type=' . $taxonomy->object_type[0]);
        } else {
            $url = admin_url('edit.php?post_type=' . $bundle->name);
        }
        $label = $bundle->entitytype_name === 'term' ? get_taxonomy($bundle->name)->labels->name : get_post_type_object($bundle->name)->labels->name;
        $context->clearTabs()->clearMenus()->clearInfo()->setInfo($label, $url);
    }

    public function onWordPressContentINotificationsInstallSuccess(AbstractComponent $component)
    {
        $this->_createNotifications($component);
    }

    public function onWordPressContentINotificationsUpgradeSuccess(AbstractComponent $component)
    {
        $this->_createNotifications($component);
    }

    protected function _createNotifications($component)
    {
        foreach ($component->wpGetNotificationNames() as $name) {
            $this->_application->WordPressContent_Notifications_create($name);
        }
    }

    public function updatePostViewCountAction($postId)
    {
        // Notify view count has been updated for a post
        if ($func = DisplayStatistic\PostViewsDisplayStatistic::getFunc()) {
            $count = $func($postId);
            $this->_application->Action('post_view_count_updated', [$postId, $count]);
        }
    }

    public function onCsvExportQueryFilter(Entity\Type\Query $query, Entity\Model\Bundle $bundle)
    {
        if ($bundle->entitytype_name !== 'post') return;

        $query->fieldIsIn('status', ['publish', 'pending', 'draft', 'future', 'private', 'trash', 'inherit']);
    }

    public function onFormBuildDirectoryAdminContentTypes(&$form)
    {
        foreach ($this->_application->Entity_Bundles(null, 'Directory', $form['#directory']->type) as $bundle) {
            if (empty($bundle->info['is_taxonomy'])
                && empty($bundle->info['parent'])
            ) {
                $main_post_type = $bundle->name;
                break;
            }
        }
        if (!isset($main_post_type)) return;

        $form['content']['#header']['content'] = ['order' => 25, 'label' => __('Content', 'directories')];
        foreach (array_keys($form['content']['#options']) as $bundle_name) {
            if (!$bundle = $this->_application->Entity_Bundle($bundle_name)) continue;

            if ($bundle->entitytype_name === 'post') {
                $url = admin_url('edit.php?post_type=' . $bundle->name);
                $counts = wp_count_posts($bundle->name, 'readable');
                $count = $counts->publish + $counts->pending + $counts->draft + $counts->future + $counts->private;
            } else {
                $url = admin_url('edit-tags.php?taxonomy=' . $bundle->name . '&post_type=' . $main_post_type);
                $count = wp_count_terms($bundle->name, ['hide_empty' => false]);
            }
            $form['content']['#options'][$bundle_name]['content'] = $this->_application->LinkTo($count, $url);
        }
    }

    public function onDirectoryAdminDirectoryLinksFilter(&$links, $directory)
    {
        $has_permission = false;
        foreach ($this->_application->Entity_Bundles(null, 'Directory', $directory->name) as $bundle) {
            if ($this->_application->Entity_BundleTypeInfo($bundle, 'entity_permissions') !== false) {
                $has_permission = true;
                break;
            }
        }
        if ($has_permission) {
            $links['settings']['link'][89] = $this->_application->LinkTo(
                __('Permissions', 'directories'),
                $this->_application->Url('/directories/' . $directory->name . '/permissions')
            );
        }
    }

    public function onEntityBundleSettingsFormFilter(&$form, $bundle, $submitValues)
    {
        if (empty($bundle->info['public'])
            || !empty($bundle->info['is_user'])
            || !empty($bundle->info['internal'])
        ) return;

        if ($page_settings_form = $this->_application->WordPressContent_PageSettingsForm($bundle, ['wordpress_page'])) {
            $form['general']['wordpress_page'] = [
                '#tree' => true,
                '#title' => __('Single Item Page Settings', 'directories'),
                '#weight' => 10,
            ] + $page_settings_form;
        }

        if (empty($bundle->info['is_taxonomy'])
            && isset($form['general']['image'])
        ) {
            $form['general']['image'] += [
                'wp_post_thumbnail' => [
                    '#type' => 'checkbox',
                    '#title' => __('Enable featured image (post thumbnail)', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => !empty($bundle->info['wp_post_thumbnail']),
                ],
                'wp_post_thumbnail_auto' => [
                    '#type' => 'checkbox',
                    '#title' => __('Automatically generate featured image', 'directories'),
                    '#horizontal' => true,
                    '#default_value' => !empty($bundle->info['wp_post_thumbnail_auto']),
                    '#states' => [
                        'visible' => [
                            'input[name="wp_post_thumbnail"]' => ['value' => true],
                        ],
                    ],
                    '#description' => __('Check this option to automatically generate a featured image using the first image when submitted from the frontend.', 'directories'),
                ],
            ];
        }
    }

    public function onDirectoryAdminExportDirectoryFilter(&$export, $directory)
    {
        // Retrieve all caps
        $caps = [];
        foreach (array_keys($export['bundles']) as $bundle_type) {
            if ((!$bundle = $this->_application->Entity_Bundle($bundle_type, 'Directory', $directory->name))
                || (!$perms = $this->_application->Entity_Permissions($bundle))
            ) continue;

            foreach (array_keys($perms) as $perm) {
                $caps[$bundle_type][$perm] = 'drts_' . $perm . '_' . $bundle->name;
            }
        }
        if (empty($caps)) return;

        // Export role permissions by bundle
        foreach (array_keys($this->_application->WordPress_Roles()) as $role_name) {
            $role = get_role($role_name);
            foreach (array_keys($caps) as $bundle_type) {
                if ($role->has_cap(DRTS_WORDPRESS_ADMIN_CAPABILITY)
                    || $role->has_cap('manage_directories')
                ) {
                    $role_perms = array_keys($caps[$bundle_type]);
                } else {
                    $role_perms = [];
                    foreach ($caps[$bundle_type] as $perm => $cap) {
                        if ($role->has_cap($cap)) {
                            $role_perms[] = $perm;
                        }
                    }
                }
                $export['bundles'][$bundle_type]['permissions'][$role_name] = $role_perms;
            }
        }

        // Export guest permissions
        $platform = $this->_application->getPlatform();
        foreach (array_keys($caps) as $bundle_type) {
            $bundle = $this->_application->Entity_Bundle($bundle_type, 'Directory', $directory->name);
            $guest_perms = [];
            foreach ($caps[$bundle_type] as $perm => $cap) {
                if ($platform->guestHasPermission($cap)) {
                    $guest_perms[] = $perm;
                }
            }
            $export['bundles'][$bundle_type]['permissions']['_guest_'] = $guest_perms;
        }
    }

    public function onSearchFieldSettingsFilter(&$form, $bundle, $fieldName, $settings)
    {
        // Search taxonomies?
        if ($fieldName !== 'search_keyword'
            || empty($bundle->info['taxonomies'])
        ) return;

        $taxonomy_options = [];
        foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_bundle_type) {
            $taxonomy_options[$taxonomy_bundle_type] = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'label_singular');
        }
        $form['taxonomies'] = [
            '#type' => 'checkboxes',
            '#title' => __('Search taxonomy term names', 'directories'),
            '#default_value' => $settings['taxonomies'],
            '#options' => $taxonomy_options,
            '#weight' => 6,
            '#horizontal' => true,
        ];
    }

    public function onCoreUninstall($removeData = false)
    {
        // make sure post type and taxonomy names are fetched
        $this->_registerContentTypes(true);
    }

    public function uninstall($removeData = false)
    {
        if (!$removeData) return;

        $this->_deleteContent($this->getPostTypeNames(), $this->getTaxonomyNames());
    }

    protected function _deleteContent(array $postTypes, array $taxonomies)
    {
        global $wpdb;

        // Delete posts
        if (!empty($postTypes)) {
            $post_types = array_map(function($v) { return "'" . esc_sql($v) . "'"; }, $postTypes);
            $wpdb->query('DELETE FROM ' . $wpdb->posts . ' WHERE post_type IN (' . implode(',', $post_types) . ');');
            $wpdb->query('DELETE meta FROM ' . $wpdb->postmeta . ' meta LEFT JOIN ' . $wpdb->posts . ' posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;');
        }

        // Delete terms
        if (!empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $wpdb->delete($wpdb->term_taxonomy, ['taxonomy' => $taxonomy]);
                $wpdb->query('DELETE tr FROM ' . $wpdb->term_relationships . ' tr LEFT JOIN ' . $wpdb->posts . ' posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;');
                $wpdb->query('DELETE t FROM ' . $wpdb->terms . ' t LEFT JOIN ' . $wpdb->term_taxonomy . ' tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;');
                $wpdb->query('DELETE tm FROM ' . $wpdb->termmeta . ' tm LEFT JOIN ' . $wpdb->term_taxonomy . ' tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;');
            }
        }
    }

    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        $this->_application->WordPressContent_ContentTypes_clearCache();

        if (empty($deleteContent)) return;

        $post_types = $taxonomies = [];
        foreach (array_keys($bundles) as $i) {
            $bundle_name = $bundles[$i]->name;
            if (!empty($bundles[$i]->info['is_taxonomy'])) {
                if ($this->hasTaxonomy($bundle_name)) {
                    $taxonomies[] = $bundle_name;
                }
            } else {
                if ($this->hasPostType($bundle_name)) {
                    $post_types[] = $bundle_name;
                }
            }
        }
        $this->_deleteContent($post_types, $taxonomies);
    }

    public function relevanssiContentToIndex($content, $post)
    {
        if ($this->hasPostType($post->post_type)) {
            foreach ($this->_application->Entity_Field($post->post_type) as $field) {
                if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IHumanReadable
                    || (!$entity = $this->_application->Entity_Entity('post', $post->ID))
                ) continue;

                $content .= $field_type->fieldHumanReadableText($field, $entity);
            }
        }
        return $content;
    }

    public function onDirectoryValidateNameFilter(&$true, $name)
    {
        if (!$true) return;

        $true = !post_type_exists($name . '_dir_ltg')
            && !taxonomy_exists($name . '_dir_cat')
            && !taxonomy_exists($name . '_dir_tag');
    }

    public function onEntityFieldConditionRuleFilter(&$rule, $field, $compare, $value, $name, $type)
    {
        if ($field->getFieldType() === 'entity_terms'
            && ($taxonomy_bundle = $this->_application->Entity_Bundle($field->getFieldName(), $field->Bundle->component, $field->Bundle->group))
        ) {
            $rule['value'] = (array)$rule['value'];
            if (is_admin()
                || $type !== 'js'
            ) {
                if ($type === 'js') {
                    $rule['target'] = '[name="tax_input[' . $taxonomy_bundle->name . '][]"],[id^="in-popular-' . $taxonomy_bundle->name . '-"]';
                }

                // Convert slugs to IDs since terms can be referenced by an ID only
                $slug_keys = [];
                foreach (array_keys($rule['value']) as $i) {
                    if (!is_numeric($rule['value'][$i])) {
                        $slug_keys[$rule['value'][$i]] = $i;
                    }
                }
                if (!empty($slug_keys)) {
                    $terms = get_terms($taxonomy_bundle->name, ['hide_empty' => false, 'slug' => array_keys($slug_keys), 'fields' => 'id=>slug']);
                    if (!is_wp_error($terms)) {
                        foreach ($terms as $id => $slug) {
                            $slug = urldecode($slug); // returned slug is URL encoded
                            if (isset($slug_keys[$slug])) {
                                $value_key = $slug_keys[$slug];
                                $rule['value'][$value_key] = (string)$id;
                            }
                        }
                    }
                }
            } else {
                // Slugs need to be URL encoded and lower-cased to match form values
                foreach (array_keys($rule['value']) as $i) {
                    if (!is_numeric($rule['value'][$i])) {
                        $rule['value'][$i] = strtolower(urlencode($rule['value'][$i]));
                    }
                }
            }
        }
    }

    public function wpPrivacyPersonalDataExportersFilter($exporters)
    {
        $exporters += $this->_application->WordPressContent_PersonalData_exporters();
        return $exporters;
    }

    public function wpPrivacyPersonalDataErasersFilter($erasers)
    {
        $erasers += $this->_application->WordPressContent_PersonalData_erasers();
        return $erasers;
    }

    public static function getTermTaxonomy($id)
    {
        global $wpdb;
        if (!empty($id)
            && ($term = $wpdb->get_row($wpdb->prepare("SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id = %d LIMIT 1", $id)))
        ) {
            return $term->taxonomy;
        }
    }

    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'wp_post_thumbnail';
        $keys[] = 'wp_post_thumbnail_auto';
    }

    public function onBodyClassFilter($classes)
    {
        if (isset($GLOBALS['drts_entity'])) {
            $classes = array_merge($classes, $this->_application->Entity_HtmlClass($GLOBALS['drts_entity'], true));
        }
        return $classes;
    }

    public function onEntityFormFilter(&$form, $bundle, $entity, $options)
    {
        if (class_exists('\ACF', false)) {
            $form['#submit'][9][] = [__NAMESPACE__ . '\DisplayElement\AcfDisplayElement', 'entityFormSubmitCallback'];
        }
    }

    public function getBundleSingleItemPageId($bundle)
    {
        if ((!$bundle = $this->_application->Entity_Bundle($bundle))
            || empty($bundle->info['public'])
            || (!empty($bundle->info['parent']) && (!$parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent'])))
        ) return;

        $page_slugs = $this->_application->getPlatform()->getPageSlugs();
        $page_id = null;
        if (empty($bundle->info['parent'])) {
            $slug_name = $bundle->group . '-' . $bundle->info['slug'];
            if (null !== ($current_slug = @$page_slugs[1][$bundle->component][$slug_name])
                && isset($page_slugs[2][$current_slug])
            ) {
                $page_id = $page_slugs[2][$current_slug];
            }
        } else {
            // Need to fetch parent slug and then prepend it to get the current slug
            $parent_slug_name = $bundle->group . '-' . $parent_bundle->info['slug'];
            if (isset($page_slugs[1][$bundle->component][$parent_slug_name])) {
                $current_slug = $page_slugs[1][$bundle->component][$parent_slug_name] . '/' . $bundle->info['slug'];
                if (isset($page_slugs[2][$current_slug])) {
                    $page_id = $page_slugs[2][$current_slug];
                }
            }
        }
        return $page_id;
    }
}