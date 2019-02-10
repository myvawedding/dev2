<?php
namespace SabaiApps\Directories\Component\Map\FieldRenderer;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;

class MapFieldRenderer extends AbstractRenderer
{
    protected $_isStreetView = false;
    protected static $_count = 0;
    
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => $this->_isStreetView
                ? __('Street view renderer', 'directories')
                : __('Map renderer', 'directories'),
            'field_types' => array('map_map',  'location_address'),
            'default_settings' => array(
                'height' => 300,
                'view_marker_icon' => 'default',
                'directions' => true,
            ),
            'separatable' => false,
            'accept_multiple' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {        
        $form = array(
            'height' => array(
                '#type' => 'number',
                '#size' => 4,
                '#integer' => true,
                '#field_suffix' => 'px',
                '#min_value' => 100,
                '#max_value' => 1000,
                '#default_value' => $settings['height'],
                '#title' => __('Map height', 'directories'),
                '#weight' => 1,
            ),
        );
        $marker_icon_options = $this->_application->Map_Marker_iconOptions($field->Bundle);
        if (count($marker_icon_options) > 1) {
            $form['view_marker_icon'] = [
                '#type' => 'select',
                '#title' => __('Map marker icon', 'directories'),
                '#default_value' => $settings['view_marker_icon'],
                '#options' => $marker_icon_options,
                '#weight' => 5,
            ];
        }
        if (!$this->_isStreetView) {
            $form += array(
                'directions' => array(
                    '#type' => 'checkbox',
                    '#default_value' => !empty($settings['directions']),
                    '#title' => __('Enable directions search', 'directories'),
                    '#weight' => 10,
                ),
            );
        }
        $form += [
            'custom_infobox_addr_format' => [
                '#type' => 'checkbox',
                '#title' => __('Customize format of address on infobox', 'directories'),
                '#default_value' => !empty($settings['custom_infobox_addr_format']),
                '#weight' => 20,
            ],
            'infobox_addr_format' => [
                '#type' => 'textfield',
                '#description' => sprintf(
                    __('Available tags: %s', 'directories'),
                    implode(' ', $this->_application->Location_FormatAddress_tags($field->Bundle))
                ),
                '#default_value' => isset($settings['infobox_addr_format']) ? $settings['infobox_addr_format'] : '{full_address}',
                '#states' => [
                    'visible' => [
                        sprintf(
                            'input[name="%s"]',
                            $this->_application->Form_FieldName(array_merge($parents, ['custom_infobox_addr_format']))
                        ) => ['type' => 'checked', 'value' => true],
                    ],
                ],
                '#weight' => 21,
            ],
        ];
        
        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        if (!$map_api = $this->_application->Map_Api()) return;

        if ($this->_isStreetView) {
            if (!$map_api instanceof \SabaiApps\Directories\Component\Map\Api\GoogleMapsApi) {
                return '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-danger ">' . __('Street view is available with Google Maps only.', 'directories') . '</div>';
            }
            $settings = array('street_view' => true, 'infobox' => false) + $settings;
        }
        return $this->_renderMap($entity, $field, $values, $settings);
    }
    
    protected function _renderMap(IEntity $entity, IField $field, array $values, array $settings)
    {        
        $id = 'drts-map-map-' . self::$_count++;
        $config = $this->_application->getComponent('Map')->getConfig('map');
        $marker_settings = [
            'marker_size' => $config['marker_size'],
            'coordinates_field' => $field->getFieldName(),
        ] + $settings;
        if (!$markers = $this->_application->Map_Marker($entity, $marker_settings, $id)) return;

        $this->_application->Action('map_render_field_map', [$field, $settings]);
        
        unset($config['api']);
        $settings += array(
            'height' => 300,
        );
        $settings += $config;

        if (empty($settings['directions'])) {
            $this->_application->Map_Api_load();
            return sprintf(
                '<div id="%s" style="position:relative;">
    <div class="drts-map-container">
        <div class="drts-map-map" style="height:%dpx;"></div>
    </div>
</div>
<script type="text/javascript">
%s
</script>',
                $id,
                $settings['height'],
                $this->_getJs($id, $markers, $settings)
            );
        }
        
        $this->_application->Map_Api_load();
        $this->_application->getPlatform()
            ->addCssFile('map-directions.min.css', 'drts-map-directions', array('drts'), 'directories');
        $multi_address = count($markers) > 1; 
        if ($multi_address) {
            $addr_options = [];
            foreach (array_keys($markers) as $key) {
                $selected = $key === 0 ? ' selected="selected"' : '';
                $option = strlen($values[$key]['address']) ? $this->_application->H($values[$key]['address']) : $values[$key]['lat'] . ',' . $values[$key]['lat'];
                $addr_options[] = '<option value="' . $entity->getId() . '-' . $key . '"' . $selected . '>' . $option . '</option>';
            }
            $addr_select = sprintf(
                '<div class="%1$smt-0 %1$smb-2 %1$salign-middle">
    <select class="drts-map-directions-destination %1$sform-control">
    %2$s
    </select>
</div>',
                DRTS_BS_PREFIX,
                implode(PHP_EOL, $addr_options)
            );
        } else {
            $addr_select = '<input type="hidden" value="' . $entity->getId() . '-0" class="drts-map-directions-destination" />';
        }
        return sprintf(
            '<div id="%1$s" style="position:relative;">
    <div class="drts-map-container">
        <div class="drts-map-map" style="height:%2$dpx;"></div>
    </div>
    <form class="drts-map-directions %3$spx-2 %3$spt-2">
        <div class="%14$s">
            <div class="%4$s %3$smt-0 %3$smb-2 %3$salign-middle">
                <input type="text" class="%3$sform-control drts-map-directions-input" value="" placeholder="%5$s" />
            </div>
            %6$s
            <div class="%7$s %3$smt-0 %3$smb-2">
                <div class="%3$sbtn-group %3$sbtn-block %3$salign-middle">
                    <button class="%3$sbtn %3$sbtn-block %3$sbtn-primary drts-directory-btn-directions drts-map-directions-trigger">%8$s</button>
                    <button class="%3$sbtn %3$sbtn-primary %3$sdropdown-toggle %3$sdropdown-toggle-split" data-toggle="%3$sdropdown" aria-expanded="false"></button>
                    <div class="%3$sdropdown-menu %3$sdropdown-menu-right">
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="driving">%9$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="transit">%10$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="walking">%11$s</a>
                        <a class="%3$sdropdown-item drts-map-directions-trigger" data-travel-mode="bicycling">%12$s</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript">
%13$s
</script>',
            $id,
            $settings['height'],
            DRTS_BS_PREFIX,
            $multi_address ? '' : ' drts-col-md-8',
            $this->_application->H(__('Enter a location', 'directories')),
            $addr_select,
            $multi_address ? '' : ' drts-col-md-4',
            $this->_application->H(__('Get Directions', 'directories')),
            $this->_application->H(__('By car', 'directories')),
            $this->_application->H(__('By public transit', 'directories')),
            $this->_application->H(__('Walking', 'directories')),
            $this->_application->H(__('Bicycling', 'directories')),
            $this->_getJs($id, $markers, $settings),
            $multi_address ? '' : ' drts-row'
        );
    }
    
    protected function _getJs($id, $markers, $settings)
    {
        return sprintf(
            '%1$s
    var renderMap = function (container) {
        var map = DRTS.Map.api.getMap(container, %3$s)
            .setMarkers(%4$s)
            .draw(%5$s);
        %7$s
    }
    var $map = $("#%2$s");
    if ($map.is(":visible")) {
        renderMap($map);
    } else {
        var pane = $map.closest(".%6$stab-pane");
        if (pane.length) {
            $("#" + pane.attr("id") + "-trigger").on("shown.bs.tab", function(e, data){
                renderMap($map);
            });
        }
    }
    $(DRTS).on("loaded.sabai", function (e, data) {
        if (data.target.find("#%2$s").length) {
            renderMap();
        }
    });
});',
            Request::isXhr() ? 'jQuery(function ($) {' : 'document.addEventListener("DOMContentLoaded", function() { var $ = jQuery;',
            $id,
            $this->_application->JsonEncode(array(
                'marker_clusters' => false,
                'infobox' => !isset($settings['infobox']) || $settings['infobox'],
            ) + $settings),
            $this->_application->JsonEncode($markers),
            $this->_application->JsonEncode(array('street_view' => !empty($settings['street_view']))),
            DRTS_BS_PREFIX,
            empty($settings['directions']) ? '' : 'DRTS.Map.enableDirections(map);'
        );
    }
}
