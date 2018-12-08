<?php
namespace SabaiApps\Directories\Component\Map\ViewMode;

use SabaiApps\Directories\Component\View\Mode\AbstractMode;
use SabaiApps\Directories\Component\Entity;

class MapViewMode extends AbstractMode
{
    protected function _viewModeInfo()
    {
        return [
            'label' => _x('Map', 'view mode label', 'directories'),
            'icon' => 'fas fa-map-marker-alt',
            'default_settings' => [
                'template' => $this->_application->getPlatform()->getAssetsDir('directories') . '/templates/map_entities',
                'map' => [
                    'coordinates_field' => null,
                    'height' => 600,
                    'fullscreen' => true,
                    'infobox_width' => 240,
                    'view_marker_icon' => 'default',
                ],
            ],
            'displays' => [],
            'filter_fullscreen' => false,
        ];
    }
    
    public function viewModeSupports(Entity\Model\Bundle $bundle)
    {
        if (!parent::viewModeSupports($bundle)) return false;

        foreach ($this->_application->Entity_Field($bundle) as $field) {
            if (!$field_type = $this->_application->Field_Type($field->getFieldType(), true)) continue;

            if ($field_type instanceof \SabaiApps\Directories\Component\Map\FieldType\ICoordinates) {
                return true;
            }
        }
        return false;
    }

    public function viewModeSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $form = [
            'map' => [
                'coordinates_field' => [
                    '#type' => 'select',
                    '#title' => __('Map coordinates field', 'directories'),
                    '#horizontal' => true,
                    '#options' => $this->_application->Entity_Field_options($bundle, ['interface' => 'Map\FieldType\ICoordinates']),
                    '#default_value' => $settings['map']['coordinates_field'],
                    '#required' => true,
                    '#weight' => 3,
                ],
                'height' => [
                    '#type' => 'slider',
                    '#title' => __('Map height', 'directories'),
                    '#default_value' => $settings['map']['height'],
                    '#min_value' => 100,
                    '#max_value' => 1000,
                    '#step' => 10,
                    '#integer' => true,
                    '#field_suffix' => 'px',
                    '#horizontal' => true,
                    '#weight' => 10,
                ],
                'infobox_width' => [
                    '#type' => 'slider',
                    '#min_value' => 100,
                    '#max_value' => 500,
                    '#step' => 10,
                    '#default_value' => $settings['map']['infobox_width'],
                    '#title' => __('Map infobox width', 'directories'),
                    '#field_suffix' => 'px',
                    '#integer' => true,
                    '#horizontal' => true,
                    '#weight' => 21,
                ],
            ],
        ];
        $marker_icon_options = $this->_application->Map_Marker_iconOptions($bundle);
        if (count($marker_icon_options) > 1) {
            $form['map']['view_marker_icon'] = [
                '#type' => 'select',
                '#title' => __('Map marker icon', 'directories'),
                '#default_value' => $settings['map']['view_marker_icon'],
                '#options' => $marker_icon_options,
                '#weight' => 12,
                '#horizontal' => true,
            ];
        }

        return $form;
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