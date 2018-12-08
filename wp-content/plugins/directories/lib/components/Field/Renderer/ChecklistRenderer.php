<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class ChecklistRenderer extends AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'label' => __('Checklist', 'directories'),
            'field_types' => array('choice'),
            'default_settings' => array(
                'checked_color' => '',
                'show_unchecked' => true,
                'unchecked_color' => '',
                'tooltip' => false,
                'inline' => false,
            ),
            'separatable' => false,
            'emptiable' => true,
        );
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        return array(
            'checked_color' => array(
                '#type' => 'select',
                '#title' => __('Checked item color', 'directories'),
                '#default_value' => $settings['checked_color'],
                '#options' => ['' => __('Default', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
            ),
            'checked_color_custom' => [
                '#type' => 'colorpicker',
                '#default_value' => $settings['checked_color_custom'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[checked_color]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'custom'),
                    ),
                ),
            ],
            'show_unchecked' => array(
                '#type' => 'checkbox',
                '#title' => __('Show unchecked items', 'directories'),
                '#default_value' => !empty($settings['show_unchecked']),
                '#horizontal' => true,
            ),
            'unchecked_color' => array(
                '#type' => 'select',
                '#title' => __('Unchecked item color', 'directories'),
                '#default_value' => $settings['unchecked_color'],
                '#options' => ['' => __('Default', 'directories'), 'custom' => __('Custom', 'directories')],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[show_unchecked]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#columns' => 4,
            ),
            'unchecked_color_custom' => [
                '#type' => 'colorpicker',
                '#default_value' => $settings['unchecked_color_custom'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[show_unchecked]"]', $this->_application->Form_FieldName($parents)) => array('type' => 'checked', 'value' => true),
                        sprintf('select[name="%s[unchecked_color]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'custom'),
                    ),
                ),
            ],
            'tooltip' => array(
                '#type' => 'checkbox',
                '#title' => __('Show item label in tooltip', 'directories'),
                '#default_value' => !empty($settings['tooltip']),
                '#horizontal' => true,
            ),
            'inline' => array(
                '#type' => 'checkbox',
                '#title' => __('Display inline', 'directories'),
                '#default_value' => !empty($settings['inline']),
                '#horizontal' => true,
            ),
        );
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $ret = [];
        $checked_icon = 'fas fa-check';
        $tooltip = !empty($settings['tooltip']);
        $inline = !empty($settings['inline']);
        $options = $this->_application->Field_ChoiceOptions($field);
        if ($settings['show_unchecked']) {
            $unchecked_icon = 'fas fa-times';
            foreach ($options['options'] as $option => $option_label) {
                $icon = isset($options['icons'][$option]) ? $options['icons'][$option] : null;
                if (in_array($option, $values)) {
                    $ret[] = $this->_renderColumn($icon ? $icon : $checked_icon, true, $settings['checked_color'], $settings['checked_color_custom'], $option_label, $tooltip, $inline);
                } else {
                    $ret[] = $this->_renderColumn($icon ? $icon : $unchecked_icon, false, $settings['unchecked_color'], $settings['unchecked_color_custom'], $option_label, $tooltip, $inline);
                }
            }
        } else {
            foreach ($values as $value) {
                if (!isset($options['options'][$value])) continue;

                $icon = isset($options['icons'][$value]) ? $options['icons'][$value] : $checked_icon;

                $ret[] = $this->_renderColumn($icon, true, $settings['checked_color'], $settings['checked_color_custom'], $options['options'][$value], $tooltip, $inline);
            }
        }
        if (empty($ret)) return '';

        $ret = implode(PHP_EOL, $ret);
        return $inline ? $ret : '<div class="drts-row">' . $ret . '</div>';
    }
    
    protected function _renderIcon($icon, $checked, $colorType, $color)
    {
        $class = $style = '';
        if ($colorType === '') {
            $class = DRTS_BS_PREFIX . 'text-' . ($checked ? 'success' : 'danger');
        } else {
            if (!empty($color)) {
                $style = 'color:' . $this->_application->H($color);
            }
        }
        return sprintf(
            '<span class="fa-stack" style="%3$s"><i class="far fa-circle fa-stack-2x %2$s"></i><i class="fa-stack-1x %1$s %2$s"></i></span>',
            $icon,
            $class,
            $style
        );
    }
    
    protected function _renderColumn($icon, $checked, $colorType, $color, $label, $tooltip, $inline)
    {
        $icon = $this->_renderIcon($icon, $checked, $colorType, $color);
        $label = $this->_application->H($label);
        if ($tooltip) {
            $label = '<span rel="sabaitooltip" title="' . $label . '">' . $icon . '</span>';
        } else {
            $label = $icon . ' ' . $label;
        }
        
        return $inline ? $label : '<div class="drts-col-6 drts-col-md-4 drts-col-xl-3 ' . DRTS_BS_PREFIX . 'mb-1">' . $label . '</div>';
    }
    
    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        $ret = [
            'checked_color' => [
                'label' => __('Checked item color', 'directories'),
                'value' => empty($settings['checked_color']) || $settings['checked_color'] === 'default'
                    ? __('Default', 'directories')
                    : $settings['checked_color_custom'],
            ],
            'show_unchecked' => [
                'label' => __('Show unchecked items', 'directories'),
                'value' => !empty($settings['show_unchecked']),
                'is_bool' => true,
            ],
        ];
        if (!empty($settings['show_unchecked'])) {
            $ret['unchecked_color'] = [
                'label' => __('Unchecked item color', 'directories'),
                'value' => empty($settings['unchecked_color']) || $settings['unchecked_color'] === 'default'
                    ? __('Default', 'directories')
                    : $settings['unchecked_color_custom'],
            ];
        }
        $ret += [
            'tooltip' => [
                'label' => __('Show item label in tooltip', 'directories'),
                'value' => !empty($settings['tooltip']),
                'is_bool' => true,
            ],
            'inline' => [
                'label' => __('Display inline', 'directories'),
                'value' => !empty($settings['inline']),
                'is_bool' => true,
            ],
        ];
        
        return $ret;
    }
}