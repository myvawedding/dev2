<?php
namespace SabaiApps\Directories;

use SabaiApps\Directories\Platform\AbstractPlatform;

class Assets
{
    protected $_assets = [];
    
    public function addHead($handle, $head, $index = 10)
    {
        $this->_assets['head'][$handle] = [$head, $index];
    }
    
    public function addJsFile($handle, $file, $dependency = null, $package = null, $inFooter = true, $vendor = false)
    {
        $this->_assets['js_files'][$handle] = [$file, $dependency, $package, $inFooter, $vendor];
    }
    
    public function addJs($js, $onDomReady = true, $index = 10)
    {
        $this->_assets['js'][] = [$js, $onDomReady, $index];
    }

    public function addJsInline($dependency, $js)
    {
        $this->_assets['js_inline'][] = [$dependency, $js];
    }
    
    public function addCssFile($handle, $file, $dependency = null, $package = null, $media = null, $vendor = false)
    {
        $this->_assets['css_files'][$handle] = [$file, $dependency, $package, $media, $vendor];
    }
    
    public function addCss($css, $targetHandle = null)
    {
        $this->_assets['css'][] = [$css, $targetHandle];
    }
    
    public function getAssets()
    {
        return $this->_assets;
    }
    
    public function addJqueryUiJs($component)
    {
        foreach ((array)$component as $_component) {
            $this->_sssets['jquery_ui_js_components'][$_component] = $_component;
        }
    }
    
    public function addImagesLoadedJs()
    {
        $this->_assets['images_loaded_js'] = true;
    }
    
    public static function load(AbstractPlatform $platform, array $assets)
    {
         if (!empty($assets['head'])) {
            foreach ($assets['head'] as $handle => $head) {
                $platform->addHead($head[0], $handle, $head[1]);
            }
        }
        if (!empty($assets['js_files'])) {
            foreach ($assets['js_files'] as $handle => $file) {
                $platform->addJsFile($file[0], $handle, $file[1], $file[2], $file[3], $file[4]);
            }
        }
        if (!empty($assets['css_files'])) {
            foreach ($assets['css_files'] as $handle => $file) {
                $platform->addCssFile($file[0], $handle, $file[1], $file[2], $file[3], $file[4]);
            }
        }
        if (!empty($assets['js_inline'])) {
            foreach ($assets['js_inline'] as $js) {
                $platform->addJsInline($js[0], $js[1]);
            }
        }
        if (!empty($assets['js'])) {
            foreach ($assets['js'] as $js) {
                $platform->addJs($js[0], $js[1], $js[2]);
            }
        }
        if (!empty($assets['css'])) {
            foreach ($assets['css'] as $css) {
                $platform->addCss($css[0], $css[1]);
            }
        }
        if (!empty($assets['jquery_ui_js_components'])) {
            $platform->loadJqueryUiJs($assets['jquery_ui_js_components']);
        }
        if (!empty($assets['images_loaded_js'])) {
            $platform->loadImagesLoadedJs();
        }
    }
}