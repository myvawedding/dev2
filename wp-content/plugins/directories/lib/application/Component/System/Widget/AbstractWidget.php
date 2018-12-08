<?php
namespace SabaiApps\Directories\Component\System\Widget;

use SabaiApps\Directories\Application;

abstract class AbstractWidget implements IWidget
{
    protected $_application, $_name, $_cacheable = true;
    
    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function systemWidgetInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_systemWidgetInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    abstract protected function _systemWidgetInfo();
        
    public function systemWidgetSettings(array $settings)
    {
        $ret = [];
        if ($this->_cacheable) {
            $ret['no_cache'] = array(
                '#type' => 'checkbox',
                '#title' => __('Do not cache output', 'directories'),
                '#default_value' => false,
                '#weight' => 99,
            );
        }
        if ($widget_settings = $this->_getWidgetSettings($settings)) {
            $ret += $widget_settings;
        }
        return $this->_application->Filter('widgets_widget_settings', $ret, array($this->_name));
    }
    
    public function systemWidgetContent(array $settings)
    {        
        if (!$this->_cacheable
            || !empty($settings['no_cache'])
        ) {
            $ret = $this->_getWidgetContent($settings);
        } else {
            if (!$cache_id = $this->_getCacheId($settings)) return;
            
            if (false === $ret = $this->_application->getPlatform()->getCache($cache_id, 'content')) {
                $ret = $this->_getWidgetContent($settings);
                $this->_application->getPlatform()->setCache($ret, $cache_id, 3600, 'content');
            }
        }
        if (empty($ret)) return;

        return array(
            'content' => $this->_application->Filter('widgets_widget_content', $ret, array($this->_name, $settings)),
            'link' => $this->_getWidgetLink($settings),
            'class' => $this->_getWidgetClass($settings),
        );
    }
    
    public function systemWidgetOnSettingsSaved(array $settings, array $oldSettings)
    {
        if (!$this->_cacheable) return;

        $this->_application->getPlatform()->deleteCache($this->_getCacheId($oldSettings), 'content');
    }
    
    protected function _getCacheId(array $settings)
    {
        return 'widgets_widget_' . $this->_name . '_' . md5(serialize($settings));
    }
    
    protected function _getWidgetLink(array $settings){}
    protected function _getWidgetClass(array $settings){}
    
    abstract protected function _getWidgetSettings(array $settings);
    abstract protected function _getWidgetContent(array $settings);
}