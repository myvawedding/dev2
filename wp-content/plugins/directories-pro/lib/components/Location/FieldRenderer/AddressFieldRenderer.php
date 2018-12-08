<?php
namespace SabaiApps\Directories\Component\Location\FieldRenderer;

use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\Renderer\AbstractRenderer;

class AddressFieldRenderer extends AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'custom_format' => false,
                'format' => '{street}, {city}, {province} {zip}, {country}',
                'link' => false,
                '_separator' => '<br />',
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        $form = [];
        if (!$field->isCustomField()) {
            $form += [
                'custom_format' => [
                    '#type' => 'checkbox',
                    '#title' => __('Customize address format', 'directories-pro'),
                    '#default_value' => !empty($settings['custom_format']),
                ],
                'format' => [
                    '#type' => 'textfield',
                    '#title' => __('Address format', 'directories-pro'),
                    '#description' => sprintf(
                        __('Available tags: %s', 'directories-pro'),
                        implode(' ', $this->_application->Location_FormatAddress_tags($field->Bundle))
                    ),
                    '#default_value' => $settings['format'],
                    '#states' => [
                        'visible' => [
                            sprintf('input[name="%s[custom_format]"]', $this->_application->Form_FieldName($parents)) => [
                                'type' => 'checked',
                                'value' => true
                            ],
                        ],
                    ],
                    '#required' => function ($form) use ($parents) {
                        $val = $form->getValue(array_merge($parents, ['custom_format']));
                        return !empty($val);
                    },
                ],
            ];
        }
        $form['link'] = [
            '#type' => 'checkbox',
            '#title' => __('Link to Google Maps', 'directories-pro'),
            '#default_value' => !empty($settings['link']),
        ];

        return $form;
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, IEntity $entity, array $values, $more = 0)
    {
        $is_mile = $this->_application->getComponent('Map')->getConfig('map', 'distance_unit') === 'mi';
        $ret = [];
        foreach ($values as $key => $value) {
            if (!empty($settings['custom_format'])) {
                if (!isset($location_hierarchy)) {
                    $location_bundle = $this->_getLocationBundle($field);
                    $location_hierarchy = $this->_application->Location_Hierarchy($location_bundle ? $location_bundle : null);
                }
                $formatted = $this->_application->Location_FormatAddress($value, $settings['format'], $entity, $location_hierarchy);
            } else {
                $formatted = $this->_application->H($value['address']);
            }
            if (!strlen($formatted)) continue;

            if ($settings['link']) {
                $formatted = sprintf('<a href="http://maps.apple.com/?q=%s,%s">%s</a>', $value['lat'], $value['lng'], $formatted);
            }
            $ret[] = sprintf(
                '<span class="drts-location-address drts-map-marker-trigger drts-map-marker-trigger-%1$d" data-key="%1$d">%2$s%3$s</span>',
                $key,
                $formatted,
                isset($value['distance'])
                    ? ' <span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-dark ' . DRTS_BS_PREFIX . 'mx-1 drts-location-distance">' . sprintf($is_mile ? __('%s mi', 'directories-pro') : __('%s km', 'directories-pro'), round($value['distance'], 2)) . '</span>'
                    : ''
            );
        }
        return implode($settings['_separator'], $ret);
    }
    
    protected function _getLocationBundle(IField $field)
    {
        return $this->_application->Entity_Bundle('location_location', $field->Bundle->component, $field->Bundle->group);
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        if (!empty($field_settings['custom_format'])
            && strlen($field_settings['format'])
        ) {
            $ret['format'] = [
                'label' => __('Address format', 'directories-pro'),
                'value' => $field_settings['format'],
            ];
        }
        $ret['link'] = [
            'label' => __('Link to Google Maps', 'directories-pro'),
            'value' => !empty($settings['link']),
            'is_bool' => true,
        ];
        return $ret;
    }
}
