<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Framework\User\User;
use SabaiApps\Framework\User\RegisteredIdentity;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\MainRoutingController;
use SabaiApps\Directories\AdminRoutingController;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Platform\AbstractPlatform;
use Monolog\Logger;
use Monolog\Handler\ErrorLogHandler;

class Platform extends AbstractPlatform
{
    const VERSION = '1.2.12';
    private $_mainContent, $_singlePageId, $_singlePageContent, $_userToBeDeleted,
        $_jqueryUiCoreLoaded, $_jqueryUiCssLoaded,
        $_moLoaded, $_i18n, $_flash = [], $_bsHandle, $_flushRewriteRules, $_pluginsUrl;
    private static $_instance;

    protected function __construct()
    {
        parent::__construct('WordPress');
        if (!defined('DRTS_WORDPRESS_SESSION_TRANSIENT')) {
            define('DRTS_WORDPRESS_SESSION_TRANSIENT', true);
        }
        if (DRTS_WORDPRESS_SESSION_TRANSIENT && !defined('DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME')) {
            define('DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME', 10800);
        }
        if (!defined('DRTS_WORDPRESS_ADMIN_CAPABILITY')) {
            define('DRTS_WORDPRESS_ADMIN_CAPABILITY', 'delete_users');
        }
        if (!defined('DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK')) {
            define('DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK', false);
        }
        if (defined('WPML_PLUGIN_BASENAME')) {
            $this->_i18n = 'wpml';
        //} elseif (defined('POLYLANG_VERSION')) {
        //    $this->_i18n = 'polylang';
        }
    }

    /**
     * @return Platform
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) self::$_instance = new self();

        return self::$_instance;
    }

    public function getRouteParam()
    {
        return 'q';
    }

    public function getI18n()
    {
        return $this->_i18n;
    }

    public function hasBootstrapCss()
    {
        return !$this->isAdmin() && $this->_getBootstrapHandle();
    }

    protected function _getBootstrapHandle()
    {
        if (!isset($this->_bsHandle)) {
            $this->_bsHandle = apply_filters('drts_bootstrap_handle', false);
        }
        return $this->_bsHandle;
    }

    public function getPageParam()
    {
        return defined('DRTS_WORDPRESS_PAGE_PARAM') ? DRTS_WORDPRESS_PAGE_PARAM : '_page';
    }

    public function getUserIdentityFetcher()
    {
        return UserIdentityFetcher::getInstance();
    }

    public function getCurrentUser()
    {
        $wp_user = wp_get_current_user();
        if ($wp_user->ID == 0) return false;

        $identity = new RegisteredIdentity(array(
            'id' => $wp_user->ID,
            'username' => $wp_user->user_login,
            'url' => $wp_user->user_url,
            'email' => $wp_user->user_email,
            'name' => $wp_user->display_name,
            'created' => strtotime($wp_user->user_registered),
        ));

        return new User($identity);
    }

    public function isAdministrator($userId = null)
    {
        if (!isset($userId)) $userId = get_current_user_id();

        return is_super_admin($userId)
            || user_can($userId, DRTS_WORDPRESS_ADMIN_CAPABILITY)
            || user_can($userId, 'manage_directories');
    }

    public function getAdministrators()
    {
        $ret = [];
        foreach ($this->getAdministratorRoles() as $role_name) {
            foreach (get_users(array('role' => $role_name)) as $user) {
                if (!isset($ret[$user->ID])) {
                    $ret[$user->ID] = new RegisteredIdentity(array(
                        'id' => $user->ID,
                        'username' => $user->user_login,
                        'name' => $user->display_name,
                        'email' => $user->user_email,
                        'url' => $user->user_url,
                        'created' => strtotime($user->user_registered),
                    ));
                }
            }
        }

        return $ret;
    }

    public function getAdministratorRoles()
    {
        global $wp_roles;

        if (!isset($wp_roles)) $wp_roles = new \WP_Roles();
        $ret = [];
        foreach($wp_roles->role_objects as $role_name => $role) {
            if (!$role->has_cap(DRTS_WORDPRESS_ADMIN_CAPABILITY)
                && !$role->has_cap('manage_directories')
            ) continue;

            $ret[$role_name] = $role_name;
        }

        return $ret;
    }

    public function getPermissions($userId)
    {
        $perms = [];
        if ($data = get_userdata($userId)) {
            $prefix_len = strlen('drts_');
            foreach (array_keys($data->allcaps) as $cap) {
                if (strpos($cap, 'drts_') === 0) {
                    $perms[] = substr($cap, $prefix_len);
                }
            }
        }
        return $perms;
    }

    public function hasPermission($userId, $permission)
    {
        return user_can($userId, 'drts_' . $permission);
    }

    public function guestHasPermission($permission)
    {
        return ($guest_perms = $this->getOption('guest_permissions')) ? !empty($guest_perms['drts_' . $permission]) : false;
    }

    public function getWriteableDir()
    {
        $upload_dir = wp_upload_dir();
        $ret = $upload_dir['basedir'] . '/drts';
        if (is_multisite() && $GLOBALS['blog_id'] != 1) {
            $ret .= '/sites/' . $GLOBALS['blog_id'];
            if (!is_dir($ret)) {
                if (!@mkdir($ret, 0755, true)) {
                    // $this->logError('Failed creating directory ' . $ret);
                }
            }
        }
        return $ret;
    }

    public function getSitePath()
    {
        return rtrim(ABSPATH, '/');
    }

    public function getSiteName()
    {
        return get_option('blogname');
    }

    public function getSiteVersion()
    {
        return get_bloginfo('version');
    }

    public function getSiteEmail()
    {
        return get_option('admin_email');
    }

    public function getSiteUrl()
    {
        return home_url();
    }

    public function getSiteAdminUrl()
    {
        return rtrim(admin_url(), '/');
    }

    public function getPackagePath()
    {
        return Loader::pluginsDir();
    }

    public function getPackages()
    {
        $plugins = $this->getSabaiPlugins(true);
        return array_keys($plugins);
    }

    public function getPackageVersion($package)
    {
        return $this->getPluginData($package, 'Version', '0.0.0');
    }

    public function getAssetsUrl($package = null, $vendor = false)
    {
        if (!isset($this->_pluginsUrl)) $this->_pluginsUrl = plugins_url();
        $url = $this->_pluginsUrl . '/' . (isset($package) ? $package : Loader::plugin()) . '/assets';
        if ($vendor) $url .= '/vendor';
        return $url;
    }

    public function getAssetsDir($package = null, $vendor = false)
    {
        $dir = $this->getPackagePath() . '/' . (isset($package) ? $package : Loader::plugin()) . '/assets';
        if ($vendor) $dir .= '/vendor';
        return $dir;
    }

    public function getLoginUrl($redirect)
    {
        return wp_login_url($redirect);
    }

    public function getLogoutUrl()
    {
        return wp_logout_url();
    }

    public function getRegisterUrl($redirect = '')
    {
        $url = rtrim(wp_registration_url(), '&');
        if ($redirect !== '') {
            $url .= strpos($url, '?') ? '&' : '?';
            $url .= esc_url_raw($redirect);
        }
        return $url;
    }

    public function getLostPasswordUrl($redirect = '')
    {
        return wp_lostpassword_url($redirect);
    }

    public function getLoginForm()
    {
        return array(
            'username' => array(
                '#type' => 'textfield',
                '#placeholder' => __('Username', 'directories'),
                '#field_prefix' => '<i class="fas fa-fw fa-user"></i>',
                '#weight' => 1,
            ),
            'password' => array(
                '#type' => 'password',
                '#placeholder' => __('Password', 'directories'),
                '#field_prefix' => '<i class="fas fa-fw fa-lock"></i>',
                '#weight' => 3,
            ),
            'extra' => array(
                '#type' => 'markup',
                // Use pre-render callback to prevent from register_form hook to be invoked on submit
                '#pre_render' => [function ($form, &$data) {
                    ob_start();
                    do_action('login_form');
                    if ($extra = ob_get_clean()) $data['#markup'] = $extra;
                }],
                '#weight' => 5,
            ),
            'remember' => array(
                '#type' => 'checkbox',
                '#title' => __('Remember Me', 'directories'),
                '#switch' => false,
                '#weight' => 10
            ),
        );
    }

    public function getRegisterForm()
    {
        return array(
            '#attributes' => array('name' => 'registerform'), // some plugins require this
            'username' => array(
                '#type' => 'textfield',
                '#placeholder' => __('Username', 'directories'),
                '#field_prefix' => '<i class="fas fa-fw fa-user"></i>',
                '#weight' => 1,
            ),
            'email' => array(
                '#type' => 'email',
                '#placeholder' => __('E-mail Address', 'directories'),
                '#field_prefix' => '<i class="fas fa-fw fa-envelope"></i>',
                '#weight' => 3,
            ),
            'extra' => array(
                '#type' => 'markup',
                // Use pre-render callback to prevent from register_form hook to be invoked on submit
                '#pre_render' => [function ($form, &$data) {
                    ob_start();
                    do_action('register_form');
                    if ($extra = ob_get_clean()) $data['#markup'] = $extra;
                }],
                '#weight' => 5,
            ),
        );
    }

    public function isUserRegisterable()
    {
        return get_option('users_can_register');
    }

    public function registerUser(array $values)
    {
        $username = trim((string)@$values['username']);
        if (!strlen($username)) {
            throw new Exception\RuntimeException(__('Username is required.', 'directories'));
        }
        $email = trim((string)@$values['email']);
        if (!strlen($email)) {
            throw new Exception\RuntimeException(__('E-mail address is required.', 'directories'));
        }
        if (username_exists($username)) {
            throw new Exception\RuntimeException(__('The username is already taken.', 'directories'));
        }
        if (email_exists($email)) {
            throw new Exception\RuntimeException(__('The e-mail address is already taken.', 'directories'));
        }

        $user_id = register_new_user($username, $email);
        if (is_wp_error($user_id)) {
            throw new Exception\RuntimeException($user_id->get_error_message());
        }
        return $user_id;
    }

    public function loginUser(array $credentials)
    {
        $username = trim((string)@$credentials['username']);
        if (!strlen($username)) {
            throw new Exception\RuntimeException(__('Username is required.', 'directories'));
        }
        $password = trim((string)@$credentials['password']);
        if (!strlen($password)) {
            throw new Exception\RuntimeException(__('Password is required.', 'directories'));
        }
        $user = wp_signon(array(
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => !empty($credentials['remember'])
        ));
        if (is_wp_error($user)) {
            throw new Exception\RuntimeException($user->get_error_message());
        }
        return $user->ID;
    }

    public function getPrivacyPolicyLink()
    {
        return function_exists('get_the_privacy_policy_link') ? get_the_privacy_policy_link() : null;
    }

    public function setCurrentUser($userId)
    {
        if (!$user = get_user_by('id', $userId)) return false;

        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID);
        do_action('wp_login', $user->user_login, $user);
        return true;
    }

    public function getHomeUrl()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_home_url', home_url());
            //case 'polylang':
            //    if (function_exists('pll_home_url')) return pll_home_url();
            default:
                return home_url();
        }
    }

    public function getDB()
    {
        return new DB($GLOBALS['wpdb']);
    }

    public function mail($to, $subject, $body, array $options = [])
    {
        $options += array(
            'from' => $this->getSiteName(),
            'from_email' => $this->getSiteEmail(),
            'attachments' => [],
            'headers' => [],
        );

        $options['headers'][] = sprintf('From: %s <%s>', $options['from'], $options['from_email']);

        // Attachments?
        if (!empty($options['attachments'])) {
            foreach (array_keys($options['attachments']) as $i) {
                // wp_mail() accepts file path only
                $options['attachments'][$i] = $options['attachments'][$i]['path'];
            }
        }

        if (!empty($options['is_html'])) {
            add_filter('wp_mail_content_type', array($this, 'onWpMailContentType'));
        }

        wp_mail($to, $subject, $body, $options['headers'], $options['attachments']);

        if (!empty($options['is_html'])) {
            remove_filter('wp_mail_content_type', array($this, 'onWpMailContentType'));
        }

        return $this;
    }

    public function onWpMailContentType()
    {
        return 'text/html';
    }

    protected function _getGuestId()
    {
        return Request::ip() . Request::userAgent();
    }

    public function addCss($css, $targetHandle = null)
    {
        wp_add_inline_style(isset($targetHandle) ? $targetHandle : 'drts', $css);
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addCss($css, $targetHandle);
        }
        return $this;
    }

    public function setSessionVar($name, $value, $userId = null)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (isset($userId)) {
                if (empty($userId)) $userId = $this->_getGuestId();

                $name .= ':' . $userId;
            }
            $this->setCache($value, 'session_' . $name, DRTS_WORDPRESS_SESSION_TRANSIENT_LIFETIME);
        } else {
            $_SESSION['drts'][$name] = $value;
        }
        return $this;
    }

    public function getSessionVar($name, $userId = null)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (isset($userId)) {
                if (empty($userId)) $userId = $this->_getGuestId();

                $name .= ':' . $userId;
            }
            $ret = $this->getCache('session_' . $name);
            return $ret === false ? null : $ret;
        }
        return isset($_SESSION['drts'][$name])
            ? $_SESSION['drts'][$name]
            : null;
    }

    public function deleteSessionVar($name, $userId = null)
    {
        $name = $GLOBALS['wpdb']->prefix . $name;
        if (DRTS_WORDPRESS_SESSION_TRANSIENT) {
            if (isset($userId)) {
                if (empty($userId)) $userId = $this->_getGuestId();

                $name .= ':' . $userId;
            }
            $this->deleteCache('session_' . $name);
        } else {
            if (isset($_SESSION['drts'][$name])) {
                unset($_SESSION['drts'][$name]);
            }
        }

        return $this;
    }

    public function setUserMeta($userId, $name, $value)
    {
        update_user_meta($userId, $GLOBALS['wpdb']->prefix . 'drts_' . $name, $value);

        return $this;
    }

    public function getUserMeta($userId, $name, $default = null)
    {
        $ret = get_user_meta($userId, $GLOBALS['wpdb']->prefix . 'drts_' . $name, true);
        return $ret === '' ? $default : $ret;
    }

    public function deleteUserMeta($userId, $name)
    {
        delete_user_meta($userId, $GLOBALS['wpdb']->prefix . 'drts_' . $name);

        return $this;
    }

    public function getUsersByMeta($name, $limit = 20, $offset = 0, $order = 'DESC', $isNumber = true)
    {
        $query = new \WP_User_Query(array(
            'meta_key' => $meta_key = $GLOBALS['wpdb']->prefix . 'drts_' . $name,
            'orderby' => $isNumber ? 'meta_value_num' : 'meta_value',
            'order' => $order,
            'number' => $limit,
            'offset' => $offset,
        ));
        $ret = [];
        if (!empty($query->results)) {
            foreach ($query->results as $user) {
                $ret[$user->ID] = new RegisteredIdentity(array(
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'url' => $user->user_url,
                    'created' => strtotime($user->user_registered),
                    $name => $user->get($meta_key),
                ));
            }
        }
        return $ret;
    }

    public function getCache($id, $group = 'settings')
    {
        return get_transient($this->_getCacheId($id, $group));
    }

    public function setCache($data, $id, $lifetime = null, $group = 'settings')
    {
        // Always set expiration to prevent this cache data from being autoloaded on every request by WP.
        // Lifetime can be set to 0 to never expire but the value will be autoloaded on every request.
        if (!isset($lifetime)) $lifetime = 604800;

        set_transient($this->_getCacheId($id, $group), $data, $lifetime);

        return $this;
    }

    public function deleteCache($id, $group = 'settings')
    {
        delete_transient($this->_getCacheId($id, $group));

        return $this;
    }

    protected function _getCacheId($id, $group)
    {
        return 'drts_' . (strlen($group) ? '_' . $group . '__' : '') . $id;
    }

    public function clearCache($group = null, $name = null)
    {
        global $wpdb;
        $prefix = '';
        if (strlen($group)) {
            $prefix .= '_' . $group . '__';
        }
        if (strlen($name)) {
            $prefix .= $name;
        }
        $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'_transient_drts_' . $prefix . '%\'');
        $wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE \'_transient_timeout_drts_' . $prefix . '%\'');

        // Clear object cache
        if (strlen($name) && function_exists('wp_cache_flush')) wp_cache_flush();

        return $this;
    }

    public function getLocale()
    {
        return get_locale();
    }

    public function isRtl()
    {
        return is_rtl();
    }

    public function htmlize($text, $inlineTagsOnly = false, $forCaching = false)
    {
        if (!strlen($text)) return '';

        if ($inlineTagsOnly) {
            $tags = [
                'a' => ['title' => true, 'href' => true, 'target' => true],
                'abbr' => ['title' => true],
                'acronym' => ['title' => true],
                'b' => [],
                'cite' => [],
                'code' => [],
                'del' => ['datetime' => true],
                'em' => [],
                'i' => [],
                'q' => ['cite' => true],
                's' => [],
                'strike' => [],
                'strong' => [],
            ];
            $text = wp_kses($text, $tags);
        } else {
            if ($inlineTagsOnly === false) {
                $text = wp_kses_post($text);
            }
        }
        $text = balanceTags($text, true);
        if (!isset($tags)) {
            if (!class_exists('\WP_Embed', false)) {
                include ABSPATH . WPINC . '/class-wp-embed.php';
            }
            if (isset($GLOBALS['wp_embed'])) {
                $text = $GLOBALS['wp_embed']->autoembed($text);
            }
            $text = make_clickable($text);
        } elseif (isset($tags['a'])) {
            $text = make_clickable($text);
        }
        $text = wptexturize($text);
        $text = convert_smilies($text);
        $text = convert_chars($text);
        if (!isset($tags)
            || (isset($tags['p']) && isset($tags['br']))
        ) {
            $text = wpautop($text);
            $text = shortcode_unautop($text);
        }
        // Process shortcodes if not caching
        if (!$forCaching) {
            $text = $this->doShortcode($text);
        }
        return $text;
    }

    public function doShortcode($text)
    {
        // Need to manually convert [embed] shortcode
        if (strpos($text, '[/embed]') !== false) {
            if (!class_exists('\WP_Embed', false)) {
                include ABSPATH . WPINC . '/class-wp-embed.php';
            }
            $text = $GLOBALS['wp_embed']->run_shortcode($text);
        }
        return do_shortcode($text);
    }

    public function getCookieDomain()
    {
        return COOKIE_DOMAIN;
    }

    public function getCookiePath()
    {
        return COOKIEPATH;
    }

    public function setOption($name, $value, $autoload = true)
    {
        update_option('drts_' . strtolower($name), $value, $autoload);
        return $this;
    }

    public function getOption($name, $default = null)
    {
        return get_option('drts_' . strtolower($name), $default);
    }

    public function deleteOption($name)
    {
        delete_option('drts_' . strtolower($name));
        return $this;
    }

    public function clearOptions($prefix = '')
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . $wpdb->options. ' WHERE option_name LIKE %s', 'drts_' . $prefix . '%'));
        return $this;
    }

    public function getDateFormat()
    {
        return get_option('date_format');
    }

    public function getTimeFormat()
    {
        return get_option('time_format');
    }

    public function getDate($format, $timestamp, $isUTC = true)
    {
        if ($isUTC) $timestamp += get_option('gmt_offset') * 3600;
        return date_i18n($format, $timestamp);
    }

    public function getStartOfWeek()
    {
        return ($ret = (int)get_option('start_of_week')) ? $ret : 7;
    }

    public function getTimeZone()
    {
        if (!$ret = get_option('timezone_string')) {
            if (!$gmt_offset = get_option('gmt_offset')) {
                $gmt_offset = 0;
            }
            $ret = timezone_name_from_abbr('', (int)$gmt_offset * 3600, 0);
        }
        return $ret ?: null;
    }

    public function getCustomAssetsDir()
    {
        if (false === $ret = $this->getCache('wordpress_assets_dir')) {
            $ret = [];
            foreach (array(TEMPLATEPATH  . '/drts', WP_CONTENT_DIR . '/drts/assets', STYLESHEETPATH . '/drts') as $dir) {
                if (is_dir($dir) && !in_array($dir, $ret)) {
                    $ret[] = $dir;
                }
            }
            $this->setCache($ret = apply_filters('drts_assets_dir', $ret), 'wordpress_assets_dir', 0);
        }
        return $ret;
    }

    public function getCustomAssetsDirUrl($index = null)
    {
        if (false === $ret = $this->getCache('wordpress_assets_dir_url')) {
            $ret = [];
            foreach ($this->getCustomAssetsDir() as $dir) {
                if ($dir === TEMPLATEPATH  . '/drts') {
                    $ret[] = get_template_directory_uri() . '/drts';
                } elseif ($dir === STYLESHEETPATH  . '/drts') {
                    $ret[] = get_stylesheet_directory_uri() . '/drts';
                } elseif ($dir === WP_CONTENT_DIR . '/drts/assets') {
                    $ret[] = WP_CONTENT_URL . '/drts/assets';
                }
            }
            $this->setCache($ret = apply_filters('drts_assets_dir_url', $ret), 'wordpress_assets_dir_url', 0);
        }
        return isset($index) ? $ret[$index] : $ret;
    }

    public function getUserProfileHtml($userId)
    {
        return nl2br(get_the_author_meta('description', $userId));
    }

    public function loadDefaultAssets($loadJs = true, $loadCss = true)
    {
        if ($loadJs) {
            $action = is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts';
            add_action($action, array($this, 'onWpEnqueueScripts'), 1);
            add_action($action, array($this, 'onWpEnqueueScriptsLast'), 99999);
        }
        if ($loadCss) {
            $action = is_admin() ? 'admin_print_styles' : 'wp_print_styles';
            add_action($action, array($this, 'onWpPrintStyles'), 99999);
        }

        return parent::loadDefaultAssets($loadJs, $loadCss);
    }

    public function run()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT) {
            Application::startSession(defined('DRTS_WORDPRESS_SESSION_PATH') ? DRTS_WORDPRESS_SESSION_PATH : null);
        }

        add_action('init', array($this, 'onInitAction'), 3); // earlier than most plugins
        add_action('admin_init', array($this, 'onAdminInitAction'));
        add_action('widgets_init', array($this, 'onWidgetsInitAction'));
        add_action('wp_login', array($this, 'onWpLoginAction'));
        add_action('wp_logout', array($this, 'onWpLogoutAction'));
        add_action('delete_user', array($this, 'onDeleteUserAction'));
        add_action('deleted_user', array($this, 'onDeletedUserAction'));

        if (is_admin()) {
            // Do not include WP admin header automatically if sabai admin page
            if (isset($_REQUEST['page']) && is_string($_REQUEST['page']) && 0 === strpos($_REQUEST['page'], 'drts')) {
                $_GET['noheader'] = 1;
            }

            add_action('admin_menu', array($this, 'onAdminMenuAction'));
            add_action('admin_notices', array($this, 'onAdminNoticesAction'));
            add_action('post_updated', array($this, 'onPostUpdatedAction'), 10, 3);
            add_action('activated_plugin', array($this, 'onActivatedPluginAction'));
            add_action('deactivated_plugin', array($this, 'onDeactivatedPluginAction'));
            add_action('upgrader_process_complete', array($this, 'onUpgraderProcessCompleteAction'), 10, 2);
            add_action('after_switch_theme', array($this, 'onAfterSwitchThemeAction'));
            add_filter('extra_plugin_headers', array($this, 'onExtraPluginHeadersFilter'));
            add_filter('network_admin_plugin_action_links', array($this, 'onNetworkAdminPluginActionLinks'), 10, 4);
            add_action('admin_head-widgets.php', array($this, 'onAdminHeadWidgetsPhpAction'));
        } else {
            add_filter('query_vars', array($this, 'onQueryVarsFilter'));

            // Add action method to run Sabai
            add_action('wp', array($this, 'onWpAction'), 1);
        }
    }

    public function onQueryVarsFilter($vars)
    {
        $vars[] = 'drts_route';
        $vars[] = 'drts_action';
        $vars[] = 'drts_pagename';
        $vars[] = 'drts_parent_pagename';
        $vars[] = 'drts_lang';
        return $vars;
    }

    public function getPageSlugs($lang = null)
    {
        if (!empty($lang)
            || ($lang !== false && ($lang = $this->getCurrentLanguage()))
        ) {
            if (false !== $slugs = $this->getOption('page_slugs_' . $lang, false)) {
                return $slugs;
            }
        }
        return $this->getOption('page_slugs', []);
    }

    public function setPageSlugs($slugs, $lang = null, $flush = true)
    {
        if (!empty($lang)
            || ($lang !== false && ($lang = $this->getCurrentLanguage()))
        ) {
            $this->setOption('page_slugs_' . $lang, $slugs);
            $this->deleteCache('wordpress_rewrite_rules_' . $lang);

            if ($lang !== $this->getDefaultLanguage()) return $this;
        }
        $this->setOption('page_slugs', $slugs);
        $this->flushRewriteRules($flush);

        return $this;
    }

    public function flushRewriteRules($flag = true)
    {
        $this->_flushRewriteRules = $flag;
    }

    public function hasSlug($component, $slug, $lang = null)
    {
        return ($page_slugs = $this->getPageSlugs($lang)) && isset($page_slugs[1][$component][$slug]) ? $page_slugs[1][$component][$slug] : false;
    }

    public function getSlug($component, $slug, $lang = null)
    {
        return ($_slug = $this->hasSlug($component, $slug, $lang)) ? $_slug : $slug;
    }

    public function getPermalinkConfig($lang = null)
    {
        $page_slugs = $this->getPageSlugs($lang);
        return empty($page_slugs[4]) ? [] : $page_slugs[4];
    }

    public function getTitle($component, $name, $lang = null)
    {
        if (($page_slugs = $this->getPageSlugs($lang))
            && ($slug = @$page_slugs[1][$component][$name])
            && ($page_id = @$page_slugs[2][$slug])
            && ($post = get_post($page_id))
        ) {
            return $post->post_title;
        }
        return parent::getTitle($component, $name, $lang);
    }

    private function _isSabaiPageId($id)
    {
        $page_slugs = $this->getPageSlugs();
        return !empty($page_slugs[2]) && ($slug = array_search($id, $page_slugs[2])) ? $slug : false;
    }

    protected function _isSabaiPage()
    {
        if (is_page()) {
            if ((!Request::isAjax()
                    && (!Request::isPostMethod() || !empty($_POST['_drts_form_build_id']))
                    && strpos($GLOBALS['post']->post_content, '[drts') !== false // using shortcode on page
                    && !get_query_var('drts_action') // make sure not on single post page
                )
                || (!$pagename = $this->_isSabaiPageId($GLOBALS['post']->ID))
            ) return false;

            if (!$route = get_query_var('drts_route')) return $pagename;

            if (strpos($route, $pagename) !== 0
                && strpos($route, '_drts') !== 0 // allow drts/* route since it may have a page when permalink structure is plain
            ) return false;

            return $route;
        }

        if (is_single() || is_tax()) {
            if (!$route = get_query_var('drts_route')) {
                // Using Plain permalink type, so get route from current object

                if (is_single()) {
                    $post_types = $this->getApplication()->getComponent('WordPressContent')->getPostTypes();
                    if (!isset($post_types[get_queried_object()->post_type])) return false; // Not a Sabai2 post type

                    $entity_type = 'post';
                } else {
                    $taxonomies = $this->getApplication()->getComponent('WordPressContent')->getTaxonomies();
                    if (!isset($taxonomies[get_queried_object()->taxonomy])) return false; // Not a Sabai2 taxonomy

                    $entity_type = 'term';
                }

                if ((!$entity = $this->getApplication()->Entity_Entity($entity_type, get_queried_object_id()))
                    || (!$bundle = $this->getApplication()->Entity_Bundle($entity))
                    || (!$bundle_permalink_path = $this->getApplication()->Entity_BundlePath($bundle, true))
                ) return false;

                if (!empty($bundle->info['parent'])) { // child entity bundles do not have custom permalinks
                    if (!$parent = $this->getApplication()->Entity_ParentEntity($entity, false)) return false;

                    $path = str_replace(':slug', $parent->getSlug(), $bundle_permalink_path) . '/' . $entity->getId();
                } else {
                    if ($entity->isDraft()
                        || $entity->isPending()
                    ) {
                        // No slug if draft or pending
                        $path = $bundle_permalink_path . '/' . $entity->getId();
                    } else {
                        $path = $bundle_permalink_path . '/' . $entity->getSlug();
                    }
                }
                $route = trim($path, '/');

                if (is_tax()
                    && !get_query_var('drts_pagename')
                    && ($route_parts = explode('/', $route))
                ) {
                    // page name is required to correctly render taxonomy term page
                    set_query_var('drts_pagename', $route_parts[0]);
                }
            }

            if (is_tax()) {
                if (($current_lang = $this->getCurrentLanguage()) // multi-lingual enabled?
                    && ($requested_lang = get_query_var('drts_lang'))
                    &&  $requested_lang !== $current_lang // language switch requested
                ) {
                    // Need to manually redirect to the requested language page since WPML does not.
                    // Todo: Check what other plugins (Polylang, ect.) do.
                    $term = get_queried_object();
                    if ($this->getTranslatedId('term', $term->taxonomy, $term->term_id, $requested_lang) // has translation?
                        && ($term_url = get_term_link($term))
                    ) {
                        wp_redirect($term_url, 301);
                        exit;
                    }
                }
            }

            if ($action = get_query_var('drts_action')) $route .= '/' . $action;

            return $route;
        }

        // For /_drts* routes
        return ($route = get_query_var('drts_route')) && strpos($route, '_drts') === 0 ? $route : false;
    }

    protected function _loadMo()
    {
        if (!$this->_moLoaded) {
            foreach ($this->getSabaiPlugins() as $plugin_name => $plugin) {
                if ($plugin['mo']) {
                    load_plugin_textdomain($plugin_name, false, $plugin_name . '/languages/');
                }
            }
            $this->_moLoaded = true;
        }

        return $this;
    }

    public function onWpAction()
    {
        $this->_mainContent = $this->_singlePageId = $this->_singlePageContent = null;

        if (!$route = $this->_isSabaiPage()) {
            if (is_page()
                && strpos($GLOBALS['post']->post_content, '[drts-') !== false // using shortcode
            ) {
                set_query_var($this->getPageParam(), ''); // prevents wordpress from redirecting to paged path, e.g. */2, */3.
            }
            return;
        }

        // Using a custom page template to display single post/term page?
        if ((is_single() || is_tax())
            && ($page_name = get_query_var('drts_pagename'))
            && ($page = get_page_by_path($page_name))
            && $page->post_status === 'publish'
        ) {
            // Using custom page
            $this->_singlePageId = $page->ID;
            if (get_query_var('drts_action')) {
                // Do not show custom content on entity action page

                if (false === $this->_mainContent = $this->_runMain($route)) return;
            } else {
                if (strpos($page->post_content, '[drts-entity]') !== false) {
                    if (false === $this->_mainContent = $this->_runMain($route)) return;

                    // Replace shortcode with a placeholder
                    $this->_singlePageContent = preg_replace('#\[drts-entity\]#', '%drts%', $page->post_content, 1);
                } else {
                    if (strpos($page->post_content, '[drts-entity ') !== false) {
                        // No need to render main content here since it should be rendered through page builder
                        $this->_singlePageContent = $page->post_content;
                    } else {
                        if (false === $this->_mainContent = $this->_runMain($route)) return;
                    }
                }
            }
            add_filter('template_include', array($this, 'onTemplateIncludeFilter'));
            add_filter('body_class', array($this, 'onBodyClassFilter'));
        } elseif (is_tax()) {
            // No custom page setup, use the parent page. Below should not fail.
            if ((!$page_name = get_query_var('drts_parent_pagename'))
                || (!$page = get_page_by_path($page_name))
                || $page->post_status !== 'publish'
            ) return;

            if (false === $this->_mainContent = $this->_runMain($route)) return;

            $this->_singlePageId = $page->ID;
            add_filter('template_include', array($this, 'onTemplateIncludeFilter'));
        } else {
            if (false === $this->_mainContent = $this->_runMain($route)) return;
        }

        if (is_tax()) {
            $GLOBALS['wp_query']->post_count = 1;
            $GLOBALS['wp_query']->posts = array($page);
            $GLOBALS['wp_query']->max_num_pages = 1;
            $GLOBALS['wp_query']->rewind_posts();
            $current_theme = strtolower(wp_get_theme(get_template())->get('Name'));
            $archive_force_singular = in_array($current_theme, ['x', 'listify']);
            if (apply_filters('drts_wordpress_archive_force_singular', $archive_force_singular)) {
                $GLOBALS['wp_query']->is_singular = true;
            }
            $archive_force_is_page = in_array($current_theme, ['thegem']);
            if (apply_filters('drts_wordpress_archive_force_is_page', $archive_force_is_page)) {
                $GLOBALS['wp_query']->is_page = true;
            }

            // For page title
            add_filter('the_title', function ($title) {
                return in_the_loop() ? single_term_title('', false) : $title;
            }, PHP_INT_MAX - 2);
            // The7 theme https://themeforest.net/item/the7-responsive-multipurpose-wordpress-theme/5556590
            add_filter('presscore_page_title_strings', function ($titles) {
                $titles['archives'] = single_term_title('', false);
                return $titles;
            }, 99999);
            // Customizr theme
            add_filter('czr_is_list_of_posts', '__return_false', 99999);
        }

        // Remove unwanted filters added by default
        remove_filter('the_content', 'wptexturize');
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'convert_smilies');
        remove_filter('the_content', 'convert_chars');
        remove_filter('the_content', 'shortcode_unautop');
        remove_filter('the_content', 'prepend_attachment');
        remove_filter('the_content', 'do_shortcode', 11);

        add_filter('the_content', array($this, 'onTheContentFilter'), 12);
    }

    public function onTheContentFilter($content)
    {
        if ((DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK || in_the_loop())
            || (function_exists('is_amp_endpoint') && is_amp_endpoint()) // in_the_loop always returns false (AMP 0.5.1) for AMP single pages
        ) {
            if (isset($this->_singlePageContent)) {
                // Process page content
                // Add back default filters that were removed
                add_filter('the_content', 'wptexturize');
                add_filter('the_content', 'wpautop');
                add_filter('the_content', 'convert_smilies');
                add_filter('the_content', 'convert_chars');
                add_filter('the_content', 'shortcode_unautop');
                add_filter('the_content', 'prepend_attachment');
                add_filter('the_content', 'do_shortcode', 11);
                // Remove current filter to prevent loop
                remove_filter('the_content', array($this, 'onTheContentFilter'), 12);
                // Apply the_content filter to page content
                $content = apply_filters('the_content', $this->_singlePageContent);
                if (isset($this->_mainContent)) {
                    // Insert plugin content to where shortcode was if there was any
                    if (strpos($content, '%drts%') !== false) {
                        $content = strtr($content, ['%drts%' => $this->_mainContent]);
                    } else {
                        // Placeholder does not exist for some reason, overwrite with content generated
                        $content = $this->_mainContent;
                    }
                }
            } else {
                $content = $this->_mainContent;
            }
        }
        return $content;
    }

    public function onTemplateIncludeFilter($template)
    {
        $templates = [];
        // Check for custom page template
        if (isset($this->_singlePageId)
            && ($template = get_page_template_slug($this->_singlePageId))
        ) {
            $templates[] = $template;
        }
        $templates[] = 'page.php';
        $templates[] = 'singular.php';
        $templates[] = 'index.php';

        return get_query_template('page', $templates);
    }

    public function onBodyClassFilter($classes)
    {
        if (isset($this->_singlePageId)) {
            $classes[] = 'page-template';

            $template_slug = get_page_template_slug($this->_singlePageId);
            $template_parts = explode('/', $template_slug);

            foreach ( $template_parts as $part ) {
                $classes[] = 'page-template-' . sanitize_html_class(str_replace( array('.', '/'), '-', basename($part, '.php')));
            }
            $classes[] = 'page-template-' . sanitize_html_class(str_replace('.', '-', $template_slug));
        }
        return $classes;
    }

    private function _runMain($route)
    {
        try {
            // Create context
            $request = new Request(true, true); // force stripslashes since WP adds them vis wp_magic_quotes() if magic_quotes_gpc is off
            $context = (new Context())->setRequest($request);

            // Run
            $response = $this->getApplication()
                ->setCurrentScriptName('main')
                ->run(new MainRoutingController(), $context, $route);
            if (!$context->isView()) {
                if ($context->isError()
                    && $context->getErrorType() === 404
                ) {
                    $GLOBALS['wp_query']->is_404 = true;
                    return false;
                }
                $response->send($context);
                exit;
            } else {
                if ($context->getRequest()->isAjax()
                    || $context->getContentType() !== 'html'
                ) {
                    if ($context->getRequest()->isAjax() === '#drts-content') {
                        $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/main_inline.html.php');
                    }
                    $response->send($context);
                    exit;
                } else {
                    ob_start();
                    $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/main_inline.html.php')
                        ->setLayoutHtmlTemplate(__DIR__ . '/layout/main.html.php')
                        ->send($context);
                    return ob_get_clean();
                }
            }
        } catch (\Exception $e) {
            $this->getApplication()->logError($e);
            if ($this->isAdministrator()
                || $this->isDebugEnabled()
            ) {
                return sprintf('<p>%s</p><p><pre>%s</pre></p>', esc_html($e->getMessage()), esc_html($e->getTraceAsString()));
            }
            return sprintf('<p>%s</p>', 'An error occurred while processing the request. Please contact the administrator of the website for further information.');
        }
    }

    public function runAdmin()
    {
        $page = substr($_GET['page'], strlen('drts/'));
        if (!$route = isset($_GET[$this->getRouteParam()]) ? trim($_GET[$this->getRouteParam()], '/') : null) {
            $route = $page;
        }
        $page_slug = current(explode('/', $route));
        if ($page_slug === '_drts') {
            $page_slug = $page;
        }
        $this->_runAdmin($page_slug, $route);

        add_action('admin_enqueue_scripts', function() {
            wp_dequeue_script('cs-plugins');
            wp_dequeue_script('cs-framework');
        }, PHP_INT_MAX);
    }

    protected function _runAdmin($page, $route = null)
    {
        // Create context
        $request = new AdminRequest(true, true);
        $context = (new Context())->setRequest($request);

        try {
            // Run application
            $response = $this->getApplication()
                ->setCurrentScriptName('admin')
                ->setScriptUrl(admin_url('admin.php?page=drts/' . $page), 'admin')
                ->run(new AdminRoutingController(), $context, $route);
            // Flush rewrite rules if required
            if ($this->_flushRewriteRules) {
                foreach ($this->getAllRewriteRules(true) as $regex => $redirect) {
                    add_rewrite_rule($regex, $redirect, 'top');
                }
                flush_rewrite_rules();
            }

            if (!$context->isView()) {
                $response->send($context);
            } else {
                if ($request->isAjax()
                    || $context->getContentType() !== 'html'
                ) {
                    if ($request->isAjax() === '#drts-content') {
                        $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/admin_inline.html.php');
                    }
                    $response->send($context);
                } else {
                    $response->setInlineLayoutHtmlTemplate(__DIR__ . '/layout/admin_inline.html.php')
                        ->setLayoutHtmlTemplate(__DIR__ . '/layout/admin.html.php')
                        ->send($context);
                }
            }
        } catch (\Exception $e) {
            // Display error message
            require_once ABSPATH . 'wp-admin/admin-header.php';
            printf('<p>%s</p><p><pre>%s</pre></p>', $e->getMessage(), $e->getTraceAsString());
            require_once ABSPATH . 'wp-admin/admin-footer.php';
        }
        exit;
    }

    public function onWpEnqueueScripts()
    {
        if (!is_admin()) {
            if (defined('DRTS_WORDPRESS_JQUERY_CDN') && DRTS_WORDPRESS_JQUERY_CDN) {
                wp_deregister_script('jquery');
                wp_register_script('jquery', is_string(DRTS_WORDPRESS_JQUERY_CDN) ? DRTS_WORDPRESS_JQUERY_CDN : '//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js');
            }

            if (defined('DRTS_WORDPRESS_JQUERY_FOOTER') && DRTS_WORDPRESS_JQUERY_FOOTER) {
                // Load jquery in the footer
                wp_enqueue_script('jquery', '', [], false, true);
            }
        }
    }

    public function onWpEnqueueScriptsLast()
    {
        wp_dequeue_script('bootstrap');
    }

    public function onWpPrintStyles()
    {
        wp_dequeue_style('fontawesome');
    }

    public function onExtraPluginHeadersFilter($headers)
    {
        $headers[] = 'SabaiApps License Package';
        return $headers;
    }

    public function getSabaiPlugins($activeOnly = true, $force = false, $addonsOnly = false)
    {
        $id = 'wordpress_plugins_' . (int)$activeOnly . (int)$addonsOnly;
        if ($force
            || false === $plugin_names = $this->getCache($id)
        ) {
            $plugin_names = [];
            if ($plugin_dirs = glob($this->getPackagePath() . '/*', GLOB_ONLYDIR | GLOB_NOSORT)) {
                if (!function_exists('is_plugin_active')) {
                    require ABSPATH . 'wp-admin/includes/plugin.php';
                }
                foreach ($plugin_dirs as $plugin_dir) {
                    $plugin_name = basename($plugin_dir);
                    if (!$activeOnly
                        || is_plugin_active($plugin_name . '/' . $plugin_name . '.php')
                    ) {
                        if ((!$plugin_data = $this->getPluginData($plugin_name))
                            || empty($plugin_data['Author'])
                            || $plugin_data['Author'] !== 'SabaiApps'
                            || ($addonsOnly && strpos($plugin_name, '-') === false)
                        ) continue;

                        $plugin_names[$plugin_name] = $plugin_data + array(
                            'mo' => file_exists($plugin_dir . '/languages/' . $plugin_name . '.pot'),
                        );
                    }
                }
                ksort($plugin_names);
            }
            $this->setCache($plugin_names, $id);
        }
        return $plugin_names;
    }

    public function onAdminMenuAction()
    {
        $default_cap = current_user_can(DRTS_WORDPRESS_ADMIN_CAPABILITY) ? DRTS_WORDPRESS_ADMIN_CAPABILITY : 'manage_directories';
        $endpoints = $this->_getAdminEndpoints();
        foreach ($endpoints as $path => $endpoint) {
            add_menu_page(
                $endpoint['label'],
                $endpoint['label_menu'],
                $capability = isset($endpoint['capability']) ? $endpoint['capability'] : $default_cap,
                'drts' . $path,
                array($this, 'runAdmin'),
                isset($endpoint['icon']) ? $endpoint['icon'] : '',
                $endpoint['order']
            );
            if (!empty($endpoint['children'])) {
                foreach ($endpoint['children'] as $_path => $_endpoint) {
                    add_submenu_page(
                        'drts' . $path,
                        $_endpoint['label'],
                        $_endpoint['label_menu'],
                        isset($_endpoint['capability']) ? $_endpoint['capability'] : $capability,
                        'drts' . $_path,
                        array($this, 'runAdmin')
                    );
                }
            }
        }
    }

    protected function _getAdminEndpoints()
    {
        if (!$endpoints = $this->getCache('wordpress_admin_endpoints')) {
            $endpoints = $this->getApplication()->Filter('wordpress_admin_endpoints', []);
            $this->setCache($endpoints, 'wordpress_admin_endpoints');
        }
        return $endpoints;
    }

    public function onAdminNoticesAction()
    {
        // Show errors on our application admin pages only
        $is_sabai_page = strpos(get_current_screen()->parent_base, 'drts/') === 0;

        foreach ($this->_flash as $flash) {
            switch ($flash['level']) {
                case 'danger':
                case 'error':
                    $class = 'error';
                    break;
                case 'success':
                case 'warning':
                    if (!$is_sabai_page) continue 2;

                    $class = $flash['level'];
                    break;
                default:
                    if (!$is_sabai_page) continue 2;

                    $class = 'info';
            }
            echo '<div class="notice notice-' . $class . ' is-dismissible"><p>[directories] ' . esc_html($flash['msg']) . '</p></div>';
        }
    }

    public function onPostUpdatedAction($postId, $postAfter, $postBefore)
    {
        // Has slug been changed?
        if ($postAfter->post_name === $postBefore->post_name) return;

        // Is it a SabaiApps application page?
        if (!$slug = $this->_isSabaiPageId($postId)) return;

        // Update SabaiApps application page slug data
        $new_slug = $postAfter->post_name;
        $page_slugs = $this->getPageSlugs();
        $page_slugs[0][$new_slug] = $new_slug;
        $page_slugs[2][$new_slug] = $postId;
        if (isset($page_slugs[5][$slug])) {
            $page_slugs[5][$new_slug] = $page_slugs[5][$slug];
        }
        unset($page_slugs[0][$slug], $page_slugs[2][$slug], $page_slugs[5][$slug]);
        foreach (array_keys($page_slugs[1]) as $component_name) {
            if ($slug_key = array_search($slug, $page_slugs[1][$component_name])) {
                $page_slugs[1][$component_name][$slug_key] = $new_slug;
                break;
            }
        }
        $this->setPageSlugs($page_slugs);

        // Reload all main routes
        $this->getApplication()->getComponent('System')->reloadAllRoutes(true);

        // Updrade all ISlug components since slugs have been updated
        $this->getApplication()->System_Component_upgradeAll(array_keys($this->getApplication()->System_Slugs()));

        // Need to manually flush rules since this is not coming from our plugin admin page
        foreach ($this->getAllRewriteRules(true) as $regex => $redirect) {
            add_rewrite_rule($regex, $redirect, 'top');
        }
        flush_rewrite_rules();
    }

    public function isSabaiAppsPlugin($plugin)
    {
        return $plugin === 'drts'
            || in_array($plugin, array_keys(apply_filters('drts_core_component_paths', [])));
    }

    public function onActivatedPluginAction($plugin)
    {
        $component_paths = apply_filters('drts_core_component_paths', []);
        $plugin = basename($plugin, '.php');
        if (isset($component_paths[$plugin])) {
            Util::activatePlugin($this, $plugin, $component_paths[$plugin][0]);
        }
    }

    public function onDeactivatedPluginAction($plugin)
    {
        $plugin = basename($plugin, '.php');
        if (in_array($plugin, array_keys(apply_filters('drts_core_component_paths', [])))) {
            // Force reload all components
            $this->getApplication()->System_Component_upgradeAll(null, true);
        }
    }

    public function onUpgraderProcessCompleteAction($upgrader, $options)
    {
        if ($options['action'] === 'update'
            && $options['type'] === 'plugin'
            && isset($options['plugins'])
        ) {
            foreach($options['plugins'] as $plugin) {
                if ($plugin === 'directories/directories.php') {
                    // Delete cache to re-check un-updated components
                    $this->deleteCache('system_component_updates');
                }
            }
        }
    }

    public function onAfterSwitchThemeAction()
    {
        $this->deleteCache('wordpress_assets_dir');
    }

    public function onNetworkAdminPluginActionLinks($links, $pluginFile)
    {
        if (strpos($pluginFile, 'directories') === 0) {
            unset($links['activate'], $links['deactivate']);
        }
        return $links;
    }

    public function onDeleteSiteTransientUpdatePluginsAction()
    {
        // Delete component update info
        $this->deleteCache('wordpress_component_updates');

        // Clear old verison info currenlty saved
        $this->getUpdater()->clearOldVersionInfo();
    }

    /**
     * @param bool $loadComponents
     * @param bool $reload
     * @param bool $throwError
     * @return Application
     */
    public function getApplication($loadComponents = true, $reload = false, $throwError = false)
    {
        $this->_loadMo();
        try {
            return parent::getApplication($loadComponents, $reload);
        } catch (Exception\NotInstalledException $e) {
            if ($throwError) throw $e;

            if (!function_exists('deactivate_plugins')) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }
            deactivate_plugins(plugin_basename(Loader::plugin(true)));
            wp_redirect(is_admin() ? admin_url('plugins.php') : home_url());
            exit;
        }
    }

    protected function _createApplication()
    {
        // Init
        $app = parent::_createApplication();
        $app->isSsl(is_ssl())->addUrlTrailingSlash(true);

        // Set main URL
        $main_url = $this->getHomeUrl();
        $app->setScriptUrl($main_url, 'main');
        $mod_rewrite_format = '%1$s';
        if ($params_pos = strpos($main_url, '?')) { // plugins such as WPML adds params to home URL
            $mod_rewrite_format .= substr($main_url, $params_pos);
            $main_url = substr($main_url, 0, $params_pos);
        }
        $app->setModRewriteFormat(rtrim($main_url, '/') . $mod_rewrite_format, 'main');

        // Set custom helpers
        $app->setHelper('GravatarUrl', array($this, 'gravatarUrlHelper'))
            ->setHelper('Slugify', array($this, 'slugifyHelper'))
            ->setHelper('Summarize', array($this, 'summarizeHelper'))
            ->setHelper('Action', array(new ActionHelper(), 'help'))
            ->setHelper('Filter', array(new FilterHelper(), 'help'))
            ->setHelper('Form_Token_create', array($this, 'formTokenCreateHelper'))
            ->setHelper('Form_Token_validate', array($this, 'formTokenValidateHelper'));
        // Custom URL helper if permalink method is Plain
        if (!$this->isAdmin()
            && !get_option('permalink_structure')
        ) {
            $page_slugs = $this->getPageSlugs();
            if (!empty($page_slugs[2])) {
                $app->setHelper('Url', array(new UrlHelper($page_slugs[2]), 'help'));
            }
        }

        if (!$logger = $app->getLogger()) {
            $logger = new Logger('drts');
        }
        if ($this->isDebugEnabled()) {
            // Set an error_log logger so that errors can be written to wordpress debug.log
            $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, Logger::ERROR));
        }
        $app->setLogger($logger);

        return $app;
    }

    public function onInitAction()
    {
        $app = $this->getApplication();

        // Invoke components
        $app->Action('core_platform_wordpress_init');

        // Redirect wp-login.php to custom login page?
        if ($GLOBALS['pagenow'] === 'wp-login.php'
            && (!isset($_REQUEST['action']) || in_array($_REQUEST['action'], array('register', 'login')))
            && $app->getUser()->isAnonymous()
            && $app->isComponentLoaded('FrontendSubmit')
            && $app->getComponent('FrontendSubmit')->getConfig('login', isset($_REQUEST['action']) && $_REQUEST['action'] === 'register' ? 'register_form' : 'login_form')
            && ($login_slug = $app->getComponent('FrontendSubmit')->getSlug('login'))
            && ($page = get_page_by_path($login_slug))
            && $this->_isSabaiPageId($page->ID)
        ) {
            wp_redirect($app->Url(
                '/' . $login_slug,
                isset($_REQUEST['redirect_to']) ? array('redirect_to' => $_REQUEST['redirect_to']) : []
            ));
            exit;
        }

        // Add rewrite rules
        foreach ($this->getAllRewriteRules() as $regex => $redirect) {
            add_rewrite_rule($regex, $redirect, 'top');
        }
    }

    public function getAllRewriteRules($force = false)
    {
        $rewrite_rules = array(
            '_drts/(.*)$' => 'index.php?drts_route=_drts/$matches[1]',
            '_drts/?$' => 'index.php?drts_route=_drts',
        );
        if ($langugaes = $this->getLanguages()) {
            foreach ($langugaes as $lang) {
                $rewrite_rules += $this->_getRewriteRules($lang, $force);
            }
        } else {
            $rewrite_rules += $this->_getRewriteRules(null, $force);
        }
        krsort($rewrite_rules, SORT_STRING);

        return $rewrite_rules;
    }

    protected function _getRewriteRules($lang, $force = false)
    {
        $cache_id = empty($lang) ? 'wordpress_rewrite_rules' : 'wordpress_rewrite_rules_' . $lang;
        if ($force
            || false === ($ret = $this->getCache($cache_id))
        ) {
            $ret = [];
            if ($page_slugs = $this->getPageSlugs($lang)) {
                $child_post_types = [];
                // Custom permalink rewrites should come first
                if (!empty($page_slugs[4])) {
                    foreach ($page_slugs[4] as $slug_info) {
                        if ((!$slug_info['component'])
                            || !isset($slug_info['slug'])
                            || (!$component_name = $slug_info['component'])
                            || !isset($page_slugs[1][$component_name][$slug_info['slug']])
                        ) continue;

                        $post_type = null;
                        $slug = $page_slugs[1][$component_name][$slug_info['slug']]; // get the actual slug configured
                        $_slug_info = $page_slugs[5][$slug];
                        $page_name = isset($_slug_info['page_name']) ? trim($_slug_info['page_name'], '/') : null;
                        if (isset($page_slugs[5][$slug]['post_type'])) {
                            $post_type = $_slug_info['post_type'];
                            foreach ($slug_info['regex'] as $regex) {
                                if ($this->isAmpEnabled($post_type)) {
                                    $ret[$regex['regex'] . '/amp/?$'] = 'index.php?amp=1&post_type=' . $post_type
                                        . '&' . ($regex['type'] === 'id' ? 'p' : 'name') . '=$matches[1]'
                                        . '&drts_route=' . $slug . '/$matches[1]'
                                        . '&drts_pagename=' . $page_name
                                        . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                        . '&drts_lang=' . $lang;
                                }
                                $ret[$regex['regex'] . '$'] = 'index.php?post_type=' . $post_type
                                    . '&' . ($regex['type'] === 'id' ? 'p' : 'name') . '=$matches[1]'
                                    . '&drts_route=' . $slug . '/$matches[1]'
                                    . '&drts_pagename=' . $page_name
                                    . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                    . '&drts_lang=' . $lang;
                            }
                            $post_bundle_types[$component_name][$_slug_info['bundle_group']][$_slug_info['bundle_type']] = $slug;
                        } elseif (isset($page_slugs[5][$slug]['taxonomy'])) {
                            $taxonomy = $_slug_info['taxonomy'];
                            foreach ($slug_info['regex'] as $regex) {
                                $ret[$regex['regex'] . '$'] = 'index.php?' . $taxonomy . '=$matches[1]'
                                    . '&drts_route=' . $slug . '/$matches[1]'
                                    . '&drts_pagename=' . $page_name
                                    . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                    . '&drts_lang=' . $lang;
                            }
                        }
                        unset($page_slugs[0][$slug], $page_slugs[5][$slug]);
                    }
                }

                // Add rewrite for post type / taxonomy
                if (!empty($page_slugs[5])) {
                    foreach ($page_slugs[5] as $slug => $slug_info) {
                        $page_name = isset($slug_info['page_name']) ? trim($slug_info['page_name'], '/') : null;
                        if (isset($slug_info['post_type'])) {
                            if (!empty($slug_info['is_child'])) {
                                $child_post_types[$slug_info['post_type']] = ['slug' => $slug, 'page_name' => $page_name];
                            } else {
                                if ($this->isAmpEnabled($slug_info['post_type'])) {
                                    $ret[preg_quote($slug) . '/([^/]+)/amp/?$'] = 'index.php?amp=1&post_type=' . $slug_info['post_type']
                                        . '&name=$matches[1]&drts_route=' . $slug . '/$matches[1]'
                                        . '&drts_pagename=' . $page_name
                                        . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                        . '&drts_lang=' . $lang;
                                }
                                $ret[preg_quote($slug) . '/([^/]+)$'] = 'index.php?post_type=' . $slug_info['post_type']
                                    . '&name=$matches[1]&drts_route=' . $slug . '/$matches[1]'
                                    . '&drts_pagename=' . $page_name
                                    . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                    . '&drts_lang=' . $lang;
                            }
                        } elseif (isset($slug_info['taxonomy'])) {
                            $ret[preg_quote($slug) . '/([^/]+)$'] = 'index.php?' . $slug_info['taxonomy'] . '=$matches[1]'
                                . '&drts_route=' . $slug . '/$matches[1]'
                                . '&drts_pagename=' . $page_name
                                . '&drts_parent_pagename=' . dirname(trim($slug, '/'))
                                . '&drts_lang=' . $lang;
                        }
                        unset($page_slugs[0][$slug]);
                    }
                }

                // Add rewrite for child post types
                if (!empty($child_post_types)) {
                    foreach (array_keys($child_post_types) as $child_post_type) {
                        $slug = $child_post_types[$child_post_type]['slug'];
                        $parent_slug = trim(dirname($slug), '/');
                        $child_slug = basename($slug);
                        $ret[preg_quote($parent_slug) . '/([^/]+)/' . preg_quote($child_slug) . '/([0-9]+)?$'] = 'index.php?'
                            . 'post_type=' . $child_post_type
                            . '&p=$matches[2]&drts_route=' . $parent_slug . '/$matches[1]/' . $child_slug . '/$matches[2]'
                            . '&drts_pagename=' . $child_post_types[$child_post_type]['page_name']
                            . '&drts_parent_pagename=' . dirname($parent_slug)
                            . '&drts_lang=' . $lang;
                    }
                }

                if (!empty($page_slugs[0])) {
                    foreach ($page_slugs[0] as $slug) {
                        $ret[preg_quote($slug) . '/(.*)$'] = 'index.php?pagename=' . $slug
                            . '&drts_route=' . $slug . '/$matches[1]'
                            . '&drts_lang=' . $lang;
                        $ret[preg_quote($slug) . '/?$'] = 'index.php?pagename=' . $slug
                            . '&drts_route=' . $slug
                            . '&drts_lang=' . $lang;
                    }
                }
            }
            $this->setCache($ret, $cache_id, 0);
        }

        return $ret;
    }

    public function getUpdater()
    {
        return Updater::getInstance($this);
    }

    public function onAdminInitAction()
    {
        // Run autoupdater
        if ($this->isAdministrator(get_current_user_id())) {
            // Enable update notification if any license key is set
            $license_keys = $this->getOption('license_keys', []);
            if (!empty($license_keys)) {
                $plugin_names = $this->getSabaiPlugins(false);
                foreach ($license_keys as $plugin_name => $license_key) {
                    if (!isset($plugin_names[$plugin_name])
                        || !strlen((string)@$license_key['value'])
                    ) continue;

                    $this->getUpdater()->addPlugin($plugin_name, $license_key['type'], $license_key['value']);
                    unset($plugin_names[$plugin_name]);
                }
                if (!empty($plugin_names)) {
                    $active_plugin_names = $this->getSabaiPlugins(true);
                    foreach (array_keys($plugin_names) as $plugin_name) {
                        if (isset($active_plugin_names[$plugin_name])) {
                            $this->addFlash([[
                                'level' => 'danger',
                                'msg' => sprintf(__('Please enter a license key for %s in Settings -> Licenses.', 'directories'), $plugin_name),
                            ]]);
                        }
                    }
                }
            }

            // Add a hook to clear cache of upgradable components when plugins are installed/updated/uninstalled
            add_action('delete_site_transient_update_plugins', array($this, 'onDeleteSiteTransientUpdatePluginsAction'));
        }

        // Invoke components
        $this->getApplication()->Action('core_platform_wordpress_admin_init');

        // Register polylang strings
        //if ($this->_i18n === 'polylang'
        //    && ($polylang_strings = $this->getOption('_polylang_strings', []))
        //) {
        //    foreach (array_keys($polylang_strings) as $domain) {
        //        foreach (array_keys($polylang_strings[$domain]) as $name) {
        //            pll_register_string($name, $polylang_strings[$domain][$name], $domain, true);
        //        }
        //    }
        //}
    }

    public function onWidgetsInitAction()
    {
        $widgets = $this->getApplication()->System_Widgets();

        // Fetch all sabai widgets and then convert each to a wp widget
        foreach ($widgets as $widget_name => $widget) {
            $class = sprintf('SabaiApps_Directories_WordPress_Widget_%s', $widget_name);
            if (class_exists('\\' . $class, false)) continue;

            eval(sprintf('
class %s extends \SabaiApps\Directories\Platform\WordPress\Widget {
    public function __construct() {
        parent::__construct("%s", "%s", "%s");
    }
}
                ', $class, $widget_name, esc_html($widget['title']), esc_html($widget['summary'])));
            register_widget($class);
        }
    }

    public function onWpLoginAction()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT) {
            Application::startSession(defined('DRTS_WORDPRESS_SESSION_PATH') ? DRTS_WORDPRESS_SESSION_PATH : null);
            session_regenerate_id(true); // to prevent session fixation attack
        }
    }

    public function onWpLogoutAction()
    {
        if (!DRTS_WORDPRESS_SESSION_TRANSIENT && session_id()) {
            $_SESSION = [];
            session_destroy();
        }
    }

    public function onDeleteUserAction($userId)
    {
        // Cache user data here so that we can reference it after the user actually being deleted
        $identity = $this->getApplication()->UserIdentity($userId);
        if (!$identity->isAnonymous()) $this->_userToBeDeleted[$userId] = $identity;
    }

    public function onDeletedUserAction($userId)
    {
        if (!isset($this->_userToBeDeleted[$userId])) return;

        // Notify that a user account has been dleted
        $this->getApplication()->Action('core_platform_user_deleted', array($this->_userToBeDeleted[$userId]));

        unset($this->_userToBeDeleted[$userId]);
    }

    public function onAdminHeadWidgetsPhpAction()
    {
        echo '<style type="text/css">
.drts-form-field {margin:1em 0;}
.drts-form-field label {display:inline; margin-bottom:2px;}
.drts-form-field input[type=checkbox] {margin-top:0;}
.drts-form-field select,.drts-form-field input[type=text] {width:100%;}
.widget[id*="drts"] .widget-title h3 {overflow: auto; text-overflow: initial;}
</style>';
    }

    protected function _loadJqueryJs()
    {
        wp_enqueue_script('hoverIntent');
    }

    protected function _loadCoreJs()
    {
        if (!$bs_handle = $this->_getBootstrapHandle()) {
            $bootstrap_handle = 'drts-bootstrap';
            wp_enqueue_script('popper', $this->getAssetsUrl(null, true) . '/js/popper.min.js', [], Application::VERSION);
            wp_enqueue_script('drts-bootstrap', $this->getAssetsUrl() . '/js/bootstrap.min.js', array('jquery', 'popper'), Application::VERSION, true);
        } else {
            $bootstrap_handle = $bs_handle['js'];
        }
        wp_enqueue_script('drts', $this->getAssetsUrl() . '/js/core.min.js', array('jquery', $bootstrap_handle), Application::VERSION, true);
        wp_add_inline_script('drts', sprintf(
            'if (typeof DRTS === "undefined") var DRTS = {isRTL: %s, domain: "%s", path: "%s", bsPrefix: "%s", params: {token: "%s", contentType: "%s", ajax: "%s"}};',
            $this->isRtl() ? 'true' : ' false',
            $this->getCookieDomain(),
            $this->getCookiePath(),
            DRTS_BS_PREFIX,
            Request::PARAM_TOKEN,
            Request::PARAM_CONTENT_TYPE,
            Request::PARAM_AJAX
        ), 'before');
    }

    protected function _loadJsFile($url, $handle, $dependency, $inFooter)
    {
        wp_enqueue_script($handle, $url, (array)$dependency, Application::VERSION, $inFooter);
    }

    protected function _loadJsInline($dependency, $js)
    {
        wp_add_inline_script($dependency, $js);
    }

    protected function _loadCoreCss($type)
    {
        $deps = [];
        $css_url = $this->getAssetsUrl() . '/css/';
        if ($type === 'admin'
            || !apply_filters('drts_fontawesome_disable', false)
        ) {
            wp_enqueue_style('drts-fontawesome', $this->getAssetsUrl(null, true) . '/css/fontawesome.min.css', [], Application::VERSION);
            wp_enqueue_style('drts-system-fontawesome', $this->getAssetsUrl() . '/css/system-fontawesome.min.css', ['drts-fontawesome'], Application::VERSION);
        }
        if ($type === 'admin') {
            wp_enqueue_style('drts-bootstrap', $css_url . 'bootstrap-' . $type . '.min.css', [], Application::VERSION);
            $deps[] = 'drts-bootstrap';
            wp_enqueue_style('drts', $css_url . $type . '.min.css', $deps, Application::VERSION);
        } else {
            if (!$bs_handle = $this->_getBootstrapHandle()) {
                $_suffix = $type;
                if (defined('DRTS_THEME')) {
                    $_suffix .= '-' . DRTS_THEME;
                }
                wp_enqueue_style('drts-bootstrap', $css_url . 'bootstrap-' . $_suffix . '.min.css', [], Application::VERSION);
                $deps[] = 'drts-bootstrap';
            }
            $_css_url = $css_url . $type;
            if (defined('DRTS_THEME')) {
                $_css_url .= '-' . DRTS_THEME;
            }
            wp_enqueue_style('drts', $_css_url . '.min.css', $deps, Application::VERSION);
        }
        if ($this->isRtl()) {
            wp_enqueue_style('drts-rtl', $css_url . $type . '-rtl.min.css', ['drts'], Application::VERSION);
        }
    }

    protected function _loadCssFile($url, $handle, $dependency, $media)
    {
        wp_enqueue_style($handle, $url, (array)$dependency, Application::VERSION, $media);
    }

    protected function _loadJqueryUiJs(array $components)
    {
        if (!isset($this->_jqueryUiCoreLoaded)) {
            wp_enqueue_script('jquery-ui-core');
            $this->_jqueryUiCoreLoaded = [];
        }
        if (!$this->_jqueryUiCssLoaded) {
            $theme_url = apply_filters(
                'drts_jquery_ui_theme_url',
                '//ajax.googleapis.com/ajax/libs/jqueryui/' . $GLOBALS['wp_scripts']->registered['jquery-ui-core']->ver . '/themes/smoothness/jquery-ui.min.css'
            );
            if ($theme_url) {
                wp_enqueue_style('drts-jquery-ui', $theme_url);
            }
            $this->_jqueryUiCssLoaded = true;
        }
        foreach ($components as $component) {
            wp_enqueue_script(strpos($component, 'effects') === 0 ? 'jquery-' . $component : 'jquery-ui-' . $component);
        }
    }

    protected function _loadImagesLoadedJs()
    {
        wp_enqueue_script('imagesloaded');
    }

    public function formTokenCreateHelper(Application $application, $tokenId, $tokenLifetime = 1800, $reobtainable = false)
    {
        return wp_create_nonce('drts_' . $tokenId);
    }

    public function formTokenValidateHelper(Application $application, $tokenValue, $tokenId, $reuseable)
    {
        $result = wp_verify_nonce($tokenValue, 'drts_' . $tokenId);
        // 1 indicates that the nonce has been generated in the past 12 hours or less.
        // 2 indicates that the nonce was generated between 12 and 24 hours ago.
        // Use 1 for enhanced security
        return $result === 1;
    }

    public function gravatarUrlHelper(Application $application, $email, $size = 96, $default = 'mm', $rating = null, $secure = false)
    {
        if (preg_match('/src=("|\')(.*?)("|\')/i', get_avatar($email, $size, $default), $matches)) {
            return str_replace('&amp;', '&', $matches[2]);
        }
    }

    public function getSiteToSystemTime($timestamp)
    {
        // mktime should return UTC in WP
        return intval($timestamp - get_option('gmt_offset') * 3600);
    }

    public function getSystemToSiteTime($timestamp)
    {
        return intval($timestamp + get_option('gmt_offset') * 3600);
    }

    public function slugifyHelper(Application $application, $string, $maxLength = 200)
    {
        $slug = rawurldecode(sanitize_title($string));
        return empty($maxLength) ? $slug : substr($slug, 0, $maxLength);
    }

    public function summarizeHelper(Application $application, $text, $length = 0, $trimmarker = '...')
    {
        if (!strlen($text)) return '';

        $text = strip_shortcodes(strip_tags(strtr($text, array("\r" => '', "\n" => ' '))));

        return empty($length) ? $text : $application->System_MB_strimwidth($text, 0, $length, $trimmarker);
    }

    public function activate()
    {
        Util::activate($this);
        Util::activatePlugin($this, 'directories');
    }

    public function createPage($slug, $title, $lang = false)
    {
        return Util::createPage($this, $slug, $title, $lang);
    }

    public function getPluginData($pluginName, $key = null, $default = false)
    {
        $plugin_file = $this->getPackagePath() . '/' . $pluginName . '/' . $pluginName . '.php';
        if (!file_exists($plugin_file)) return $default;

        // Fetch plugin data for version comparison
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin_data = get_plugin_data($plugin_file, false, false);

        return isset($key) ? (isset($plugin_data[$key]) ? $plugin_data[$key] : $default) : $plugin_data;
    }

    public function unzip($from, $to)
    {
        global $wp_filesystem;
        if (!isset($wp_filesystem)) WP_Filesystem();

        if (true !== $result = unzip_file($from, $to)) {
            throw new Exception\RuntimeException($result->get_error_message());
        }
    }

    public function updateDatabase($schema, $previousSchema = null)
    {
        Util::updateDatabase($this, $schema, $previousSchema);
    }

    public function isAdmin()
    {
        return is_admin();
    }

    public function registerString($str, $name, $group = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                do_action('wpml_register_single_string', isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name, $str);
                return $this;
            //case 'polylang':
            //    $strings = $this->getOption('_polylang_strings', []);
            //    $strings[$domain][$name] = $str;
            //    $this->setOption('_polylang_strings', $strings);
            //    return $this;
            default:
                return $this;
        }
    }

    public function unregisterString($name, $group = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                if (function_exists('icl_unregister_string')) {
                    icl_unregister_string(isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name);
                }
                return $this;
            //case 'polylang':
            //    $strings = $this->getOption('_polylang_strings', []);
            //    unset($strings[isset($group) ? 'drts-strings-' . $group : 'drts-strings';][$name]);
            //    $this->setOption('_polylang_strings', $strings);
            //    return $this;
            default:
                return $this;
        }
    }

    public function translateString($str, $name, $group = null, $lang = null)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_translate_single_string', $str, isset($group) ? 'drts-strings-' . $group : 'drts-strings', $name, $lang);
            //case 'polylang':
            //    return isset($lang) ? pll_translate_string($str, $lang) : pll__($str);
            default:
                return $str;
        }
    }

    public function getLanguages()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return array_keys(apply_filters('wpml_active_languages', []));
            //case 'polylang':
            //    return pll_languages_list();
            default:
                return [];
        }
    }

    public function getDefaultLanguage()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return ($lang = apply_filters('wpml_default_language', null)) ? $lang : null;
            //case 'polylang':
            //    return ($lang = pll_default_language()) ? $lang : null;
            default:
                return;
        }
    }

    public function getCurrentLanguage()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return ($lang = apply_filters('wpml_current_language', null)) ? $lang : null;
            //case 'polylang':
            //    return ($lang = pll_current_language()) ? $lang : null;
            default:
                return;
        }
    }

    public function isTranslatable($entityType, $bundleName)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return $entityType === 'term' ? is_taxonomy_translated($bundleName) : is_post_type_translated($bundleName);
            //case 'polylang':
            //    return $entityType === 'term' ? pll_is_translated_taxonomy($bundleName) : pll_is_translated_post_type($bundleName);
            default:
                return;
        }
    }

    public function getTranslatedId($entityType, $bundleName, $id, $lang)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return apply_filters('wpml_object_id', $id, $bundleName, false, $lang);
            //case 'polylang':
            //    return $entityType === 'term' ? pll_get_term($id, $lang) : pll_get_post($id, $lang);
            default:
                return;
        }
    }

    public function isTranslated($entityType, $bundleName, $id)
    {
        switch ($this->_i18n) {
            case 'wpml':
                $original_entity_id = apply_filters('wpml_original_element_id', null, $id, ($entityType === 'term' ? 'tax_' : 'post_') . $bundleName);
                return !$original_entity_id || $original_entity_id == $id ? false : $original_entity_id;
            //case 'polylang':
            //    if (!$original_entity_id = $entityType === 'term' ? pll_get_term($id) : pll_get_post($id)) return;
            //    return $original_entity_id == $id ? false : $original_entity_id;
            default:
                return;
        }
    }

    public function getLanguageFor($entityType, $bundleName, $id)
    {
        switch ($this->_i18n) {
            case 'wpml':
                return $GLOBALS['sitepress']->get_language_for_element($id, ($entityType === 'term' ? 'tax_' : 'post_') . $bundleName);
            //case 'polylang':
            //    retun $entityType === 'term' ? pll_get_term_language($id)) : pll_get_post_language($id);
            default:
                return;
        }

    }

    public function isAdminAddTranslation()
    {
        switch ($this->_i18n) {
            case 'wpml':
                return !empty($_GET['trid']) && class_exists('\SitePress', false) && \SitePress::get_original_element_id_by_trid($_GET['trid']);
            //case 'polylang':
            //
            default:
                return;
        }
    }

    public function isDebugEnabled()
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    public function isAmpEnabled($bundleName)
    {
        return false;
        return function_exists('is_amp_endpoint') && post_type_supports($bundleName, AMP_QUERY_VAR);
    }

    public function isAmp()
    {
        return is_amp_endpoint();
    }

    public function addFlash(array $flash)
    {
        if (!$this->isAdmin()) return parent::addFlash($flash);

        foreach ($flash as $_flash) $this->_flash[] = $_flash;

        return $this;
    }

    public function getTemplate()
    {
        return Template::getInstance($this);
    }

    public function remoteGet($url, array $args = [])
    {
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            throw new Exception\RuntimeException($response->get_error_message());
        }
        if (200 != ($code = wp_remote_retrieve_response_code($response))) {
            throw new Exception\RuntimeException('The server did not return a valid response. Request sent to: ' . $url);
        }
        return $response['body'];
    }

    public function remotePost($url, array $params = [], array $args = [])
    {
        $response = wp_remote_post($url, array('body' => $params) + $args);
        if (is_wp_error($response)) {
            throw new Exception\RuntimeException($response->get_error_message());
        }
        if (200 != ($code = wp_remote_retrieve_response_code($response))) {
            throw new Exception\RuntimeException('The server did not return a valid response. Request sent to: ' . $url . '; Response body: ' . $response['body']);
        }
        return $response['body'];
    }

    public function anonymizeEmail($email)
    {
        return wp_privacy_anonymize_data('email', $email);
    }

    public function anonymizeUrl($url)
    {
        return wp_privacy_anonymize_data('url', $url);
    }

    public function anonymizeIp($ip)
    {
        return wp_privacy_anonymize_data('ip', $ip);
    }

    public function anonymizeText($text)
    {
        return wp_privacy_anonymize_data('text', $text);
    }

    public function downloadUrl($url, $save = false, $title = null, $ext = null)
    {
        // Check if already saved
        if ($save) {
            if (!isset($title)
                || !is_string($title)
                || !strlen($title = trim($title))
            ) {
                throw new Exception\RuntimeException('Invalid download file title.');
            }

            $slug = 'drts-download-url-' . sanitize_title($title);
            if ($attachment = get_page_by_path($slug, OBJECT, 'attachment')) {
                if ($url = wp_get_attachment_url($attachment->ID)) return $url;

                wp_delete_attachment($attachment->ID, /*force*/ true);
            }
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Download
        $file_path = download_url($url);
        if (is_wp_error($file_path)) {
            throw new Exception\RuntimeException($url . ': ' . $file_path->get_error_message());
        }

        // Save file?
        if (!$save) return $file_path;
        // $save can be a custom function to determine whether or not to save the file
        if ($save instanceof \Closure) {
            if (!$save($file_path)) return $file_path;
        }

        // Save
        $id = media_handle_sideload(
            [
                'name' => sanitize_file_name($title . (string)$ext),
                'tmp_name' => $file_path,
            ],
            0, // post ID
            null, // desc
            [
                'post_name' => $slug,
                'post_title' => $title,
            ]
        );
        if (is_wp_error($id)) {
            @unlink($file_path);
            throw new Exception\RuntimeException($url . ': ' . $id->get_error_message());
        }

        // Get URL
        if (!$url = wp_get_attachment_url($id)) {
            throw new Exception\RuntimeException('Failed retrieving URL for attachment ID:' . $id);
        }

        return $url;
    }
}
