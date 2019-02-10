<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Component\Entity;

class ListMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return array(
            'label' => _x('List', 'view mode label', 'directories'),
            'default_settings' => array(
                'template' => 'view_entities_list',
                'display' => 'summary',
                'list_grid' => true,
                'list_no_row' => false,
                'list_grid_cols' => ['num' => 'responsive', 'num_responsive' => ['xs' => 2, 'lg' => 3, 'xl' => 4]],
                'list_grid_gutter_width' => '',
                'list_layout_switch_cookie' => 'drts-entity-view-list-layout',
                'list_grid_default' => false,
            ),
            'default_display' => 'summary',
            'displays' => $this->_getDisplays(),
            'mapable' => true,
        );
    }

    protected function _getDisplays()
    {
        return array(
            'summary' => _x('Summary', 'display name', 'directories'),
        );
    }

    public function viewModeSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $list_grid_selector = sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('list_grid'))));
        return array(
            'list_grid' => array(
                '#title' => __('Enable grid layout', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_grid']),
                '#horizontal' => true,
            ),
            'list_no_row' => array(
                '#title' => __('Disable row layout', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_no_row']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        $list_grid_selector => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
            'list_grid_default' => array(
                '#title' => __('Set grid layout as default', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['list_grid_default']),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        $list_grid_selector => array('type' => 'checked', 'value' => true),
                        sprintf('input[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, array('list_no_row')))) => array('type' => 'checked', 'value' => false),

                    ),
                ),
            ),
            'list_grid_cols' => $this->_getGridColumnSettingsForm($settings['list_grid_cols'], array_merge($parents, ['list_grid_cols']), __('Grid layout columns', 'directories'), 'num') + array(
                '#states' => array(
                    'visible' => array(
                        $list_grid_selector => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
            'list_grid_gutter_width' => array(
                '#title' => __('Grid layout gutter width', 'directories'),
                '#type' => 'select',
                '#default_value' => $settings['list_grid_gutter_width'],
                '#options' => array(
                    'none' => __('None', 'directories'),
                    '' => __('Default', 'directories'),
                    'md' => __('Medium width', 'directories'),
                    'lg' => __('Large width', 'directories'),
                ),
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        $list_grid_selector => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
        );
    }

    public function viewModeNav(Entity\Model\Bundle $bundle, array $settings)
    {
        return [
            [
                [['filters'], []],
                [['filter', 'num'], ['layout_switch', 'sort', 'add']]
            ], // header
            [
                [[], ['perpages', 'pagination']]
            ], // footer
        ];
    }

    protected function _getGridColumnSettingsForm(array $settings, array $parents = [], $label = null, $name = null)
    {
        if (!isset($name)) $name = $this->_name . '_cols';
        return $this->_application->GridColumnSettingsForm($name, $settings, $parents, $label, [2, 3, 4, 6]);
    }

    public function getGridClass($cols, $dw = false)
    {
        $prefix = $dw ? 'drts-dw-' : 'drts-col-';
        if (!is_array($cols)) return $prefix . intval(12 / $cols);

        $classes = [];
        if (isset($cols['xs'])) {
            $_size = 12 / $cols['xs'];
            unset($cols['xs']);
        } else {
            $_size = 12;
        }
        $classes[] = $prefix . $_size;
        foreach (array_keys($cols) as $_width) {
            if (!is_numeric($cols[$_width])) continue;

            $classes[] = $prefix . $_width . '-' . intval(12 / $cols[$_width]);
        }
        return implode(' ', $classes);
    }
}
