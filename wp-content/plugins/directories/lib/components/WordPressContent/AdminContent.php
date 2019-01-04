<?php
namespace SabaiApps\Directories\Component\WordPressContent;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Platform\WordPress\Updater;

class AdminContent
{
    protected $_application, $_postTypes, $_taxonomies;
    private $_js, $_termFormHtml, $_saving = false, $_oldStatus, $_oldPostEntity, $_oldTermEntity;
    private static $_redirectPostLocationArgs;

    public function __construct(Application $application, array $postTypes, array $taxonomies)
    {
        $this->_application = $application;
        $this->_postTypes = $postTypes;
        $this->_taxonomies = $taxonomies;

        if (($post_type = $this->_getPostType())
            || ($taxonomy = $this->_getTaxonomy())
        ) {
            if ((!$bundle = $this->_application->Entity_Bundle($post_type ? $post_type : $taxonomy))
                || (!$bundle_type_component = $this->_application->Entity_BundleTypeInfo($bundle, 'component'))
                || !$application->isComponentLoaded($bundle_type_component)
                || (!$package = $application->getComponent($bundle_type_component)->getPackage())
                || !$application->getComponent('WordPress')->validatePackage($package)
            ) {
                $this->_application->getPlatform()->setSessionVar(
                    'system_flash',
                    [['level' => 'danger', 'msg' => sprintf(__('%s: Please enter your license key.'), $package)]],
                    $this->_application->getUser()->id
                );
                wp_redirect(admin_url());
                exit;
            }
        }

        if ($post_type
            && $GLOBALS['pagenow'] === 'post-new.php'
            && !$this->_application->getUser()->isAdministrator()
        ) {
            if (!$bundle = $this->_application->Entity_Bundle($post_type)) { // this should not happen but in case
                wp_redirect(admin_url());
                exit;
            }

            // Redirect to select plan page in the frontend if non admin is trying to create a payment enabled content
            if (!empty($bundle->info['public'])
                && $this->_application->isComponentLoaded('Payment')
                && !empty($bundle->info['payment_enable'])
                && $this->_application->isComponentLoaded('FrontendSubmit')
            ) {
                wp_redirect($this->_application->Url(
                    '/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle),
                    array('bundle' => $bundle->name))
                );
                exit;
            }
        }

        // Add JS/CSS
        add_action('admin_print_styles', array($this, 'adminPrintStylesAction'));

        // Post hooks
        add_action('add_meta_boxes', array($this, 'addMetaBoxesAction'));
        add_action('admin_notices', array($this, 'adminNoticesAction'));
        add_action('pre_post_update', array($this, 'prePostUpdateAction'));
        add_action('transition_post_status', array($this, 'transitionPostStatusAction'), 10, 3);
        add_action('save_post', array($this, 'savePostAction'), 10, 3);
        add_filter('quick_edit_show_taxonomy', array($this, 'quickEditShowTaxonomyFilter'), 10, 3);
        foreach (array_keys($this->_postTypes) as $post_type) {
            // Add extra columns
            add_filter('manage_' . $post_type . '_posts_columns' , array($this, 'managePostsColumnsFilter'));
            add_action('manage_' . $post_type . '_posts_custom_column' , array($this, 'managePostsCustomColumnAction'), 10, 2);
            add_filter('manage_edit-' . $post_type . '_sortable_columns', array($this, 'manageEditSortableColumnsFilter'), 12);
            add_filter('posts_clauses', array($this, 'postsClausesFilter'), 12, 2);
        }
        add_action('restrict_manage_posts', array($this, 'restrictManagePostsAction'));
        add_action('before_delete_post', array($this, 'beforeDeletePostAction'));
        add_filter('post_row_actions', array($this, 'postRowActionsFilter'), 10, 2);
        add_action('wpml_after_save_post', array($this, 'wpmlAfterSavePostAction'), 10, 4);
        add_filter('submenu_file', array($this, 'submenuFileFilter'));
        add_filter('preview_post_link', array($this, 'previewPostLinkFilter'), 10, 2);

        // Taxonomy hooks
        add_action('created_term', array($this, 'createdTermAction'), 10, 3);
        add_action('edit_terms', array($this, 'editTermsAction'), 10, 2);
        add_action('edited_term', array($this, 'editedTermAction'), 10, 3);
        add_action('delete_term', array($this, 'deleteTermAction'), 10, 4);
        // Add tag page
        add_action('load-edit-tags.php', array($this, 'loadEditTagsPhpAction'));
        // Edit tag page
        add_action('load-term.php', array($this, 'loadTermPhpAction'));
        foreach (array_keys($this->_taxonomies) as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', array($this, 'taxonomyFormFieldsAction'));
            add_action($taxonomy . '_edit_form_fields', array($this, 'taxonomyFormFieldsAction'));
            // Add extra columns
            add_filter('manage_edit-' . $taxonomy . '_columns' , array($this, 'manageTaxonomyColumnsFilter'));
            add_action('manage_' . $taxonomy . '_custom_column' , array($this, 'manageTaxonomyCustomColumnAction'), 10, 3);
        }
        // Set lower priority for WP SEO metabox
        add_filter('wpseo_metabox_prio', function () {
            return $this->_getPost() ? 'low' : 'high';
        });

        // Add a post display state for drts pages.
        add_filter('display_post_states', array($this, 'onDisplayPostStatesFilter'), 10, 2);

        add_filter('parse_query', array($this, 'parseQueryFilter'));
    }

    protected function _getPostType()
    {
        if (!isset($GLOBALS['pagenow'])) return;

        if (in_array($GLOBALS['pagenow'], array('post-new.php', 'edit.php'))) {
            if (isset($_GET['post_type'])
                && isset($this->_postTypes[$_GET['post_type']])
            ) {
                return $_GET['post_type'];
            }
        } elseif ($GLOBALS['pagenow'] === 'post.php') {
            if (isset($GLOBALS['post'])
                && is_object($GLOBALS['post'])
                && isset($this->_postTypes[$GLOBALS['post']->post_type])
            ) {
                return $GLOBALS['post']->post_type;
            }
        }
    }

    protected function _getPost()
    {
        if (isset($GLOBALS['pagenow'])
            && in_array($GLOBALS['pagenow'], array('post.php', 'post-new.php'))
            && isset($GLOBALS['post'])
            && is_object($GLOBALS['post'])
            && isset($this->_postTypes[$GLOBALS['post']->post_type])
        ) {
            return $GLOBALS['post'];
        }
    }

    protected function _getTaxonomy()
    {
        if (isset($GLOBALS['taxnow'])
            && isset($this->_taxonomies[$GLOBALS['taxnow']])
        ) {
            return $GLOBALS['taxnow'];
        }
    }

    protected function _getTerm()
    {
        if (isset($GLOBALS['pagenow'])
            && $GLOBALS['pagenow'] === 'term.php'
            && isset($GLOBALS['tag'])
            && is_object($GLOBALS['tag'])
            && isset($this->_taxonomies[$GLOBALS['tag']->taxonomy])
        ) {
            return $GLOBALS['tag'];
        }
    }

    public function adminPrintStylesAction()
    {
        if ($post_type = $this->_getPostType()) {
            $css = [];
            if ($post = $this->_getPost()) {
                // Add/edit post page

                if (!in_array(get_post_status($post), ['pending', 'draft', 'future', 'private'])) {
                    // Hide "Preview" and "Preview Changes" buttons
                    $css[] = '#preview-action {display:none !important;}';
                }

                // Hide status selection box if non-public
                if (empty(get_post_type_object($post_type)->public)) {
                    if (get_post_status($post) !== 'publish') {
                        $css[] = '#publishing-action, #misc-publishing-actions {display:none !important;} #minor-publishing-actions {padding:10px !important;}';
                    } else {
                        $css[] = '#minor-publishing {display:none !important;}';
                    }
                }

                // Remove extra margin above fields if no title
                if (!post_type_supports($post_type, 'title')) {
                    $css[] = '#post-body-content {display:none !important;}';
                }

                // Fix for Avada fusion icons in the dashboard
                $css[] = '.dashicons-fusiona-logo:before {font-family: "icomoon" !important;}';
                // Add JS required for Yoast SEO https://github.com/Yoast/YoastSEO.js/blob/master/docs/Customization.md
                if (defined('WPSEO_FILE')) {
                    $this->_application->getPlatform()
                        ->addJsFile('wordpress-admin-yoastseo.min.js', 'drts-wordpress-yoastseo');
                }
            } else {
                // Post index page

                // Hide status selection box if non-public
                if (empty(get_post_type_object($post_type)->public)) {
                    $css[] = '.inline-edit-status {display:none !important;}';
                }
            }
            if (!empty($css)) {
                echo '<style type="text/css">' . implode(' ', $css) . '</style>';
            }
        } elseif (!$this->_getTaxonomy()) {
            return;
        }

        // Add JS/CSS
        $this->_application->getPlatform()
            ->loadDefaultAssets()
            ->addJs('DRTS.init(jQuery("#wpbody"));', true, -99)
            ->addJsFile('form.min.js', 'drts-form', array('drts')); // for modal ajax form
        add_action('admin_footer', array($this, 'adminFooterAction'));
    }

    public function adminFooterAction()
    {
        if ($js = $this->_application->getPlatform()->getJsHtml()) echo $js;

        if (isset($this->_js)) echo $this->_js;

        if ((($bundle_name = $this->_getPostType()) && !$this->_getPost()) // post type index page
            || (($bundle_name = $this->_getTaxonomy()) && !$this->_getTerm()) // taxonomy index page
        ) {
            if (($bundle = $this->_application->Entity_Bundle($bundle_name))
                && $this->_application->HasPermission('directory_admin_directory_' . $bundle->group)
            ) {
                $actions = [];
                if ($this->_application->isComponentLoaded('CSV')
                    && !$this->_application->Entity_BundleTypeInfo($bundle, 'csv_disable')
                ) {
                    $actions['import'] = __('Import', 'directories');
                    $actions['export'] = __('Export', 'directories');
                }
                if ($this->_application->isComponentLoaded('Faker')
                    && !$this->_application->Entity_BundleTypeInfo($bundle, 'faker_disable')
                ) {
                    $actions['generate'] = _x('Generate', 'generate content', 'directories');
                }
                if (!empty($actions)) {
                    $js = array(
                        '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(event) {',
                        'var title_action = jQuery(\'#wpbody-content .page-title-action\');',
                        'if (!title_action.length) title_action = jQuery(\'#wpbody-content h1\');',
                        'title_action = title_action.eq(0);'
                    );
                    $path = 'admin.php?page=drts/directories&q=/directories/' . $bundle->group . '/content_types/' . $bundle_name . '/';
                    $class = 'page-title-action drts-wordpress-admin-entities-action';
                    foreach ($actions as $action => $label) {
                        $js[] = 'title_action = jQuery(\'<a href="' . admin_url($path . $action) . '" class="' . $class . '">' . $this->_application->H($label) . '</a>\').insertAfter(title_action);';
                    }
                    $js[] = '});</script>';
                    echo implode(PHP_EOL, $js);
                }
            }
        }
    }

    // Lets Fields/Displays/View settings pages appear as sub pages of post type index page
    public function submenuFileFilter($submenuFile)
    {
        global $self, $parent_file, $plugin_page, $typenow;
        if ($plugin_page === 'drts/directories'
            && !empty($_REQUEST['q'])
            && strpos($_REQUEST['q'], '/directories/') === 0
            && ($parts = explode('/', trim($_REQUEST['q'], '/')))
            && isset($parts[4])
            && in_array($parts[4], array('generate', 'import', 'export'))
            && isset($parts[3])
            && ($bundle = $this->_application->Entity_Bundle($parts[3]))
        ) {
            if (!empty($bundle->info['is_taxonomy'])) {
                if ($taxonomy = get_taxonomy($bundle->name)) {
                    $post_type = $taxonomy->object_type[0];
                    $self = 'edit-tags.php';
                    $parent_file = 'edit.php?post_type=' . $post_type;
                    $submenuFile = 'edit-tags.php?taxonomy=' . $bundle->name . '&amp;post_type=' . $post_type; // need to use &amp; to match
                    $typenow = $bundle->name;
                }
            } else {
                $self = $parent_file = $submenuFile = 'edit.php?post_type=' . $bundle->name;
            }
            $plugin_page = null;
        }
        return $submenuFile;
    }

    public function previewPostLinkFilter($link, $post)
    {
        return isset($this->_postTypes[$post->post_type]['parent']) ? false : $link;
    }

    public function addMetaBoxesAction()
    {
        if ((!$post = $this->_getPost())
            || (!$bundle = $this->_application->Entity_Bundle($post->post_type))
        ) return;

        if (empty($_GET['drts_error'])
            || (!$values = $this->_getSaveContentErrorValues($post->ID))
        ) $values = null;

        $form = $this->_getPostForm($post, $values, 'drts');

        // Remove taxonomy fields
        $form['drts'] = array_diff_key($form['drts'], array_flip($this->_taxonomies));

        if (!isset($form['drts']['post_title'])) { // title disabled?
            remove_post_type_support($post->post_type, 'title');
        } else {
            unset($form['drts']['post_title']);
        }

        $admin_only_fields = [];
        foreach (array_keys($form['drts']) as $field_name) {
            if (strpos($field_name, '#')) continue;

            if (!empty($form['drts'][$field_name]['#admin_only'])) {
                $admin_only_fields[$field_name] = $form['drts'][$field_name]['#title'];
                $form['drts'][$field_name]['#title'] = null;
            }
        }

        $rendered = $this->_application->Entity_Form_renderDisplay(isset($values) ? $this->_application->Form_Build($form, false, array('drts' => $values)) : $form);
        if (!empty($rendered['html'])) {
            add_meta_box(
                'drts_' . $post->post_type . '_postbox',
                sprintf(__('%s Fields', 'directories'), $bundle->getLabel('singular')),
                array($this, 'addMetaBox'),
                $post->post_type,
                'normal',
                'high',
                array($rendered['html'] . PHP_EOL . $rendered['form']->getHiddenHtml())
            );
        }
        $this->_js = $rendered['js'] . PHP_EOL . $rendered['form']->getJsHtml();

        foreach (array_keys($admin_only_fields) as $field_name) {
            $field_title = $this->_application->H($admin_only_fields[$field_name]);
            if (!empty($form['drts'][$field_name]['#required'])) {
                $field_title .= '<span class="drts-form-field-required">*</span>';
            }
            add_meta_box(
                'drts_' . $post->post_type . '_postbox_' . $field_name,
                $field_title,
                array($this, 'addMetaBox'),
                $post->post_type,
                'side',
                'low',
                array($rendered['form']->getHtml($field_name, ['drts']))
            );
        }

        // Remove author meta box added by WP
        remove_meta_box('authordiv', $post->post_type, 'normal');
    }

    public function adminNoticesAction()
    {
        if (!$post = $this->_getPost()) {
            if (!$this->_getTaxonomy()
                || empty($_GET['tag_ID'])
                || (!$term_id = (int)$_GET['tag_ID'])
            ) return;

            $content_id = $term_id;
        } else {
            $content_id = $post->ID;
        }

        if ($errors = $this->_getSaveContentErrors($content_id)) {
            $html = array('<div class="error">');
            $html[] = '<p>' . $this->_application->H(__('Please correct the error(s) below.', 'directories')) . '</p>';
            foreach ($errors as $error) {
                $html[] = '<p>';
                if (isset($error['label'])) {
                    $html[] = '<strong>' . $this->_application->H($error['label']) . ':</strong> ';
                }
                $html[] = $this->_application->H($error['error']);
                $html[] = '</p>';
            }
            $html[] = '</div>';
            echo implode(PHP_EOL, $html);
        }
    }

    protected function _getPostForm($post, array $values = null, $wrap = null)
    {
        $entity = null;
        if ($post->post_status !== 'auto-draft') {
            $entity = new EntityType\PostEntity($post);
            $this->_application->Entity_LoadFields($entity);
        }
        $form = $this->_application->Entity_Form(
            isset($entity) ? $entity : $post->post_type,
            array(
                'values' => isset($values) ? ($wrap ? array($wrap => $values) : $values) : null,
                'is_admin' => true,
                'wrap' => isset($wrap) ? [$wrap] : [],
                'pre_render_display' => true,
            )
        );

        $form['#id'] = 'post'; // Form ID which is assigned by WordPress
        $form['#method'] = 'post'; // This is required to generage form biuld ID hidden field
        $form['#token'] = false;
        $form['#build_id'] = false;

        return $form;
    }

    public function addMetaBox($post, $box)
    {
        echo '<div class="drts">' . $box['args'][0] . '</div>';
    }

    public function addStatusMetaBox($post, $box)
    {
        var_dump($box['args'][0]);
    }

    public function prePostUpdateAction($postId)
    {
        if (!$post = $this->_canSavePost($postId)) return;

        $this->_oldPostEntity = new EntityType\PostEntity($post);
        $this->_application->Entity_LoadFields($this->_oldPostEntity);
    }

    public function transitionPostStatusAction($status, $oldStatus, $post)
    {
        $this->_oldStatus = $oldStatus;
    }

    protected function _isInlineSavePost($postId)
    {
        return isset($_POST['action'])
            && $_POST['action'] === 'inline-save'
            && isset($_POST['post_type'])
            && isset($this->_postTypes[$_POST['post_type']])
            && $_POST['post_ID'] == $postId;
    }

    protected function _isBulkEditPost($postId)
    {
        return isset($_GET['action'])
            && $_GET['action'] === 'edit'
            && isset($_GET['bulk_edit'])
            && $_GET['bulk_edit'] === 'Update'
            && isset($_GET['post_type'])
            && isset($this->_postTypes[$_GET['post_type']])
            && !empty($_GET['post'])
            && in_array($postId, (array)$_GET['post']);
    }

    protected function _canSavePost($postId)
    {
        if ($this->_saving
            || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || !current_user_can('edit_post', $postId)
            // We can not use _getPost() here since it will return the object of the post before the post was saved
            || (!$post = get_post($postId))
            || !isset($this->_postTypes[$post->post_type]) // sabai custom post type?
        ) return false;

        return $post;
    }

    public function savePostAction($postId)
    {
        if (!$post = $this->_canSavePost($postId)) return;

        $is_inline_save = $is_bulk_edit = false;
        if (!$this->_getPost() // add/edit post page
            && (!$is_inline_save = $this->_isInlineSavePost($post->ID)) // inline save
            && (!$is_bulk_edit = $this->_isBulkEditPost($post->ID)) // bulk edit save
        ) return;

        // Skip if auto saving or trashing
        if (in_array($post->post_status, ['auto-draft', 'trash'])) return;

        if (!isset($this->_oldPostEntity)) return; // old entity is needed for hooks to compare with the new

        // Skip if restoring
        if ($this->_oldPostEntity->getStatus() === 'trash'
            && $post->post_status !== 'trash'
        ) return;

        if (!$bundle = $this->_application->Entity_Bundle($post->post_type)) return;

        $submit_values = empty($_POST['drts']) ? [] : stripslashes_deep($_POST['drts']);
        $submit_values['post_title'][0] = $post->post_title; // need this to validate title
        // Manually set taxonomy term values assigned by WordPress
        if (!empty($bundle->info['taxonomies'])) {
            foreach ($bundle->info['taxonomies'] as $taxonomy_type => $taxonomy) {
                if (false === $this->_application->Entity_BundleTypeInfo($taxonomy_type, 'taxonomy_assignable')) continue; // not assigned by WP

                $terms = wp_get_object_terms($post->ID, $taxonomy);
                if (is_wp_error($terms)) {
                    $this->_application->logError($terms->get_error_message());
                    continue;
                }

                $submit_values[$taxonomy_type] = [];
                foreach ($terms as $term) {
                    $submit_values[$taxonomy_type][] = $term->term_id;
                }
            }
        }

        // Build form
        $form = $this->_getPostForm($post, $submit_values);

        if ($is_inline_save
            || $is_bulk_edit
        ) {
            // Remove fields other than post title and taxonomy terms to prevent those fields from being wiped out
            // since only post title and taxonomy term values can be sent through the inline/bulk edit form.
            foreach (array_keys($form) as $field_name) {
                if (strpos($field_name, '#') === 0
                    || $field_name === 'post_title'
                    || isset($bundle->info['taxonomies'][$field_name])
                ) continue;

                unset($form[$field_name]);
            }
            if ($is_bulk_edit) {
                $form['#method'] = 'get';
            }
        }
        $form = $this->_application->Form_Build($form);

        // Submit form
        $extra_args = [];
        if (!$form->submit($submit_values)) {
            if ($form->hasError()) {
                $this->_setSaveContentErrors($post, $form);
            }
            // Fetch values for reverting back
            $values = $this->_oldPostEntity->getProperties();
            // Revert back taxonomy terms
            if (!empty($bundle->info['taxonomies'])) {
                foreach ($bundle->info['taxonomies'] as $taxonomy_type => $taxonomy) {
                    if (false === $this->_application->Entity_BundleTypeInfo($taxonomy_type, 'taxonomy_assignable')) continue; // not assigned by WP

                    $values[$taxonomy_type] = [];
                    foreach ((array)$this->_oldPostEntity->getFieldValue($taxonomy_type) as $term) {
                        $values[$taxonomy_type][] = (int)$term->getId();
                    }
                }
            }
            $extra_args['skip_is_modified_check'] = true; // required to fully revert back values
        } else {
            $values = $form->values;

            // Set max number of items for each field
            if (isset($form->settings['#max_num_items'])) {
                $extra_args['entity_field_max_num_items'] = $form->settings['#max_num_items'];
            }

            // Make sure only administrators can change system statuses
            if (!empty($bundle->info['payment_enable'])
                && !$this->_application->IsAdministrator()
            ) {
                switch ($this->_oldPostEntity->getStatus()) {
                    case 'draft':
                    case 'auto-draft':
                        $values['status'] = 'draft';
                        $extra_args['skip_is_modified_check']['status'] = true; // required to revert back status
                        break;
                    default:
                        break;
                }
            }

            // Set value of the author property to 0 if current user is admin and the property is empty,
            // which comes as an array containing a null value if empty, otherwise the value will not get updated.
            if (!$is_inline_save
                && !$is_bulk_edit
                && $this->_application->IsAdministrator()
            ) {
                if (empty($values['post_author'][0])) {
                    unset($values['post_author']);
                    $values['author'] = 0;
                    // WP will automatically update the post author field with the currnent user ID if empty,
                    // so skip modified check so that the author field is updated with 0
                    $extra_args['skip_is_modified_check']['author'] = true;
                }
            } else {
                unset($values['post_author'], $values['author']);
            }

            // Let other components filter values
            $values = $this->_application->Filter('wordpress_admin_save_post_values', $values, [$post, $this->_oldPostEntity]);
        }

        if ($this->_oldPostEntity->getStatus() === 'auto-draft') {
            // Force Create events otherwise Update events will be called since WP has already created the entity
            $extra_args['force_create_events'] = true;
        }

        // Update the post, which will fire save_post again so use $this->_saving to prevent loops
        $this->_saving = true;
        $this->_application->Entity_Save($this->_oldPostEntity, ['id' => $post->ID] + $values, $extra_args);
        $this->_saving = false;
    }

    public function quickEditShowTaxonomyFilter($show, $taxonomy, $postType)
    {
        if (isset($this->_postTypes[$postType])
            && isset($this->_taxonomies[$taxonomy])
        ) {
            // Do not show in quick/bulk edit form if not assignable
            $show = false !== $this->_application->Entity_BundleTypeInfo($this->_taxonomies[$taxonomy], 'taxonomy_assignable');
        }
        return $show;
    }

     public function loadEditTagsPhpAction()
    {
        // For some reason, load-edit-tags.php is called on load-term.php action, so prevent form fields to be re-rendered
        if (isset($this->_termFormHtml)) return;

        $this->_termFormHtml = null;
        if (!$taxonomy = $this->_getTaxonomy()) return;

        $this->_termFormHtml = $this->_renderTermForm(
            $this->_getTermForm($taxonomy, null, 'drts'),
            '<div class="form-field drts">
    <label for="tag-description">%s</label>
    <div class="drts">%s</div>
    %s
</div>'
        );

        $this->_application->getPlatform()->addCssFile('wordpress-admin-tag.min.css', 'drts-wordpress-admin-tag', array('drts'));
    }

    public function loadTermPhpAction()
    {
        if (!$taxonomy = $this->_getTaxonomy()) return;

        $this->_termFormHtml = null;
        if (empty($_GET['tag_ID'])
           || (!$term_id = (int)$_GET['tag_ID'])
           || (!$term = get_term_by('id', $term_id, $taxonomy))
        ) return;

        $this->_termFormHtml = $this->_renderTermForm(
            $this->_getTermForm($term, null, 'drts'),
            '<tr class="form-field">
    <th scope="row">
        <label for="tag-description">%s</label>
    </th>
    <td>
        <div class="drts">%s</div>
        %s
    </td>
</tr>',
            'description'
        );

        $this->_application->getPlatform()->addCssFile('wordpress-admin-tag.min.css', 'drts-wordpress-admin-tag', array('drts'));
    }

    public function taxonomyFormFieldsAction()
    {
        if (isset($this->_termFormHtml)) echo $this->_termFormHtml;
    }

    protected function _getTermForm($term = null, array $values = null, $wrap = false)
    {
        $entity = null;
        if (is_object($term)) {
            $entity = new EntityType\TermEntity($term);
            $this->_application->Entity_LoadFields($entity);
        }
        $form = $this->_application->Entity_Form(
            isset($entity) ? $entity : $term,
            array('values' => $values, 'is_admin' => true, 'wrap' => $wrap, 'pre_render_display' => true));

        // Unwrap form settings if wrapped
        if ($wrap) {
            $_form =& $form[$wrap];
        } else {
            $_form =& $form;
        }

        // Remove the term title, description, parent fields since they are added by WP
        unset($_form['term_title'], $_form['term_content'], $_form['term_parent']);

        $form['#id'] = isset($entity) ? 'edittag' : 'addtag'; // Form ID which is assigned by WordPress
        $form['#method'] = 'post'; // This is required to generage form biuld ID hidden field
        $form['#token'] = false;
        $form['#build_id'] = false;

        return $form;
    }

    protected function _renderTermForm($form, $format, $descClass = '')
    {
        $form = $this->_application->Form_Render($form);
        $fields = $form->getFields(true, 'drts');
        unset($fields[Form\FormComponent::FORM_SUBMIT_BUTTON_NAME]);
        $ret = $hidden_values = [];
        foreach (array_keys($fields) as $field_name) {
            $field = $fields[$field_name];
            if ($field['#type'] === 'hidden') {
                $hidden_values[$field['#name']] = $field['#default_value'];
                continue;
            }

            $desc = null;
            if ($form->hasError($field['#name'])) {
                $desc = $this->_application->H($form->getError($field['#name']));
            } else {
                if (isset($field['#description']) && strlen($field['#description'])) {
                    $desc = $field['#description'];
                    if (empty($field['#description_no_escape'])) {
                        $desc = $this->_application->H($desc);
                    }
                }
            }

            $ret[] = sprintf(
                $format,
                $this->_application->H($field['#title']),
                implode(PHP_EOL, $field['#html']),
                isset($desc) ? '<p class="' . $descClass . '">' . $desc . '</p>' : ''
            );
        }
        foreach ($hidden_values as $hidden_name => $hidden_value) {
            $ret[] = '<input name="' . $this->_application->H($hidden_name) . '" type="hidden" value="' . $this->_application->H($hidden_value) . '" />';
        }
        $this->_js = $form->getJsHtml();

        return implode(PHP_EOL, $ret);
    }

    public function createdTermAction($termId, $ttId, $taxonomy)
    {
        if (defined('DRTS_CSV_IMPORTING')
            || !isset($this->_taxonomies[$taxonomy])
            || (!$term = get_term_by('id', $termId, $taxonomy))
        ) return;

        $this->_oldTermEntity = new EntityType\TermEntity($term); // required to save term entity
        $this->_saveTerm($term);
    }

    private function _getSaveContentErrorCacheId($postOrTermId)
    {
        return 'wordpress_admin_save_content_errors_' . $postOrTermId . '_' . $this->_application->getUser()->id;
    }

    protected function _getSaveContentErrors($id)
    {
        $cache_id = $this->_getSaveContentErrorCacheId($id);
        $ret = $this->_application->getPlatform()->getCache($cache_id);
        $this->_application->getPlatform()->deleteCache($cache_id);

        return $ret;
    }

    protected function _getSaveContentErrorValues($id)
    {
        $cache_id = $this->_getSaveContentErrorCacheId($id) . '-values';
        $ret = $this->_application->getPlatform()->getCache($cache_id);
        $this->_application->getPlatform()->deleteCache($cache_id);

        return $ret;
    }

    protected function _setSaveContentErrors($content, Form\Form $form)
    {
        if (isset($content->term_id)) {
            $id = $content->term_id; // term
        } elseif (isset($content->ID)) {
            $id = $content->ID; // post
        } else {
            return;
        }
        $errors = $form->getError();
        foreach (array_keys($errors) as $name) {
            $errors[$name] = array(
                'label' => ($label = $form->getLabel($name)) ? $label : null,
                'error' => $errors[$name],
            );
        }
        $cache_id = $this->_getSaveContentErrorCacheId($id);
        $this->_application->getPlatform()->setCache($errors, $cache_id, 60)
            ->setCache($form->values, $cache_id . '-values', 60);

        self::$_redirectPostLocationArgs = array('drts_error' => 1);

        // WPML
        if (!empty($_POST['icl_trid'])) {
            // Keeps translation connected with the source
            self::$_redirectPostLocationArgs += array(
                'trid' => $_POST['icl_trid'],
                'source_lang' => class_exists('\SitePress', false) ? \SitePress::get_source_language_by_trid($_POST['icl_trid']) : '',
            );
        }

        // Suppress WordPress notice message on redirected page
        add_filter('redirect_post_location', array(__CLASS__, 'redirectContentLocationFilter'), 99);
    }

    static public function redirectContentLocationFilter($location)
    {
        remove_filter('redirect_post_location', array(__CLASS__, __METHOD__), 99);
        return remove_query_arg('message', add_query_arg(self::$_redirectPostLocationArgs, $location));
    }

    protected function _saveTerm($term)
    {
        if ($this->_saving) return;

        // Use previous entity if set since save_post action is called after insert and the entity is not the previous one anymore
        $entity = $this->_oldTermEntity;
        $submit_values = empty($_POST['drts']) ? [] : stripslashes_deep($_POST['drts']);
        $form = $this->_application->Form_Build($this->_getTermForm($term, $submit_values));
        if (!$form->submit($submit_values)) {
            if ($form->hasError()) {
                $this->_setSaveContentErrors($term, $form);
            }
            // Fetch values for reverting back
            $values = $entity->getProperties();
            // Change saving entity to the new one otherwise values are not saved because unmodified
            $entity = new EntityType\TermEntity($term);
        }
        $values = $form->values;

        // Update the term, which will fire edited_term so use $this->_saving to prevent loops
        $this->_saving = true;
        $this->_application->Entity_Save($entity, $values);
        $this->_saving = false;
    }

    public function editTermsAction($termId, $taxonomy)
    {
        $this->_oldTermEntity = null;
        if (!isset($this->_taxonomies[$taxonomy])
            || (!$term = get_term_by('id', $termId, $taxonomy))
        ) return;

        $this->_oldTermEntity = new EntityType\TermEntity($term);
    }

    public function editedTermAction($termId, $ttId, $taxonomy)
    {
        if (!$this->_getTaxonomy() // make sure on edit term page
            || !isset($this->_taxonomies[$taxonomy])
            || !isset($this->_oldTermEntity)
            || (!$term = get_term_by('id', $termId, $taxonomy))
        ) return;

        $this->_saveTerm($term);

        //if (is_taxonomy_hierarchical($taxonomy)
        //    && (int)$term->parent !== (int)$this->_oldTermEntity->getParentId() // parent term has changed
        //) {
        //    $this->_updateEntitiesByTerm($termId, $taxonomy);
        //}
    }

    public function deleteTermAction($termId, $ttId, $taxonomy, $deletedTerm)
    {
        if (!isset($this->_taxonomies[$taxonomy])) return;

        $this->_application->Entity_TaxonomyTerms_clearCache($taxonomy);

        // Delete field cache
        $this->_application->Entity_FieldCache_remove('term', [$termId]);

        //if (is_taxonomy_hierarchical($taxonomy)) {
        //    $this->_updateEntitiesByTerm($termId, $taxonomy);
        //}
    }

    protected function _updateEntitiesByTerm($termId, $taxonomy, $deleted = true)
    {
        // Re-save posts that belong to the edited term so the parent term entries in db for the posts are updated
        $taxonomy_type = $this->_taxonomies[$taxonomy];
        foreach ($this->_application->Entity_Query('post')->taxonomyTermIdIs($taxonomy_type, $termId, true)->fetch() as $entity) {
            // Save without mofidication check so that parent term entries are updated
            $extra_args = array('skip_is_modified_check' => true);
            if ($deleted) {
                $extra_args['taxonomy_terms_deleted'][$this->_taxonomies[$taxonomy]] = array($termId);
            }
            $this->_application->Entity_Save($entity, [], $extra_args);
        }
    }

    public function restrictManagePostsAction()
    {
        global $pagenow, $typenow;
        if ($pagenow != 'edit.php'
            || !isset($this->_postTypes[$typenow])
            || (!$bundle = $this->_application->Entity_Bundle($typenow))
        ) return;

        if (!empty($bundle->info['taxonomies'])) {
            foreach ($this->_application->Entity_Bundles($bundle->info['taxonomies']) as $taxonomy) {
                if (!$this->_application->isComponentLoaded($taxonomy->component)
                    || empty($taxonomy->info['is_hierarchical'])
                ) continue;

                wp_dropdown_categories(array(
                    'show_option_all' => $taxonomy->getLabel('all'),
                    'hierarchical' => true,
                    'name' => 'drts_term[' . $taxonomy->name . ']',
                    'taxonomy' => $taxonomy->name,
                    'orderby' => 'name',
                    'selected' => !empty($_GET[$taxonomy->name]) && ($term = get_term_by('slug', $_GET[$taxonomy->name], $taxonomy->name)) ? $term->term_id : 0,
                    'hide_empty' => true,
                    'show_count' => true,
                    'hide_if_empty' => true,
                ));
            }
        }

        foreach ($this->_application->Entity_Field($bundle->name) as $field) {
            if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IRestrictable
                || (!$options = $field_type->fieldRestrictableOptions($field))
            ) continue;

            $name = 'drts_' . $field->getFieldName();
            $selected = isset($_GET[$name]) ? $_GET[$name] : '';
            if (!isset($options[''])) {
                $options = array('' => sprintf(__('Select %s', 'directories'), $field->getFieldLabel())) + $options;
            }
            $html = array('<select name="' . $name . '">');
            foreach (array_keys($options) as $option_key) {
                $_selected = $selected && $selected == $option_key ? ' selected="selected"' : '';
                $html[] = '<option value="' . $this->_application->H($option_key) . '"' . $_selected . '>' . $this->_application->H($options[$option_key]) . '</option>';
            }
            $html[] = '</select>';
            echo implode(PHP_EOL, $html);
        }
    }

    public function parseQueryFilter($query)
    {
        global $pagenow;
        if ($pagenow == 'edit.php'
            && isset($GLOBALS['typenow'])
            && isset($this->_postTypes[$GLOBALS['typenow']])
        ) {
            if (!empty($_GET['drts_term'])
                && is_array($_GET['drts_term'])
            ) {
                foreach ($_GET['drts_term'] as $taxonomy => $term_id) {
                    if ($this->_application->Entity_Bundle($taxonomy)
                        && ($term = get_term_by('id', $term_id, $taxonomy))
                    ) {
                        $query->set($taxonomy, $term->slug);
                        $_GET[$taxonomy] = $term->slug; // required for dropdown menu
                    }
                }
            }
            if (isset($_GET['drts_wp_post_parent'])
                && ($post_parent = (int)$_GET['drts_wp_post_parent'])
            ) {
                $query->set('post_parent', $post_parent);
            }

            foreach ($this->_application->Entity_Field($GLOBALS['typenow']) as $field) {
                $name = 'drts_' . $field->getFieldName();
                if (empty($_GET[$name])
                    || (!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IRestrictable
                    || (!$options = $field_type->fieldRestrictableOptions($field))
                    || !array_key_exists($_GET[$name], $options)
                    || (false === $restrict = $field_type->fieldRestrictableRestrict($field, $_GET[$name]))
                ) continue;

                $restrict += array(
                    'column' => 'value',
                    'value' => $_GET[$name],
                    'compare' => '=',
                );
                $query->set('meta_query', array(
                    array(
                        'key' => '_' . $name . '.' . $restrict['column'] ,
                        'value' => $restrict['value'],
                        'compare' => $restrict['compare'],
                    ),
                ));
            }
        }
        return $query;
    }

    public function beforeDeletePostAction($postId)
    {
        if ((!$post = get_post($postId))
            || !isset($this->_postTypes[$post->post_type])
        ) return;

        $entity_ids = [$postId];

        // Delete fields only since deleting actual post is done by wp_delete_post
        $this->_application->getComponent('Entity')
            ->deleteEntities('post', array(new EntityType\PostEntity($post)), array('fields_only' => true));

        if (!empty($this->_postTypes[$post->post_type]['children'])) {
            foreach ($this->_postTypes[$post->post_type]['children'] as $child_post_type) {
                foreach (get_children(array('post_parent' => $post->ID, 'post_type' => $child_post_type, 'post_status' => 'any')) as $child_post) {
                    wp_delete_post($child_post->ID);
                    $entity_ids[] = $child_post->ID;
                }
                foreach (get_children(array('post_parent' => $post->ID, 'post_type' => $child_post_type, 'post_status' => 'trash')) as $child_post) {
                    wp_delete_post($child_post->ID);
                    $entity_ids[] = $child_post->ID;
                }
            }
        }

        // Delete field cache
        $this->_application->Entity_FieldCache_remove('post', $entity_ids);
    }

    public function postRowActionsFilter($actions, $post)
    {
        if (isset($this->_postTypes[$post->post_type])) {
            if (!in_array($post->post_status, array('publish', 'private'))
                || ($post->post_parent && !in_array(get_post_status($post->post_parent), array('publish', 'private')))
            ) {
                unset($actions['view']);
            }
        }

        return $actions;
    }

    public function managePostsColumnsFilter($columns)
    {
        global $typenow;
        if (isset($this->_postTypes[$typenow])) {
            $this->_maybeAddThumbnailColumn($typenow, $columns);

            // Add parent/child entity columns
            if (isset($this->_postTypes[$typenow]['parent'])) {
                $columns['drts_wp_post_parent'] = $this->_application->Entity_Bundle($this->_postTypes[$typenow]['parent'])->getLabel('singular');
            }
            if (!empty($this->_postTypes[$typenow]['children'])
                && ($bundle = $this->_application->Entity_Bundle($typenow))
                && ($child_bundle_types = (array)$this->_application->Entity_BundleTypes_children($bundle->type, false))
            ) {
                foreach ($child_bundle_types as $child_bundle_type) {
                    if (($child_bundle = $this->_application->Entity_Bundle($child_bundle_type, $bundle->component, $bundle->group))
                        && in_array($child_bundle->name, $this->_postTypes[$typenow]['children'])
                    ) {
                        $columns['drts_wp_post_child_' . $child_bundle->name] = $child_bundle->getLabel();
                    }
                }
            }

            foreach ($this->_application->Entity_Field($typenow) as $field) {
                if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IColumnable
                    || (!$info = $field_type->fieldColumnableInfo($field))
                ) continue;

                if (isset($info['icon'])) {
                    $label = '<i class="drts-' . $this->_application->H($info['icon']) . '" title="' . $this->_application->H($info['label']) . '"></i>';
                } else {
                    $label = $this->_application->H($info['label']);
                }
                $columns['drts_field_' . $field->getFieldName()] = $label;
            }

            // Move comments and date columns to last
            foreach (array('comments', 'date') as $column_name) {
                if (!isset($columns[$column_name])) continue;

                $column_label = $columns[$column_name];
                unset($columns[$column_name]);
                $columns[$column_name] = $column_label;
            }
        }
        return $columns;
    }

    public function managePostsCustomColumnAction($column, $postId)
    {
        global $typenow;
        if (!isset($this->_postTypes[$typenow])) return;

        if ($this->_maybeDisplayThumbnailColumn($typenow, $postId, $column)) return;

        // Display parent/child entity columns
        if ($column === 'drts_wp_post_parent') {
            if ($parent_id = wp_get_post_parent_id($postId)) {
                echo '<a href="' . admin_url('edit.php?post_type=' . $typenow . '&drts_wp_post_parent=' . $parent_id) . '">'
                    . esc_html(get_the_title($parent_id)) . '</a>';
            }
            return;
        }
        if (strpos($column, 'drts_wp_post_child_') === 0) {
            if (empty($this->_postTypes[$typenow]['children'])) return;

            foreach ($this->_postTypes[$typenow]['children'] as $child_post_type) {
                if ($column === 'drts_wp_post_child_' . $child_post_type) {
                    if (($child_bundle = $this->_application->Entity_Bundle($child_post_type))
                        && ($entity = $this->_application->Entity_Entity('post', $postId))
                    ) {
                        if ($count = (int)$entity->getSingleFieldValue('entity_child_count', $child_bundle->type)) {
                            echo '<a href="' . admin_url('edit.php?post_type=' . $child_post_type . '&drts_wp_post_parent=' . $postId) . '">' . $count . '</a>';
                        } else {
                            echo 0;
                        }
                    }
                    break;
                }
            }
            return;
        }

        if (strpos($column, 'drts_field_') === 0) {
            $field_name = substr($column, strlen('drts_field_'));
            if ((!$field = $this->_application->Entity_Field($typenow, $field_name))
                || (!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IColumnable
                || (!$entity = $this->_application->Entity_Entity('post', $postId))
            ) return;

            echo '<div class="drts">' . $field_type->fieldColumnableColumn($field, $entity->getFieldValue($field_name)) . '</div>';
        }
    }

    public function postsClausesFilter($clauses, $query)
    {
        global $pagenow, $typenow, $wpdb;
        if ($pagenow === 'edit.php'
            && isset($this->_postTypes[$typenow])
            && !empty($_GET['orderby'])
            && strpos($_GET['orderby'], 'drts_field_') === 0
        ) {
            $field_name = substr($_GET['orderby'], strlen('drts_field_'));
            if (($field = $this->_application->Entity_Field($typenow, $field_name))
                && ($field_type = $this->_application->Field_Type($field->getFieldType(), true))
                && $field_type instanceof \SabaiApps\Directories\Component\Field\Type\IColumnable
                && ($info = $field_type->fieldColumnableInfo($field))
                && isset($info['sortby'])
            ) {
                $clauses['join'] .= sprintf(
                    ' LEFT JOIN %1$sdrts_entity_field_%2$s %3$s ON %1$sposts.ID = %3$s.entity_id AND %3$s.field_name = \'%4$s\'',
                    $wpdb->prefix,
                    $field->getFieldType(),
                    $field_name,
                    esc_sql($field_name)
                );
                $clauses['orderby'] = $field_name . '.' . $info['sortby'] . ' ' . ($_GET['order'] === 'asc' ? 'ASC' : 'DESC');
            }
        }

        return $clauses;
    }

    public function manageEditSortableColumnsFilter($columns)
    {
        global $typenow;
        if (isset($this->_postTypes[$typenow])
            && ($bundle = $this->_application->Entity_Bundle($typenow))
        ) {
            foreach ($this->_application->Entity_Field($bundle) as $field) {
                if ((!$field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof \SabaiApps\Directories\Component\Field\Type\IColumnable
                    || (!$info = $field_type->fieldColumnableInfo($field))
                    || !isset($info['sortby'])
                ) continue;

                $orderby = 'drts_field_' . $field->getFieldName();
                $columns[$orderby] = $orderby;
            }
        }

        return $columns;
    }

    public function manageTaxonomyColumnsFilter($columns)
    {
        if ($taxonomy = $this->_getTaxonomy()) {
            $this->_maybeAddThumbnailColumn($taxonomy, $columns);
        }
        return $columns;
    }

    public function manageTaxonomyCustomColumnAction($row, $column, $termId)
    {
        if ($taxonomy = $this->_getTaxonomy()) {
            $this->_maybeDisplayThumbnailColumn($taxonomy, $termId, $column);
        }
    }

    protected function _maybeAddThumbnailColumn($bundleName, array &$columns, $after = null)
    {
        if ((!$bundle = $this->_application->Entity_Bundle($bundleName))
            || (empty($bundle->info['entity_image'])
                && empty($bundle->info['entity_icon']))
        ) return;

        $thumb_column = '<i class="drts-far fa-image" title="' . __('Thumbnail', 'directories') . '"></i>';
        $column_name = empty($bundle->info['entity_image']) ? 'drts_entity_icon' : 'drts_entity_image';
        if (isset($after)) {
            $new_columns = [];
            foreach (array_keys($columns) as $key) {
                $new_columns[$key] = $columns[$key];
                if ($key === 'title' || $key === 'name') {
                    $new_columns[$column_name] = $thumb_column;
                    $new_columns += $columns;
                    break;
                }
            }
            $columns = $new_columns;
        } else {
            $columns[$column_name] = $thumb_column;
        }
    }

    protected function _maybeDisplayThumbnailColumn($bundleName, $entityId, $column)
    {
        if (!in_array($column, ['drts_entity_image', 'drts_entity_icon'])) return;

        if (($bundle = $this->_application->Entity_Bundle($bundleName))
            && ($entity = $this->_application->Entity_Entity($bundle->entitytype_name, $entityId))
        ) {
            if ($column === 'drts_entity_image') {
                if ($url = $this->_application->Entity_Image($entity, 'icon')) {
                    echo '<div class="drts"><img class="drts-icon" src="' . $this->_application->H($url) . '" alt="" /></div>';
                }
            } elseif ($column === 'drts_entity_icon') {
                if ($icon = $this->_application->Entity_Icon($entity, false)) {
                    if ($color = $this->_application->Entity_Color($entity)) {
                        $style = ' style="background-color:' . $this->_application->H($color) .  ';color:#fff;"';
                    } else {
                        $style = '';
                    }
                    echo '<div class="drts"><i class="drts-icon ' . $this->_application->H($icon) . '"' . $style . ' /></div>';
                }
            }
        }
        return true;
    }

    /**
     * Our plugin modifies the status of a post to auto-draft when there was an error adding the post in the backend.
     * Therefore we need to prevent those auto-draft posts to be associated with original posts as translations in WPML
     * since for some reason auto-draft posts do not show up as associated however they do get linked from original posts.
     */
    public function wpmlAfterSavePostAction($postId, $trid, $languageCode, $sourceLanguage)
    {
        if ((!$post = get_post($postId))
            || !isset($this->_postTypes[$post->post_type]) // not our post type
            || $post->post_status !== 'auto-draft'
        ) return;

        // Remove association with original post
        $GLOBALS['sitepress']->delete_element_translation($trid, 'post_' . $post->post_type, $languageCode);
    }

    public function onDisplayPostStatesFilter($postStates, $post)
    {
        $page_slugs = $this->_application->getPlatform()->getPageSlugs();
        if (!empty($page_slugs[2])
            && ($slug = array_keys($page_slugs[2], $post->ID)) // is drts page?
        ) {
            foreach ($slug as $_slug) {
                foreach (array_keys($page_slugs[1]) as $component) {
                    if ($slug_name = array_search($_slug, $page_slugs[1][$component])) {
                        $slugs = $this->_application->System_Slugs($component);
                        if (isset($slugs[$slug_name])) {
                            $postStates['-sabia_app_name-_page_' . $slug_name] = $slugs[$slug_name]['admin_title'];
                        }
                        break;
                    }
                }
            }
        }
        return $postStates;
    }
}
