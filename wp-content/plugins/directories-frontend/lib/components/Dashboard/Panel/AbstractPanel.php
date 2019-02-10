<?php
namespace SabaiApps\Directories\Component\Dashboard\Panel;

use SabaiApps\Directories\Application;
use SabaiApps\Framework\User\AbstractIdentity;

abstract class AbstractPanel implements IPanel
{
    protected $_application, $_name, $_info, $_links = [];

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function dashboardPanelInfo($key = null)
    {
        if (!isset($this->_info)) $this->_info = (array)$this->_dashboardPanelInfo();

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function dashboardPanelOnLoad(){}
    
    public function dashboardPanelContent($link, array $params, AbstractIdentity $identity = null)
    {
        return print_r($link, true);
    }
    
    public function dashboardPanelLinks(AbstractIdentity $identity = null)
    {
        $user_id = isset($identity) ? $identity->id : 0;
        if (!isset($this->_links[$user_id])) {
            if ($links = $this->_dashboardPanelLinks($identity)) {
                uasort($links, function ($a, $b) {
                    if ($a['weight'] === $b['weight']) return strnatcmp($a['title'], $b['title']);

                    return $a['weight'] < $b['weight'] ? -1 : 1;
                });
                $this->_links[$user_id] = $links;
            } else {
                $this->_links[$user_id] = [];
            }
        }
        return $this->_links[$user_id];
    }

    abstract protected function _dashboardPanelInfo();
    abstract protected function _dashboardPanelLinks(AbstractIdentity $identity = null);
    
    public function panelHtmlLinks($firstActive = true, $badge = false, AbstractIdentity $identity = null)
    {
        $links = $this->dashboardPanelLinks($identity);
        foreach (array_keys($links) as $link_name) {
            $link =& $links[$link_name];
            $link['title'] = $this->_application->H($link['title']);
            if (isset($link['icon'])) {
                $link['title'] = '<span><i class="fa-fw ' . $link['icon'] . '"></i> ' . $link['title'] . '</span>';
            }
            if (isset($link['count'])) {
                if ($badge) {
                    $link['title'] .= sprintf(' <span class="%1$sbadge %1$sbadge-pill %1$sbadge-secondary">%2$d</span>', DRTS_BS_PREFIX, $link['count']);
                } else {
                    $link['title'] .= ' (' . $link['count'] . ')';
                }
            }
            $link['attr'] = [
                'class' => 'drts-dashboard-panel-link drts-dashboard-panel-link-' . str_replace('_', '-', $link_name),
                'data-url' => $this->_application->getComponent('Dashboard')->getPanelUrl($this->_name, $link_name, '', [], true, $identity),
                'data-panel-name' => $this->_name,
                'data-link-name' => $link_name,
            ];
            if ($firstActive) {
                if (is_string($firstActive)) {
                    if ($link_name === $firstActive) {
                        $link['attr']['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
                    }
                } else {
                    if (!isset($is_first)) {
                        $link['attr']['class'] .= ' ' . DRTS_BS_PREFIX . 'active';
                        $is_first = false;
                    }
                }
            }
        }
        
        return $links;
    }
}