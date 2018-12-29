<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity;

class MasonryMode extends ListMode
{
    protected function _viewModeInfo()
    {
        return array(
            'label' => _x('Masonry', 'view mode label', 'directories'),
            'default_settings' => array(
                'template' => 'view_entities_masonry',
                'display' => 'summary',
                'masonry_cols' => 'responsive',
                'masonry_cols_responsive' => ['xs' => 2, 'lg' => 3, 'xl' => 4],
            ),
            'default_display' => 'summary',
            'assets' => array(
                'css' => array(
                    'driveway' => array('driveway.min.css', null, null, true),
                ),
            ),
        ) + parent::_viewModeInfo();
    }

    public function viewModeSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        return $this->_getGridColumnSettingsForm($settings, $parents);
    }

    public function viewModeNav(Entity\Model\Bundle $bundle, array $settings)
    {
        return [
            [
                [['filters'], []],
                [['filter', 'num'], ['sort', 'add']]
            ], // header
            [
                [[], ['perpages', 'pagination']]
            ], // footer
        ];
    }
}
