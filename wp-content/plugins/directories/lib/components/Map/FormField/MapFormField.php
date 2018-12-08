<?php
namespace SabaiApps\Directories\Component\Map\FormField;

use SabaiApps\Directories\Component\Form;

class MapFormField extends Form\Field\FieldsetField
{
    protected static $_elements, $_count = 0;

    public function formFieldInit($name, array &$data, Form\Form $form)
    {
        $map = [
            'scrollwheel' => $this->_application->getComponent('Map')->getConfig('map', 'scrollwheel') ? 1 : 0,
        ];
        if (!empty($data['#default_value']['lat']) && !empty($data['#default_value']['lng'])) {
            $map['center-lat'] = $map['lat'] = str_replace(',', '.', floatval($data['#default_value']['lat']));
            $map['center-lng'] = $map['lng'] = str_replace(',', '.', floatval($data['#default_value']['lng']));
        } else {
            $map['center-lng'] = str_replace(',', '.', isset($data['#center_longitude']) ? floatval($data['#center_longitude']) : -73.95144);
            $map['center-lat'] = str_replace(',', '.', isset($data['#center_latitude']) ? floatval($data['#center_latitude']) : 40.69847);
            $map['lat'] = $map['lng'] = '';
        }
        if (!empty($data['#default_value']['zoom'])) {
            $map['zoom'] = $data['#default_value']['zoom'];
        } else {
            $map['zoom'] = isset($data['#zoom']) ? intval($data['#zoom']) : 10;
        }
        $map['map-type'] = isset($data['#map_type']) && in_array(strtolower($data['#map_type']), array('satellite', 'hybrid', 'osm'))
            ? $data['#map_type']
            : 'roadmap';
        $data = array(
            '#tree' => true,
            '#group' => true,
            '#children' => array(
                0 => [],
            ),
            '#data' => $map,
        ) + $data;
        if (!isset($data['#id'])) {
            $data['#id'] = 'drts-map-form-map-field-' . ++self::$_count;
        }
        $data['#children'][0] += array(
            'map' => array(
                '#type' => 'markup',
                '#markup' => sprintf(
                    '<div style="height:%dpx;" class="drts-map-map" data-form-field-name="__FORM_FIELD_NAME__"></div>',
                    empty($data['#map_height']) ? 300 : $data['#map_height']
                ),
            ),
            'manual' => array(
                '#type' => 'checkbox',
                '#title' => __('Enter coordinates manually', 'directories'),
                '#class' => DRTS_BS_PREFIX . 'my-2',
                '#attributes' => array('class' => 'drts-map-field-manual'),
                '#switch' => false,
            ),
            '_latlng' => array(
                '#type' => 'map_latlng',
                '#class' => 'drts-map-field-latlng',
                '#default_value' => is_array(@$data['#default_value']['_latlng']) ? $data['#default_value']['_latlng'] : @$data['#default_value'],
                '#states' => array(
                    'visible' => array('.drts-map-field-manual' => array('type' => 'checked', 'value' => true, 'container' => '.drts-form-type-map-map')),
                ),
                '#states_selector' => '.drts-map-field-latlng',
            ),
            'zoom' => array(
                '#type' => 'hidden',
                '#attributes' => array('class' => 'drts-map-field-zoom'),
                '#render_hidden_inline' => true,
                '#value' => isset($data['#default_value']['zoom']) ? $data['#default_value']['zoom'] : 10,
            ),
        );

        if (!isset(self::$_elements)) {
            self::$_elements = [];  
        }
        self::$_elements[$data['#id']] = $data['#id'];
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
        
        parent::formFieldInit($name, $data, $form);
    }
    
    public function formFieldSubmit(&$value, array &$data, Form\Form $form)
    {
        parent::formFieldSubmit($value, $data, $form);
        
        if (empty($value['_latlng']['lat']) || empty($value['_latlng']['lng'])) {
            if ($form->isFieldRequired($data)) {
                $form->setError(__('Please click on the map or fill out the following fields.', 'directories'), $data['#name'] . '[_latlng]');
                $form->setError(__('Please select a valid location on map.', 'directories'), $data['#name'] . '[map]');
            }
        }
        
        if ($form->hasError()) return;
        
        if (!empty($value['_latlng'])) {
            foreach ($value['_latlng'] as $key => $_value) {
                $value[$key] = $_value;
            }
        } else {
            unset($value['_latlng']); // prevent from saving array as string
        }
    }

    public function preRenderCallback(Form\Form $form)
    {
        $this->_application->Map_Api_load(array('map_field' => true));
        
        $form->settings['#js_ready'][] = sprintf(
            '$("#%s").each(function(index){
    new DRTS.Map.field($(this));
});
$(DRTS).on("clonefield.sabai", (e, data) => {
    if (data.clone.hasClass("drts-form-type-map-map")) {
        data.clone.data("lat", null).data("lng", null);
        new DRTS.Map.field(data.clone);
    }
});',
            implode(', #', self::$_elements)
        );
    }
}
