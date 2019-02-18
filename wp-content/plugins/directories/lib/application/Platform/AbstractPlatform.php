<?php
namespace SabaiApps\Directories\Platform;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\MainRoutingController;
use SabaiApps\Directories\Assets;
use SabaiApps\Directories\Exception;
use SabaiApps\Framework\Application\Url;

abstract class AbstractPlatform
{
    private static $_application;
    protected $_name, $_defaultJsLoaded, $_defaultCssLoaded, $_renderCount = 0,
        $_head = [], $_js = [], $_jsIndex = 0, $_css = [],
        // For tracking assets
        $_trackedAssets;

    protected function __construct($name)
    {
        $this->_name = $name;
    }

    final public function getName()
    {
        return $this->_name;
    }

    public function getHomeUrl()
    {
        return $this->getSiteUrl();
    }

    /**
     * @param bool $loadComponents
     * @param bool $reload
     * @return Application
     */
    public function getApplication($loadComponents = true, $reload = false)
    {
        if (!isset(self::$_application)) {
            self::$_application = $this->_createApplication();
        }
        if ($loadComponents) {
            if ($reload) {
                self::$_application->reloadComponents();
            } else {
                self::$_application->loadComponents();
            }
        }

        return self::$_application;
    }

    /**
     * @return Application
     */
    protected function _createApplication()
    {
        return new Application($this);
    }

    public function loadDefaultAssets($loadJs = true, $loadCss = true)
    {
        if ($loadJs
            && !$this->_defaultJsLoaded
        ) {
            $this->_loadJqueryJs();
            $this->_loadCoreJs();
            $this->addJsFile('sweetalert2.all.min.js', 'sweetalert2', null, null, true, true);
            $this->addJsFile('autosize.min.js', 'autosize', 'jquery', null, true, true);
            $this->addJsFile('jquery.coo_kie.min.js', 'jquery-cookie', 'jquery', null, true, true);
            $this->addJsFile('cq-prolyfill.min.js', 'cq-polyfill', null, null, false, true);
            $this->_defaultJsLoaded = true;
        }
        if ($loadCss) {
            if (!$this->_defaultCssLoaded) {
                $type = $this->isAdmin() ? 'admin' : 'main';

                // Load main CSS
                $this->_loadCoreCss($type);

                // Load plugin CSS
                $packages = $this->getPackages();
                $cache_id = 'core_css_files_' . implode('-', $packages);
                if (!$css_files = $this->getCache($cache_id)) {
                    $css_files = ['main' => [], 'admin' => [], 'rtl' => ['main' => [], 'admin' => []]];
                    $core_assets_dir = $this->getAssetsDir();
                    foreach ($packages as $package) {
                        $assets_dir = $this->getAssetsDir($package);
                        if ($core_assets_dir === $assets_dir) continue;

                        if (file_exists($assets_dir . '/css/main.min.css')) {
                            $css_files['main'][$package] = 'main';
                        }
                        if (file_exists($assets_dir . '/css/main-rtl.min.css')) {
                            $css_files['rtl']['main'][$package] = 'main-rtl';
                        }
                        if (file_exists($assets_dir . '/css/admin.min.css')) {
                            $css_files['admin'][$package] = 'admin';
                        }
                        if (file_exists($assets_dir . '/css/admin-rtl.min.css')) {
                            $css_files['rtl']['admin'][$package] = 'admin-rtl';
                        }
                    }
                    $this->setCache($css_files, $cache_id);
                }

                foreach (array_keys($css_files[$type]) as $package) {
                    $this->addCssFile($css_files[$type][$package] . '.min.css', $package, ['drts'], $package);
                }

                if ($type === 'main') {
                    // Load custom CSS if any
                    $deps = ['drts'];
                    foreach ($this->getCustomAssetsDir() as $index => $custom_dir) {
                        if (@file_exists($custom_dir . '/style.css')) {
                            $this->addCssFile($this->getCustomAssetsDirUrl($index) . '/style.css', $handle = 'drts-custom-' . $index, $deps, false);
                            $deps[] = $handle;
                        }
                    }
                }

                // Load RTL CSS
                if (!empty($css_files['rtl'][$type])
                    && $this->isRtl()
                ) {
                    foreach (array_keys($css_files['rtl'][$type]) as $package) {
                        $this->addCssFile($css_files['rtl'][$type][$package] . '.min.css', $package . '-rtl', [$package], $package);
                    }
                }

                $this->_defaultCssLoaded = true;
            }
        }

        return $this;
    }

    public function addHead($head, $handle, $index = 10)
    {
        $this->_head[$index][$handle] = $head;
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addHead($handle, $head, $index);
        }
        return $this;
    }

    public function getHeadHtml($clear = true)
    {
        $html = [];
        if (!empty($this->_head)) {
            ksort($this->_head);
            foreach (array_keys($this->_head) as $i) {
                foreach (array_keys($this->_head[$i]) as $j) {
                    $html[] = $this->_head[$i][$j];
                }
            }
        }
        if ($clear) $this->_head = [];
        return empty($html) ? '' : implode(PHP_EOL, $html);
    }

    public function addJsFile($file, $handle, $dependency = null, $package = null, $inFooter = true, $vendor = false)
    {
        $url = $package !== false ? $this->getAssetsUrl($package, $vendor) . '/js/' . $file : $file;
        $this->_loadJsFile($url, $handle, $dependency, $inFooter);
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addJsFile($handle, $file, $dependency, $package, $inFooter, $vendor);
        }
        return $this;
    }

    public function addJs($js, $onDomReady = true, $index = null)
    {
        $i = isset($index) ? $index : ++$this->_jsIndex;
        $this->_js[$onDomReady ? 1 : 0][$i][] = $js;
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addJs($js, $onDomReady, $index);
        }
        return $this;
    }

    public function addJsInline($dependency, $js)
    {
        $this->_loadJsInline($dependency, $js);
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addJsInline($dependency, $js);
        }
        return $this;
    }

    public function addCssFile($file, $handle, $dependency = null, $package = null, $media = null, $vendor = false)
    {
        $url = $package !== false ? $this->getAssetsUrl($package, $vendor) . '/css/' . $file : $file;
        $this->_loadCssFile($url, $handle, $dependency, isset($media) ? $media : 'all');
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addCssFile($handle, $file, $dependency, $package, $media, $vendor);
        }
        return $this;
    }

    public function addCss($css, $targetHandle = null)
    {
        $this->_css[isset($targetHandle) ? $targetHandle : 'drts'][] = $css;
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addCcss($css, $targetHandle);
        }
        return $this;
    }

    public function getCss($clear = true)
    {
        $css = $this->_css;
        if ($clear) {
            $this->_css = [];
            $this->_cssIndex = 0;
        }
        return $css;
    }

    public function getJsHtml($clear = true)
    {
        if (empty($this->_js)) {
            if ($clear) {
                $this->_js = [];
                $this->_jsIndex = 0;
            }
            return '';
        }

        $html = ['<script type="text/javascript">'];
        if (!empty($this->_js[0])) {
            ksort($this->_js[0]);
            foreach (array_keys($this->_js[0]) as $k) {
                foreach (array_keys($this->_js[0][$k]) as $i) {
                    $html[] = $this->_js[0][$k][$i];
                }
            }
        }
        if (!empty($this->_js[1])) {
            ksort($this->_js[1]);
            if (Request::isXhr()) {
                $html[] = 'jQuery(function($) {';
            } else {
                $html[] = 'document.addEventListener("DOMContentLoaded", function(event) { var $ = jQuery;';
            }
            foreach (array_keys($this->_js[1]) as $k) {
                foreach (array_keys($this->_js[1][$k]) as $i) {
                    $html[] = $this->_js[1][$k][$i];
                }
            }
            $html[] = '});';
        }
        $html[] = '</script>';
        if ($clear) {
            $this->_js = [];
            $this->_jsIndex = 0;
        }
        return implode(PHP_EOL, $html);
    }

    public function addFlash(array $flash)
    {
        foreach ($flash as $_flash) {
            $this->addJs(
                sprintf(
                    'DRTS.flash("%s", "%s");',
                    htmlspecialchars(str_replace("\r\n", '', $_flash['msg']), ENT_QUOTES, 'UTF-8'),
                    $_flash['level']
                 )
             );
        }
        return $this;
    }

    public function loadJqueryUiJs(array $components)
    {
        $this->_loadJqueryUiJs($components);
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addJqueryUiJs($components);
        }
        return $this;
    }

    public function loadImagesLoadedJs()
    {
        $this->_loadImagesLoadedJs();
        if ($this->_trackedAssets) {
            $this->_trackedAssets->addImagesLoadedJs();
        }
        return $this;
    }

    protected function _getRenderCacheId($path, array $attributes, $cache, $title)
    {
        $attr = serialize($attributes);
        // Append current entity ID if settings contain _current_
        if (isset($GLOBALS['drts_entity'])
            && strpos($attr, '_current_') && isset($GLOBALS['drts_entity'])
        ) {
            $attr .= $GLOBALS['drts_entity']->getId();
        }
        return 'core_platform_render_' . md5((string)$path . $attr) . (string)$title . (string)$cache;
    }

    public function trackAssets($bool = true)
    {
        $this->_trackedAssets = $bool ? new Assets() : null;

        return $this;
    }

    public function getTrackedAssets()
    {
        return $this->_trackedAssets ? $this->_trackedAssets->getAssets() : [];
    }

    public function render($path, array $attributes = [], $cache = false, $title = null, $container = null, $renderAssets = true)
    {
        // Render and cache
        if ((!$cacheable = !empty($cache))
            || (!$cached = $this->getCache($cache_id = $this->_getRenderCacheId($path, $attributes, $cache, $title), 'content'))
        ) {
            if (!isset($container)) {
                $container = 'drts-platform-render-' . uniqid() . '-' . ++$this->_renderCount;
            }
            $this->trackAssets()->addJs('DRTS.init($("#' . $container . '"));', true, -99);
            $cached = [
                'container' => $container,
                'content' => $this->_render($container, $path, $attributes, $title, $cacheable),
                'assets' => $this->getTrackedAssets(),
            ];
            $this->trackAssets(false);

            if (!empty($cache)
                && $cacheable
                && !$this->isAdmin() // WordPress shortcodes may run on the admin side
            ) {
                if (!isset($cache_id)) $cache_id = $this->_getRenderCacheId($path, $attributes, $cache, $title);
                $this->setCache($cached, $cache_id, is_numeric($cache) && $cache > 1 ? $cache : 86400, 'content');
            }

            // Assets already loaded by _render(), so no need to load them again
            unset($cached['assets']);
        }

        // Load assets if needed
        if (!empty($cached['assets'])) {
            Assets::load($this, $cached['assets']);
        }
        if ($renderAssets) {
            $this->loadDefaultAssets();
            if ($js_html = $this->getJsHtml()) {
                $cached['content'] .= PHP_EOL . $js_html;
            }
            if ($head_html = $this->getHeadHtml()) {
                $cached['content'] = $head_html . PHP_EOL . $cached['content'];
            }
        }

        if (!strlen($cached['content'])) return;

        $class = 'drts drts-main';
        if ($this->isRtl()) $class .= ' drts-rtl';
        return '<div id="' . $cached['container'] . '" class="' . $class . '">' . $cached['content'] . '</div>';
    }

    protected function _render($container, $path, array $attributes = [], $title = null, &$cacheable = true)
    {
        try {
            if ($path instanceof Url) {
                if (!$path->route) {
                    throw new Exception\InvalidArgumentException('URL path may not be empty');
                }
                $params = $path->params;
                $path = $path->route;
            } elseif (is_array($path)) {
                $params = $path['params'];
                $path = $path['path'];
            } else {
                $params = null;
            }

            // Create context
            $context = new Context();
            $context->setContainer('#' . $container)
                ->setRequest(new Request(true, true, $params))
                ->setAttributes($attributes)
                ->setTitle($title)
                ->isCaching($cacheable);

            // Run Sabai
            $response = $this->getApplication()->setCurrentScriptName('main')->run(new MainRoutingController(), $context, $path);

            // Cacheable if response is view
            $cacheable = $context->isView();

            // Render output
            ob_start();
            $response->send($context);
            $content = ob_get_clean();
            if (false !== $title // title disabled explicitly if false
                && ($title = $context->getTitle(false))
            ) {
                $content = '<h2>' . $title . '</h2>' . PHP_EOL . $content;
            }
            return $content;
        } catch (\Exception $e) {
            $cacheable = false;
            $this->getApplication()->logError($e);
            if ($this->isAdministrator()
                || $this->isDebugEnabled()
            ) {
                return sprintf(
                    '<p>%s</p><p><pre>%s</pre></p>',
                    htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8')
                );
            } else {
                return sprintf('<p>%s</p>', 'An error occurred while processing the request. Please contact the administrator of the website for further information.');
            }
        }
    }

    abstract protected function _loadJqueryJs();
    abstract protected function _loadCoreJs();
    abstract protected function _loadCoreCss($type);
    abstract protected function _loadJsFile($url, $handle, $dependency, $inFooter);
    abstract protected function _loadJsInline($dependency, $js);
    abstract protected function _loadCssFile($url, $handle, $dependency, $media);
    abstract protected function _loadJqueryUiJs(array $components);
    abstract protected function _loadImagesLoadedJs();
    abstract public function getRouteParam();
    abstract public function getPageParam();
    abstract public function hasBootstrapCss();
    abstract public function getUserIdentityFetcher();
    abstract public function getCurrentUser();
    abstract public function setCurrentUser($userId);
    abstract public function isAdministrator($userId = null);
    abstract public function getAdministrators();
    abstract public function getPermissions($userId);
    abstract public function hasPermission($userId, $permission);
    abstract public function guestHasPermission($permission);
    abstract public function getWriteableDir();
    abstract public function getSitePath();
    abstract public function getPackagePath();
    abstract public function getPackageVersion($package);
    abstract public function getSiteName();
    abstract public function getSiteVersion();
    abstract public function getSiteEmail();
    abstract public function getSiteUrl();
    abstract public function getSiteAdminUrl();
    abstract public function getAssetsUrl($package = null, $vendor = false);
    abstract public function getAssetsDir($package = null, $vendor = false);
    abstract public function getLoginUrl($redirect);
    abstract public function getLogoutUrl();
    abstract public function getRegisterUrl($redirect = '');
    abstract public function getLostPasswordUrl($redirect = '');
    abstract public function getRegisterForm();
    abstract public function isUserRegisterable();
    abstract public function registerUser(array $values);
    abstract public function loginUser(array $credentials);
    abstract public function getPrivacyPolicyLink();
    abstract public function getDB();
    abstract public function mail($to, $subject, $body, array $options = []);
    abstract public function setSessionVar($name, $value, $userId = null);
    abstract public function getSessionVar($name, $userId = null);
    abstract public function deleteSessionVar($name, $userId = null);
    abstract public function setUserMeta($userId, $name, $value);
    abstract public function getUserMeta($userId, $name, $default = null);
    abstract public function deleteUserMeta($userId, $name);
    abstract public function getUsersByMeta($name, $limit = 20, $offset = 0, $order = 'DESC', $isNumber = true);
    abstract public function setCache($data, $id, $lifetime = null, $group = 'settings');
    abstract public function getCache($id, $group = 'settings');
    abstract public function deleteCache($id, $group = 'settings');
    abstract public function clearCache($settings = null, $name = null);
    abstract public function getLocale();
    abstract public function isRtl();
    abstract public function setOption($name, $value, $autoload = true);
    abstract public function getOption($name, $default = null);
    abstract public function deleteOption($name);
    abstract public function clearOptions($prefix = '');
    abstract public function getCustomAssetsDir();
    abstract public function getCustomAssetsDirUrl($index);
    abstract public function getUserProfileHtml($userId);
    abstract public function getSiteToSystemTime($timestamp);
    abstract public function getSystemToSiteTime($timestamp);
    abstract public function unzip($from, $to);
    abstract public function updateDatabase($schema, $previousSchema = null);
    abstract public function isAdmin();
    abstract public function getCookieDomain();
    abstract public function getCookiePath();
    abstract public function htmlize($text, $inlineTagsOnly = false, $forCaching = false);
    abstract public function getStartOfWeek();
    abstract public function getDateFormat();
    abstract public function getTimeFormat();
    abstract public function getDate($format, $timestamp, $isUTC = true);
    abstract public function getTimeZone();
    abstract public function registerString($str, $name, $domain = 'directories');
    abstract public function unregisterString($name, $domain = 'directories');
    abstract public function translateString($str, $name, $domain = 'directories', $lang = null);
    abstract public function getLanguages();
    abstract public function getDefaultLanguage();
    abstract public function getCurrentLanguage();
    abstract public function isTranslatable($entityType, $bundleName);
    abstract public function getTranslatedId($entityType, $bundleName, $id, $lang);
    abstract public function getLanguageFor($entityType, $bundleName, $id);
    abstract public function isAdminAddTranslation();
    abstract public function isDebugEnabled();
    abstract public function isAmpEnabled($bundleName);
    abstract public function isAmp();
    abstract public function hasSlug($component, $slug, $lang = null);
    abstract public function getSlug($component, $slug, $lang = null);
    abstract public function getTitle($component, $name, $lang = null);
    abstract public function remoteGet($url, array $args = []);
    abstract public function remotePost($url, array $params = [], array $args = []);
    abstract public function anonymizeEmail($email);
    abstract public function anonymizeUrl($url);
    abstract public function anonymizeIp($ip);
    abstract public function anonymizeText($text);
    abstract public function downloadUrl($url, $save = false, $title = null, $ext = null);
}
