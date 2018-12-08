<?php
namespace SabaiApps\Directories\Component\Dashboard\ViewMode;

use SabaiApps\Directories\Component\View\Mode\AbstractMode;
use SabaiApps\Directories\Component\Entity;

class DashboardViewMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return [
            'label' => 'Dashboard',
            'icon' => 'fas fa-tasks',
            'default_settings' => [
                'template' => 'view_entities_table',
                'display' => 'dashboard_row',
            ],
            'displays' => $this->_getDisplays(),
            'system' => true,
        ];
    }
    
    protected function _getDisplays()
    {
        return [
            'dashboard_row' => _x('Dashboard Row', 'display name', 'directories-frontend'),
        ];
    }
    
    public function viewModeSupports(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['internal'])
            && empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['is_user']);
    }
    
    public function viewModeNav(Entity\Model\Bundle $bundle, array $settings)
    {   
        return [
            [
                [['num'], ['status', 'sort', 'add', 'dashboard_logout']]
            ], // header
            [
                [[], ['perpages', 'pagination']]
            ], // footer
        ];
    }
}