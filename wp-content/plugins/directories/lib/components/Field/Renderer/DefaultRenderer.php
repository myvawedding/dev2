<?php
namespace SabaiApps\Directories\Component\Field\Renderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\IField;

class DefaultRenderer extends AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        $info = array(
            'field_types' => array($this->_name),
            'default_settings' => [],
            'inlineable' => true,
        );
        switch ($this->_name) {
            case 'boolean':
                $info['default_settings'] = array(
                    'on_label' => __('Yes', 'directories'),
                    'off_label' => __('No', 'directories'),
                );
                break;
            case 'email':
                $info['default_settings'] = array(
                    'type' => 'default',
                    'label' => null,
                    'target' => '_self',
                    '_separator' => ', ',
                );
                break;
            case 'user':
                $info['default_settings'] = array(
                    'format' => 'thumb_s_l',
                    '_separator' => ' ',
                );
                break;
            case 'number':
                $info['default_settings'] = array(
                    'dec_point' => '.',
                    'thousands_sep' => ',',
                    'trim_zeros' => false,
                    '_separator' => ' ',
                );
                break;
            case 'range':
                $info['default_settings'] = array(
                    'dec_point' => '.',
                    'thousands_sep' => ',',
                    'range_sep' => ' - ',
                    '_separator' => ' ',
                );
                break;
            case 'url':
                $info['default_settings'] = array(
                    'type' => 'default',
                    'label' => null,
                    'target' => '_blank',
                    'rel' => array('nofollow', 'external'),
                    '_separator' => ', ',
                );
                break;
            case 'phone':
                $info['default_settings'] = array(
                    'type' => 'default',
                    'label' => null,
                    '_separator' => ', ',
                );
                break;
            case 'time':
                $info['default_settings'] = array(
                    'daytime_sep' => ' ',
                    'time_sep' => ' - ',
                    '_separator' => ', ',
                );
                break;
            default:
                $info['default_settings'] = array(
                    '_separator' => ', ',
                );
                break;
        }
        return $info;
    }

    protected function _fieldRendererSettingsForm(IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'boolean':
                return array(
                    'on_label' => array(
                        '#type' => 'textfield',
                        '#size' => 10,
                        '#title' => __('ON label', 'directories'),
                        '#default_value' => $settings['on_label'],
                    ),
                    'off_label' => array(
                        '#type' => 'textfield',
                        '#size' => 10,
                        '#title' => __('OFF label', 'directories'),
                        '#default_value' => $settings['off_label'],
                    ),
                );
            case 'email':
                return array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getEmailDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#title' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'target' => array(
                        '#title' => __('Open link in', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getLinkTargetOptions(),
                        '#default_value' => $settings['target'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                );
            case 'url':
                return array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getUrlDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#title' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'max_len' => array(
                        '#title' => __('Max URL display length', 'directories'),
                        '#type' => 'slider',
                        '#min_text' => __('Unlimited', 'directories'),
                        '#default_value' => $settings['max_len'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'label'),
                            ),
                        ),
                    ),
                    'target' => array(
                        '#title' => __('Open link in', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getLinkTargetOptions(),
                        '#default_value' => $settings['target'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                    'rel' => array(
                        '#title' => __('Link "rel" attribute', 'directories'),
                        '#inline' => true,
                        '#type' => 'checkboxes',
                        '#options' => $this->_getLinkRelAttrOptions(),
                        '#default_value' => $settings['rel'],
                        '#states' => array(
                            'invisible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'nolink'),
                            ),
                        ),
                    ),
                );
            case 'user':
                return array(
                    'format' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_application->UserIdentityHtml(),
                        '#default_value' => $settings['format'],
                    ),
                );
            case 'number':
                return array(
                    'dec_point' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Decimal point', 'directories'),
                        '#default_value' => $settings['dec_point'],
                    ),
                    'thousands_sep' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Thousands separator', 'directories'),
                        '#default_value' => $settings['thousands_sep'],
                    ),
                    'trim_zeros' => [
                        '#type' => 'checkbox',
                        '#title' => __('Remove trailing zeros', 'directories'),
                        '#default_value' => $settings['trim_zeros'],
                    ],
                );
            case 'phone':
                return array(
                    'type' => array(
                        '#title' => __('Display format', 'directories'),
                        '#type' => 'select',
                        '#options' => $this->_getPhoneDisplayFormatOptions(),
                        '#default_value' => $settings['type'],
                    ),
                    'label' => array(
                        '#title' => __('Custom label', 'directories'),
                        '#type' => 'textfield',
                        '#default_value' => $settings['label'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'label'),
                            ),
                        ),
                    ),
                );
            case 'range':
                return array(
                    'dec_point' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Decimal point', 'directories'),
                        '#default_value' => $settings['dec_point'],
                    ),
                    'thousands_sep' => array(
                        '#type' => 'textfield',
                        '#size' => 3,
                        '#title' => __('Thousands separator', 'directories'),
                        '#default_value' => $settings['thousands_sep'],
                    ),
                    'range_sep' => array(
                        '#type' => 'textfield',
                        '#title' => __('Range separator', 'directories'),
                        '#default_value' => $settings['range_sep'],
                        '#no_trim' => true,
                        '#size' => 10,
                    ),
                );
            default:
                return [];
        }
    }

    protected function _fieldRendererRenderField(IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        $type = $field->getFieldType();
        return $this->$type($field, $settings, $values, $entity);
    }

    protected function string(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        if ($field_settings['char_validation'] === 'email') {
            $values = array_map('antispambot', $values);
        }
        foreach ($values as $value) {
            $ret[] = @$field_settings['prefix'] . $this->_application->H($value) . @$field_settings['suffix'];
        }
        return implode($settings['_separator'], $ret);
    }

    protected function email(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        if ($settings['type'] === 'nolink') {
            foreach ($values as $value) {
                $ret[] = antispambot($value);
            }
        } else {
            $attr = '';
            if ($settings['target'] === '_blank') {
                $attr .= ' target="_blank" rel="noopener"';
            }
            foreach ($values as $value) {
                $email = antispambot($value);
                $ret[] = sprintf(
                    '<a href="mailto:%s"%s>%s</a>',
                    $email,
                    $attr,
                    $settings['type'] === 'label' ? $this->_application->H($settings['label']) : $email
                );
            }
        }

        return implode($settings['_separator'], $ret);
    }

    protected function url(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        if ($settings['type'] === 'nolink') {
            if (empty($settings['max_len'])) {
                foreach ($values as $value) {
                    $ret[] = $this->_application->H($value);
                }
            } else {
                foreach ($values as $value) {
                    $ret[] = $this->_application->H($this->_application->System_MB_strimwidth($value, 0, $settings['max_len'], '...'));
                }
            }
        } else {
            $attr = '';
            if ($settings['target'] === '_blank') {
                $attr .= ' target="_blank"';
                $settings['rel'][] = 'noopener';
            }
            $rel = implode(' ', $settings['rel']);
            foreach ($values as $value) {
                $ret[] = sprintf(
                    '<a href="%s"%s rel="%s">%s</a>',
                    $this->_application->H($this->_application->Filter('field_render_url', $value, [$field, $value, $entity])),
                    $attr,
                    $rel,
                    $settings['type'] === 'label'
                        ? $this->_application->H($settings['label'])
                        : (empty($settings['max_len']) ? $this->_application->H($value) : $this->_application->H($this->_application->System_MB_strimwidth($value, 0, $settings['max_len'], '...')))
                );
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function phone(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        if ($settings['type'] === 'nolink') {
            foreach (array_keys($values) as $key) {
                $values[$key] = $this->_application->H($values[$key]);
            }
        } else {
            foreach (array_keys($values) as $key) {
                $values[$key] = sprintf(
                    '<a data-phone-number="%1$s" href="tel:%1$s">%2$s</a>',
                    preg_replace('/[^0-9\+]/','', $values[$key]),
                    $settings['type'] === 'label'
                        ? $this->_application->H($settings['label'])
                        : $this->_application->H($values[$key])
                );
            }
        }
        return implode($settings['_separator'], $values);
    }

    protected function number(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        $dec_point = isset($settings['dec_point']) ? $settings['dec_point'] : '.';
        $thousands_sep = isset($settings['thousands_sep']) ? $settings['thousands_sep'] : ',';
        $trim_zeros = $field_settings['decimals'] > 0 && !empty($settings['trim_zeros']);
        foreach ($values as $value) {
            $value = number_format($value, $field_settings['decimals'], $dec_point, $thousands_sep);
            if ($trim_zeros) {
                $value = rtrim(rtrim($value, 0), '.');
            }
            $ret[] = @$field_settings['prefix'] . $value . @$field_settings['suffix'];
        }
        return implode($settings['_separator'], $ret);
    }

    protected function range(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        if ($field_settings['min'] === null) $field_settings['min'] = 0;
        if ($field_settings['max'] === null) $field_settings['max'] = 100;
        $dec_point = isset($settings['dec_point']) ? $settings['dec_point'] : '.';
        $thousands_sep = isset($settings['thousands_sep']) ? $settings['thousands_sep'] : ',';
        foreach ($values as $value) {
            if ($value['min'] == $field_settings['min']
                && $value['max'] == $field_settings['max']
            ) continue;

            $ret[] = @$field_settings['prefix'] . number_format($value['min'], $field_settings['decimals'], $dec_point, $thousands_sep) . '<span class="drts-field-range-separator">' . $settings['range_sep'] . '</span>'
                . number_format($value['max'], $field_settings['decimals'], $dec_point, $thousands_sep). @$field_settings['suffix'];
        }
        return empty($ret) ? '' : implode($settings['_separator'], $ret);
    }

    protected function choice(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        $options = $this->_application->Field_ChoiceOptions($field);
        foreach ($values as $value) {
            if (isset($options['options'][$value])) {
                $ret[] = $this->_application->H($options['options'][$value]);
            }
        }
        return implode($settings['_separator'], $ret);
    }

    protected function boolean(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        return empty($values[0]) ? $settings['off_label'] : $settings['on_label'];
    }

    protected function user(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->_application->UserIdentityHtml($value, $settings['format']);
        }
        return implode($settings['_separator'], $ret);
    }

    public function date(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        $field_settings = $field->getFieldSettings();
        foreach ($values as $value) {
            $ret[] = !empty($field_settings['enable_time'])
                ? $this->_application->System_Date_datetime($value, true)
                : $this->_application->System_Date($value, true);
        }
        return implode($settings['_separator'], $ret);
    }

    public function time(IField $field, array $settings, array $values, Entity\Type\IEntity $entity)
    {
        $ret = [];
        foreach ($values as $value) {
            $str = '';
            if (!empty($value['day'])) {
                $str .= $this->_application->H($this->_application->Days($value['day'])) . $settings['daytime_sep'];
            }
            $str .= $this->_application->System_Date_time($value['start']);
            if (!empty($value['end'])) {
                $str .= $settings['time_sep'] . $this->_application->System_Date_time($value['end']);
            }
            $ret[] = $str;
        }
        return implode($settings['_separator'], $ret);
    }

    protected function _getEmailDisplayFormatOptions()
    {
        return [
            'default' => __('E-mail Address', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('E-mail Address', 'directories')),
            'label' => __('Custom label', 'directories'),
        ];
    }

    protected function _getUrlDisplayFormatOptions()
    {
        return [
            'default' => __('URL', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('URL', 'directories')),
            'label' => __('Custom label', 'directories')
        ];
    }

    protected function _getPhoneDisplayFormatOptions()
    {
        return [
            'default' => __('Phone Number', 'directories'),
            'nolink' => sprintf(__('%s (without link)', 'directories'), __('Phone Number', 'directories')),
            'label' => __('Custom label', 'directories')
        ];
    }

    protected function _fieldRendererReadableSettings(IField $field, array $settings)
    {
        switch ($this->_name) {
            case 'boolean':
                return [
                    'on_label' => [
                        'label' => __('ON label', 'directories'),
                        'value' => $settings['on_label'],
                    ],
                    'off_label' => [
                        'label' => __('OFF label', 'directories'),
                        'value' => $settings['off_label'],
                    ],
                ];
            case 'email':
                $formats = $this->_getEmailDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['label'] = [
                        'label' => __('Custom label', 'directories'),
                        'value' => $settings['label'],
                    ];
                }
                if ($settings['type'] !== 'nolink') {
                    $targets = $this->_getLinkTargetOptions();
                    $ret['target'] = [
                        'label' => __('Open link in', 'directories'),
                        'value' => $targets[$settings['target']],
                    ];
                    $rels = $this->_getLinkRelAttrOptions();
                    $ret['rel'] = [
                        'label' => __('Open link in', 'directories'),
                        'value' => $targets[$settings['target']],
                    ];
                }
                return $ret;
            case 'url':
                $formats = $this->_getUrlDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['label'] = [
                        'label' => __('Custom label', 'directories'),
                        'value' => $settings['label'],
                    ];
                } else {
                    $ret['max_len'] = [
                        'label' => __('Max URL display length', 'directories'),
                        'value' => empty($settings['max_len']) ? __('Unlimited', 'directories') : $settings['max_len'],
                    ];
                }
                if ($settings['type'] !== 'nolink') {
                    $targets = $this->_getLinkTargetOptions();
                    $ret['target'] = [
                        'label' => __('Open link in', 'directories'),
                        'value' => $targets[$settings['target']],
                    ];
                    if (!empty($settings['rel'])) {
                        $rels = $this->_getLinkRelAttrOptions();
                        $value = [];
                        foreach ($settings['rel'] as $rel) {
                            $value[] = $rels[$rel];
                        }
                        $ret['rel'] = [
                            'label' => __('Link "rel" attribute', 'directories'),
                            'value' => implode(', ', $value),
                        ];
                    }
                }
                return $ret;
            case 'phone':
                $formats = $this->_getPhoneDisplayFormatOptions();
                $ret =[
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['type']],
                    ],
                ];
                if ($settings['type'] === 'label') {
                    $ret['label'] = [
                        'label' => __('Custom label', 'directories'),
                        'value' => $settings['label'],
                    ];
                }
                return $ret;
            case 'user':
                $formats = $this->_application->UserIdentityHtml();
                return [
                    'type' => [
                        'label' => __('Display format', 'directories'),
                        'value' => $formats[$settings['format']],
                    ],
                ];
            case 'number':
                return [
                    'dec_point' => [
                        'label' => __('Decimal point', 'directories'),
                        'value' => $settings['dec_point'],
                    ],
                    'thousands_sep' => [
                        'label' => __('Thousands separator', 'directories'),
                        'value' => $settings['thousands_sep'],
                    ],
                ];
            case 'range':
                return [
                    'dec_point' => [
                        'label' => __('Decimal point', 'directories'),
                        'value' => $settings['dec_point'],
                    ],
                    'thousands_sep' => [
                        'label' => __('Thousands separator', 'directories'),
                        'value' => $settings['thousands_sep'],
                    ],
                    'range_sep' => [
                        'label' => __('Range separator', 'directories'),
                        'value' => $settings['range_sep'],
                    ],
                ];
        }
    }
}
