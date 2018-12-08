<?php
namespace SabaiApps\Directories\Component\Form;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class Form implements \ArrayAccess
{
    public $settings, $values, $storage, $rebuild = false, $redirect, $redirectMessage;
    protected $_application, $_fields, $_errors, $_clickedButton, $_submitSuccess = false, $_defaultFieldType = 'markup', $_originalValues;
    protected static $_defaultFieldSettings = array(
        '#type' => null,
        '#title' => null,
        '#description' => null,
        '#value' => null,
        '#attributes' => [],
        '#data' => [],
        '#weight' => 0,
        '#element_validate' => [],
        '#required' => null,
        '#disabled' => null,
        '#tree' => null,
        '#tree_allow_override' => true,
        '#class' => null,
        '#collapsed' => false,
        '#children' => [],
        '#processed' => false,
    );

    public function __construct(Application $application, array $settings, array $storage = [], array $errors = [])
    {
        $this->_application = $application;
        $this->settings = $settings;
        $this->storage = $storage;
        $this->_errors = $errors;
    }

    public function getApplication()
    {
        return $this->_application;
    }

    public function build(array $values = null)
    {
        $this->settings['#method'] = isset($this->settings['#method']) && 'get' === strtolower($this->settings['#method']) ? 'get' : 'post';
        if (!isset($this->settings['#states']) || !is_array($this->settings['#states'])) {
            $this->settings['#states'] = [];
        }
        $this->settings['#header'] = isset($this->settings['#header']) ? $this->settings['#header'] : [];
        $this->settings['#js'] = isset($this->settings['#js']) ? (array)$this->settings['#js'] : [];
        $this->settings['#js_ready'] = isset($this->settings['#js_ready']) ? (array)$this->settings['#js_ready'] : [];
        $this->settings['#pre_render'] = isset($this->settings['#pre_render']) ? $this->settings['#pre_render'] : [];
        $this->settings['#tabs'] = isset($this->settings['#tabs']) ? $this->settings['#tabs'] : [];
        $this->values = $values;

        if (!isset($this->settings['#token']) || $this->settings['#token'] !== false) {
            // Add form token
            $this->settings[Request::PARAM_TOKEN] = array(
                '#type' => 'token',
                '#token_id' => !empty($this->settings['#token_id'])
                    ? $this->settings['#token_id']
                    : (isset($this->settings['#name']) ? $this->settings['#name']: $this->settings['#id']),
                '#token_reuseable' => !empty($this->settings['#token_reuseable']),
                '#token_reobtainable' => !empty($this->settings['#token_reobtainable']),
                '#token_lifetime' => empty($this->settings['#token_lifetime']) ? 1800 : $this->settings['#token_lifetime'],
            );
        }

        // Add form fields
        $this->_fields = [];
        $this->_initFieldSettings($this->settings, $this->_fields, $this->values);
        foreach (array_keys((array)@$this->_fields['#children']) as $weight) {
            foreach (array_keys($this->_fields['#children'][$weight]) as $ele_key) {
                $this->initField($ele_key, $this->_fields['#children'][$weight][$ele_key]);
            }
        }

        return $this;
    }

    public function getFields($normalize = false, $unwrap = false)
    {
        if (!$normalize) return $this->_fields;

        $ret = [];

        if ($unwrap) {
            if (!isset($this->_fields['#children'][0][$unwrap]['#children'])) return $ret;

            $fields =& $this->_fields['#children'][0][$unwrap]['#children'];
        } else {
            $fields =& $this->_fields['#children'];
        }

        foreach (array_keys($fields) as $weight) {
            foreach (array_keys($fields[$weight]) as $ele_key) {
                $ret[$ele_key] = $fields[$weight][$ele_key];
            }
        }
        return $ret;
    }

    public function getFieldId($name)
    {
        return strtolower($this->settings['#id'] . '-' . strtr($name, array('[' => '-', ']' => '', '_' => '-')));
    }

    public function initField($name, array &$data)
    {
        if (empty($data['#type'])) {
            $data['#type'] = $this->_defaultFieldType;
        }

        $this->_application->Form_Fields_impl($data['#type'])->formFieldInit($name, $data, $this);

        // Has this field already been processed?
        if (!empty($data['#processed'])) return;

        $data['#processed'] = true; // mark as processed

        $data['#name'] = $name;

        if (isset($data['#value'])) {
            $data['#default_value'] = $data['#value'];
        }

        // Required?
        if (!empty($data['#required'])) {
            if (!empty($data['#disabled'])) {
                $data['#required'] = false; // skip required validation, but show as required
                $data['#display_required'] = true;
            }
        }

        if ($data['#type'] !== 'submit' // submit handlers of a submit button are added when the button is actually clicked
            && !empty($data['#submit'])
        ) {
            foreach ($data['#submit'] as $weight => $submits) {
                foreach ($submits as $submit) {
                    $this->settings['#submit'][$weight][] = $submit;
                }
            }
            unset($data['#submit']);
        }

        if (isset($data['#tabs'])) {
            $this->settings['#tabs'] += $data['#tabs'];
        }
    }

    private function _initFieldSettings(array &$settings, array &$fields, $values = null, $parent = null)
    {
        foreach (array_keys($settings) as $key) {
            if (0 === strpos($key, '#')) continue;

            $settings[$key] += self::$_defaultFieldSettings;

            if (is_array($values) && array_key_exists($key, $values)) {
                $settings[$key]['#default_value'] = $values[$key];
                $_values = !isset($settings[$key]['#tree']) || $settings[$key]['#tree'] ? $values[$key] : $values;
            } else {
                $_values = !isset($settings[$key]['#tree']) || $settings[$key]['#tree'] ? null : $values;
            }
            $weight = isset($settings[$key]['#weight']) ? intval($settings[$key]['#weight']) : 0;
            $fields['#children'][$weight][$key] = $settings[$key];
            $this->_initFieldSettings($settings[$key], $fields['#children'][$weight][$key], $_values, $key);

            if (isset($parent)) {
                if (empty($fields['#type'])) $fields['#type'] = 'fieldset';
                unset($fields[$key]); // remove redundant field data
            }
        }

        // Sort fields by the #weight setting
        if (!empty($fields['#children'])) ksort($fields['#children'], SORT_NUMERIC);
    }

    public static function defaultFieldSettings()
    {
        return self::$_defaultFieldSettings;
    }

    public function setError($message, $field = '')
    {
        $this->_errors[is_array($field) ? $field['#name'] : $field] = $message;

        return $this;
    }

    public function hasError($fieldName = null)
    {
        return isset($fieldName) ? isset($this->_errors[$fieldName]) : !empty($this->_errors);
    }

    public function getError($fieldName = null)
    {
        return isset($fieldName) ? $this->_errors[$fieldName] : $this->_errors;
    }

    public function getClickedButton()
    {
        return $this->_clickedButton;
    }

    public function setClickedButton($buttonValue)
    {
        $this->_clickedButton = $buttonValue;

        return $this;
    }

    public function isSubmitSuccess()
    {
        return $this->_submitSuccess;
    }

    public function getValue()
    {
        $args = func_get_args();
        if (is_array($args[0])) {
            $args = $args[0];
        }
        $value = $this->values;
        foreach ($args as $name) {
            if (strpos($name, ']')) {
                if ($names = explode('[', str_replace(']', '', $name))) {
                    foreach ($names as $_name) {
                        if (isset($value[$_name])) {
                            $value = $value[$_name];
                        } else {
                            // non-existant key, return null
                            return;
                        }
                    }
                }
                continue;
            }

            if (!isset($value[$name])) return;

            $value = $value[$name];
        }

        return $value;
    }

    public function getLabel()
    {
        $labels = [];
        $separator = ' &raquo; ';
        $args = func_get_args();
        $settings = $this->settings;
        foreach ($args as $name) {
            if (strpos($name, ']')) {
                if ($names = explode('[', str_replace(']', '', $name))) {
                    foreach ($names as $_name) {
                        if (isset($settings[$_name])) {
                            $settings = $settings[$_name];
                            if (isset($settings['#tab'])
                                && isset($this->settings['#tabs'][$settings['#tab']]['#title'])
                            ) {
                                $labels[] = $this->settings['#tabs'][$settings['#tab']]['#title'];
                            }
                            if (isset($settings['#title'])) {
                                $labels[] = $settings['#title'];
                            }
                        } else {
                            // non-existant key, return last matched label
                            return implode($separator, $labels);
                        }
                    }
                }
                continue;
            }

            if (!isset($settings[$name])) return implode($separator, $labels);

            $settings = $settings[$name];
            if (isset($settings['#title'])) {
                if (isset($settings['#tab'])
                    && isset($this->settings['#tabs'][$settings['#tab']]['#title'])
                ) {
                    $labels[] = $this->settings['#tabs'][$settings['#tab']]['#title'];
                }
                $labels[] = $settings['#title'];
            }
        }

        return implode($separator, $labels);;
    }

    public function isSubmitted()
    {
        if (!isset($this->settings['#submitted'])) {
            if (strcasecmp($_SERVER['REQUEST_METHOD'], $this->settings['#method']) == 0) {
                if ($this->settings['#build_id'] === false || isset($this->values[FormComponent::FORM_BUILD_ID_NAME])) {
                    $this->settings['#submitted'] = true;
                } else {
                    // $values may not be set yet
                    $this->settings['#submitted'] = $this->settings['#method'] === 'get'
                        ? isset($_GET[FormComponent::FORM_BUILD_ID_NAME])
                        : isset($_POST[FormComponent::FORM_BUILD_ID_NAME]);
                }
            } else {
                $this->settings['#submitted'] = false;
            }
        }
        return $this->settings['#submitted'];
    }

    public function submit($values = [], $force = false)
    {
        $this->values = $values;

        // Has form been submitted?
        if (!$force && !$this->isSubmitted()) {
            return false;
        }

        $original_values = $this->values;

        // Process submit
        if ($this->_doSubmit()) {
            $this->_submitSuccess = true;
        } else {
            // Rebuild form to reflect changes made to values during submit
            $this->rebuild = true;
            // Restore original submit values
            $this->values = $original_values;
        }

        if (!empty($this->settings['#enable_storage'])) {
            // Save form storage data so that it can be retrieved in subsequent steps
            $this->_application->getComponent('Form')->setFormStorage($this->settings['#build_id'], $this->storage);
        }

        // Allow form fields to cleanup things
        $this->cleanup();

        return $this->isSubmitSuccess();
    }

    private function _doSubmit()
    {
        $result = $this->_submitFields();

        if (!empty($this->settings['#skip_validate'])) {
            // Skip validation and clear errors if forcing submit
            $this->settings['#validate'] = [];
            $this->_errors = [];
        } elseif (!$result) {
            // We're not forcing submit, so return false if the result so far has not been success
            return false;
        }

        unset(
            $this->values[FormComponent::FORM_BUILD_ID_NAME],
            $this->values[Request::PARAM_TOKEN],
            $this->values[Request::PARAM_AJAX],
            $this->values['noheader'],
            $this->values[$this->_application->getRouteParam()]
        );
        if ($this->_application->getPlatform()->isAdmin()) {
            unset($this->values['page']);
        }

        // Call form level validation callbacks
        if (!empty($this->settings['#validate'])) {
            ksort($this->settings['#validate']);
            foreach ($this->settings['#validate'] as $callback) {
                // Catch errors that might occur and show them as form error
                try {
                    $this->_application->CallUserFuncArray($callback, array($this));
                } catch (Exception\IException $e) {
                    $this->setError($e->getMessage());
                }
            }

            if ($this->hasError()) return false;
        }

        // Call submit callbacks
        if (!empty($this->settings['#submit'])) {
            ksort($this->settings['#submit'], SORT_NUMERIC);
            while (is_array(@$this->settings['#submit']) && ($callbacks = array_shift($this->settings['#submit']))) {
                foreach ($callbacks as $callback) {
                    // Catch errors that might occur and show them as form error
                    try {
                        if (false === $this->_application->CallUserFuncArray($callback, array($this))) {
                            break 2;
                        }
                    } catch (Exception\IException $e) {
                        $this->setError($e->getMessage());
                    }

                    // Abort immediately on any error
                    if ($this->hasError()) return false;
                }
            }
        }

        return !$this->hasError();
    }

    protected function _submitFields()
    {
        // Allow each field to work on its submitted value before being processed by the submit callbacks
        foreach (array_keys((array)@$this->_fields['#children']) as $weight) {
            foreach (array_keys($this->_fields['#children'][$weight]) as $ele_name) {
                $ele_data =& $this->_fields['#children'][$weight][$ele_name];
                if (!empty($ele_data['#disabled'])) {
                    unset($this->values[$ele_name]);
                    continue;
                }
                if (!isset($this->values[$ele_name])) {
                    $this->values[$ele_name] = null;
                }

                // Catch any application level exception that might occur and display it as a form field error.
                try {
                    // Send form submit notification to the field
                    $this->_application->Form_Fields_impl($ele_data['#type'])->formFieldSubmit($this->values[$ele_name], $ele_data, $this);
                } catch (Exception\IException $e) {
                    $this->setError($e->getMessage(), $ele_data);
                } catch (\Exception $e) {
                    // Do not display system error messages to the user
                    $this->_application->logError($e);
                    $this->setError(__('An error occurred while processing the form.', 'directories'), $ele_data);
                }

                // Any error?
                if ($this->hasError($ele_name)) continue;

                if (empty($this->settings['#skip_validate'])) {
                    // Process field level validations if any
                    foreach ($ele_data['#element_validate'] as $callback) {
                        // Catch any application level exception that might occur and display it as a form field error.
                        try {
                            $this->_application->CallUserFuncArray($callback, array($this, &$this->values[$ele_name], $ele_data));
                        } catch (Exception\IException $e) {
                            $this->setError($e->getMessage(), $ele_data);
                        } catch (\Exception $e) {
                            // Do not display system error messages to the user
                            $this->_application->logError($e);
                            $this->setError(__('An error occurred while processing the form.', 'directories'), $ele_data);
                        }
                    }
                }
                // Unset value if null. This may happen when fieldset #tree is false.
                if (is_null($this->values[$ele_name])) unset($this->values[$ele_name]);
            }
        }

        return !$this->hasError();
    }

    public function cleanup()
    {
        foreach (array_keys((array)@$this->_fields['#children']) as $weight) {
            foreach (array_keys($this->_fields['#children'][$weight]) as $ele_name) {
                $ele_data =& $this->_fields['#children'][$weight][$ele_name];
                // Process cleanup.
                try {
                    $this->_application->Form_Fields_impl($ele_data['#type'])->formFieldCleanup($ele_data, $this);
                } catch (Exception\IException $e) {
                    // Catch any exception that might be thrown so that all fields are cleaned up properly.
                    if ($this->isSubmitSuccess()) {
                        $this->_application->logError($e);
                    } else {
                        // Form submit did not success, so append form cleanup error to the list of form error messages
                        $this->setError($e->getMessage());
                    }
                } catch (\Exception $e) {
                    // Do not display system error messages to the user
                    $this->_application->logError($e);
                    if (!$this->isSubmitSuccess()) {
                        $this->setError(__('An error occurred while processing the form.', 'directories'));
                    }
                }
            }
        }

        return $this;
    }

    public function getHeaderHtml()
    {
        // Render headers
        $headers = [];
        // Add form header if any
        if (!empty($this->settings['#header'])) {
            foreach ((array)$this->settings['#header'] as $header) {
                if (is_array($header)) {
                    switch ($header['level']) {
                        case 'danger':
                        case 'warning':
                            $icon = 'fas fa-exclamation-triangle';
                            break;
                        case 'info':
                            $icon = 'fas fa-info-circle';
                            break;
                        case 'success':
                            $icon = 'fas fa-check-circle';
                            break;
                        case 'primary':
                        case 'secondary':
                            $icon = null;
                            break;
                        default:
                            continue;
                    }
                    $headers[] = sprintf(
                        '<div class="%1$salert %1$salert-%2$s">%3$s%4$s</div>',
                        DRTS_BS_PREFIX,
                        $header['level'],
                        empty($icon) ? '' : '<i class="' . $icon . '"></i> ',
                        $this->_application->H($header['message'])
                    );
                } else {
                    $headers[] = $header;
                }
            }
        }
        // Assign error headers if any
        if (!empty($this->_errors)) {
            $errors = [];
            foreach (array_keys($this->_errors) as $name) {
                if ($label = $this->getLabel($name)) {
                    $errors[] = sprintf(
                        '<strong>%s:</strong> <span>%s</span>',
                        $this->_application->H($label),
                        $this->_application->H($this->_errors[$name])
                    );
                }
            }
            $headers[] = sprintf(
                '<div class="%1$salert %1$salert-danger"><div><i class="fas fa-exclamation-triangle"></i> %2$s</div>%3$s</div>',
                DRTS_BS_PREFIX,
                isset($this->_errors['']) ? $this->_errors[''] : $this->_application->H(__('Please correct the error(s) below.', 'directories')),
                empty($errors) ? '' : '<ul class="' . DRTS_BS_PREFIX . 'mt-2"><li>' . implode('</li><li>', $errors) . '</li></ul>'
            );
        }

        return empty($headers) ? '' : '<div class="drts-form-headers">' . implode(PHP_EOL, $headers) . '</div>';
    }

    public function getHtml($name = false, $unwrap = false)
    {
        if (!empty($unwrap)) {
            if (!$name || !isset($this->_fields['#children'][0][$unwrap]['#children'])) return '';

            if (!isset($this->settings['#html'][$unwrap])) {
                $this->settings['#html'][$unwrap] = [];
                $elements =& $this->_fields['#children'][0][$unwrap]['#children'];
                foreach (array_keys($elements) as $weight) {
                    foreach (array_keys($elements[$weight]) as $ele_key) {
                        $this->settings['#html'][$unwrap][$ele_key] = implode(PHP_EOL, $elements[$weight][$ele_key]['#html']);
                    }
                }
            }
            return isset($this->settings['#html'][$unwrap][$name]) ? $this->settings['#html'][$unwrap][$name] : '';
        }

        if (!isset($this->settings['#html'][0])) {
            $this->settings['#html'][0] = [];
            foreach (array_keys($this->_fields['#children']) as $weight) {
                foreach (array_keys($this->_fields['#children'][$weight]) as $ele_key) {
                    if (empty($this->_fields['#children'][$weight][$ele_key]['#html'])) continue;

                    $this->settings['#html'][0][$ele_key] = implode(PHP_EOL, $this->_fields['#children'][$weight][$ele_key]['#html']);
                }
            }
        }

        if (!$name) return implode(PHP_EOL, $this->settings['#html'][0]);

        return is_bool($name) ? $this->settings['#html'][0] : (isset($this->settings['#html'][0][$name]) ? $this->settings['#html'][0][$name] : '');
    }

    public function getHiddenHtml()
    {
        return implode(PHP_EOL, $this->settings['#rendered_hiddens']);
    }

    public function getTabsHtml()
    {
        if (empty($this->settings['#render_tabs']) || empty($this->settings['#tabs'])) return '';

        $tabs = [];
        foreach ($this->settings['#tabs'] as $tab_name => $tab) {
            if (isset($tab['#html'])) {
                $tabs[$tab_name] = $tab['#html'];
            }
        }
        if (empty($tabs)) return '';

        switch ($this->settings['#tab_style']) {
            case 'pill_less_margin':
                $class = DRTS_BS_PREFIX . 'nav-pills ' . DRTS_BS_PREFIX . 'mb-3';
                break;
            case 'pill':
                $class = DRTS_BS_PREFIX . 'nav-pills ' . DRTS_BS_PREFIX . 'mb-4';
                break;
            default:
                $class = DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'mb-4';
        }

        return implode(PHP_EOL, array('<div class="' . DRTS_BS_PREFIX . 'nav ' . $class . '">', implode(PHP_EOL, $tabs), '</div>'));
    }

    public function getJsHtml($wrap = true)
    {
        // Add states
        if (!empty($this->settings['#states'])) {
            $this->settings['#js_ready'][] = sprintf(
                '(function() {
    var states = %s;
    DRTS.states(states, "#%s");
    $(DRTS).on("clonefield.sabai", function(e, data) {
        DRTS.states(states, data.clone.closest("form"));
    });
})();',
                $this->_application->JsonEncode($this->settings['#states'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $this->settings['#id']
            );
        }

        $_js = [];
        foreach ($this->settings['#js'] as $js) {
            $_js[] = str_replace('__FORM_ID__', $this->settings['#id'], $js);
        }
        if (!empty($this->settings['#js_ready'])) {
            $js_callback = [];
            foreach ($this->settings['#js_ready'] as $js) {
                $js_callback[] = str_replace('__FORM_ID__', $this->settings['#id'], $js);
            }
            if (!empty($this->settings['#js_ready_fallback'])) {
                $js_format = 'if (typeof jQuery === "undefined") {
    document.addEventListener("DOMContentLoaded", function() { var $ = jQuery; %1$s });
} else {
    jQuery(function($) { %1$s });
}';
            } else {
                if (Request::isXhr()) {
                    $js_format = 'jQuery(function($) { %s });';
                } else {
                    $js_format = 'document.addEventListener("DOMContentLoaded", function() { var $ = jQuery; %s });';
                }
            }
            $_js[] = sprintf($js_format, implode(PHP_EOL, $js_callback));
        }
        if (empty($_js)) {
            return '';
        }
        $js = implode(PHP_EOL, $_js);
        if ($wrap) {
            $js = '<script type="text/javascript">' . $js . '</script>';
        }
        return $js;
    }

    public function getFormTag($class = null)
    {
        return sprintf(
            '<form id="%s" method="%s" action="%s" class="%s"%s novalidate="novalidate" data-form-name="%s">',
            $this->settings['#id'],
            $this->settings['#method'],
            empty($this->settings['#action']) ? '' : $this->settings['#action'],
            $this->getFormTagClass($class),
            empty($this->settings['#attributes']) ? '' : $this->_application->Attr($this->settings['#attributes']),
            empty($this->settings['#name']) ? '' : $this->_application->H($this->settings['#name'])
        );
    }

    public function getFormTagClass($class = null)
    {
        $classes = array('drts-form');
        if (isset($this->settings['#name'])) {
            $classes[] = 'drts-' . str_replace('_', '-', $this->settings['#name']);
        }
        if (!empty($this->settings['#attributes']['class'])) {
            $classes[] = $this->settings['#attributes']['class'];
            unset($this->settings['#attributes']['class']);
        }
        if (!empty($this->settings['#class'])) {
            $classes[] = $this->settings['#class'];
        }
        if (isset($class)) {
            $classes[] = $class;
        }
        return implode(' ', $classes);
    }

    public function render()
    {
        if (!empty($this->settings['#rendered'])) return $this;

        $this->settings['#rendered'] = true;
        $this->settings['#rendered_hiddens'] = [];
        $this->settings['#render_tabs'] = !isset($this->settings['#render_tabs']) || $this->settings['#render_tabs'];
        $this->settings['#tab_style'] = !isset($this->settings['#tab_style']) || $this->settings['#tab_style'] !== 'tab' ? 'pill' : 'tab';

        // Call pre-render callbacks
        if (!empty($this->settings['#pre_render'])) {
            ksort($this->settings['#pre_render'], SORT_NUMERIC);
            foreach ($this->settings['#pre_render'] as $callback) {
                $this->_application->CallUserFuncArray($callback, array($this));
            }
        }

        // Render tabs
        if (!empty($this->settings['#render_tabs']) && !empty($this->settings['#tabs'])) {
            uasort($this->settings['#tabs'], function ($a, $b) {
                return (int)@$a["#weight"] < (int)@$b["#weight"] ? -1 : 1;
            });
            $active_tab = null;
            foreach ($this->settings['#tabs'] as $tab_name => $tab) {
                if (!empty($tab['#disabled'])) continue;

                if (!empty($tab['#active'])) {
                    $active_tab = $tab_name;
                    break;
                }
            }
            foreach ($this->settings['#tabs'] as $tab_name => $tab) {
                if (!empty($tab['#disabled'])) continue;

                if (!$active_tab) {
                    $this->settings['#tabs'][$tab_name]['#active'] = true;
                    $active_tab = $tab_name;
                }
                $tab_id = empty($tab['#id']) ? 'tab-' . $tab_name : $tab['#id'];
                $html = sprintf(
                    '<a class="%1$snav-item %1$snav-link%2$s" href="#%3$s" data-toggle="%1$s%5$s">%4$s</a>',
                    DRTS_BS_PREFIX,
                    $active_tab === $tab_name ? ' ' . DRTS_BS_PREFIX . 'active' : '',
                    $tab_id,
                    $this->_application->H($tab['#title']),
                    $this->settings['#tab_style']
                );
                if (!empty($tab['#menu'])) {
                    $html .= '<div class="' . DRTS_BS_PREFIX . 'dropdown-menu">';
                    foreach ($tab['#menu'] as $menu_name => $menu_title) {
                        $html .= sprintf(
                            '<a class="%3$sdropdown-item" href="#%1$s">%2$s</a>',
                            DRTS_BS_PREFIX,
                            $tab_id . '-menu-' . $menu_name,
                            $this->_application->H($menu_title)
                        );
                    }
                    if (isset($tab['#link'])) {
                        $link = $tab['#link'];
                        if (!isset($link['#options'])) $link['#options'] = [];
                        $html .= '<div class="' . DRTS_BS_PREFIX . 'dropdown-divider"></div>'
                            . $this->_application->LinkTo($link['#title'], $link['#url'], $link['#options'], ['class' => DRTS_BS_PREFIX . 'dropdown-item']);
                    }
                    $html .='</div>';
                }
                $this->settings['#tabs'][$tab_name]['#html'] = $html;
            }
        }

        // Render fields
        foreach (array_keys($this->_fields['#children']) as $weight) {
            foreach (array_keys($this->_fields['#children'][$weight]) as $ele_key) {
                $data =& $this->_fields['#children'][$weight][$ele_key];
                try {
                    $this->renderField($data);
                } catch (Exception\IException $e) {
                    $this->setError($e->getMessage());
                } catch (\Exception $e) {
                    // Do not display system error messages to the user
                    $this->_application->logError($e);
                    $this->setError(__('An error occurred while processing the form.', 'directories'));
                }
            }
        }

        $platform = $this->_application->getPlatform();
        $platform->addJsFile('form.min.js', 'drts-form', ['drts']);
        if ($platform->isRtl()) {
            $platform->addCssFile('form-rtl.min.css', 'drts-form-rtl', ['drts-form']);
        }

        return $this;
    }

    protected function _hasChildFields($data)
    {
        $child_count = 0;
        foreach (array_keys($data['#children']) as $key) {
            if (false === strpos($key, '#')) {
                ++$child_count;
            }
        }
        return $child_count > 0;
    }

    public function renderField(&$data)
    {
        // Call pre-render callbacks
        if (!empty($data['#pre_render'])) {
            ksort($data['#pre_render'], SORT_NUMERIC);
            foreach ($data['#pre_render'] as $callback) {
                $this->_application->CallUserFuncArray($callback, array($this, &$data));
            }
        }

        // Define classes
        $classes = array('drts-form-field');
        // Add field specific classes
        $classes[] = 'drts-form-type-' . str_replace('_', '-', $data['#type']);
         // Required?
        if ((!empty($data['#required']) && empty($data['#display_unrequired']))
            || !empty($data['#display_required'])
        ) {
            if (!isset($data['#display_required'])) {
                $data['#display_required'] = true;
            }
            $classes[] = 'drts-form-required';
        }
        // Set class
        if (!isset($data['#class'])) {
            $data['#class'] = implode(' ', $classes);
        } else {
            $data['#class'] .= ' ' . implode(' ', $classes);
        }
        // Disabled?
        if (!empty($data['#disabled'])) {
            $data['#attributes']['disabled'] = 'disabled';
        }

        if (isset($data['#id'])) {
            $data['#id'] = str_replace('__FORM_ID__', $this->settings['#id'], $data['#id']);
        }
        if (isset($data['#field_prefix'])) {
            $data['#field_prefix'] = str_replace('__FORM_ID__', $this->settings['#id'], $data['#field_prefix']);
        }
        if (isset($data['#field_suffix'])) {
            $data['#field_suffix'] = str_replace('__FORM_ID__', $this->settings['#id'], $data['#field_suffix']);
        }

        // States
        if (!empty($data['#states'])) {
            if (!isset($data['#states_selector'])) {
                if (!isset($data['#id'])) {
                    $data['#id'] = $this->getFieldId($data['#name']);
                }
                $dependent = '#' . $data['#id'];
            } else {
                $dependent = $data['#states_selector'];
            }

            if (!isset($this->settings['#states'][$dependent])) {
                $this->settings['#states'][$dependent] = [];
            }
            foreach ($data['#states'] as $action => $conditions) {
                if (empty($conditions)) continue;

                if (isset($conditions[0])) {
                    foreach ($conditions as $condition) {
                        $this->settings['#states'][$dependent][$action]['conditions'][] = $condition;
                    }
                } else {
                    // For backward compat
                    foreach ($conditions as $dependee => $condition) {
                        $this->settings['#states'][$dependent][$action]['conditions'][] = ['target' => $dependee] + $condition;
                    }
                }
            }
        }

        // Add custom JS if any
        if (isset($data['#js'])) {
            foreach ((array)$data['#js'] as $js) {
                $this->settings['#js'][] = $js;
            }
        }
        if (isset($data['#js_ready'])) {
            foreach ((array)$data['#js_ready'] as $js) {
                $this->settings['#js_ready'][] = $js;
            }
        }

        // tab?
        if (isset($data['#tab'])
            && isset($this->settings['#tabs'][$data['#tab']])
        ) {
            if (!empty($this->settings['#render_tabs'])) {
                if (empty($this->settings['#tabs'][$data['#tab']]['#disabled'])) {
                    if (!isset($data['#prefix'])) {
                        $data['#prefix'] = '';
                    }
                    if (!isset($data['#suffix'])) {
                        $data['#suffix'] = '';
                    }
                    $tab_id = empty($this->settings['#tabs'][$data['#tab']]['#id']) ? 'tab-' . $data['#tab'] : $this->settings['#tabs'][$data['#tab']]['#id'];
                    if (isset($data['#tab_menu'])
                        && (!empty($this->settings['#tabs'][$data['#tab']]['#menu'][$data['#tab_menu']]))
                    ) {
                        $tab_id .= '-menu-' . $data['#tab_menu'];
                    }
                    $data['#prefix'] = sprintf(
                        '<div class="%1$stab-pane %1$sfade%2$s" id="%3$s">%4$s',
                        DRTS_BS_PREFIX,
                        empty($this->settings['#tabs'][$data['#tab']]['#active']) ? '' : ' ' . DRTS_BS_PREFIX . 'show ' . DRTS_BS_PREFIX . 'active',
                        $tab_id,
                        $data['#prefix']
                    );
                    $data['#suffix'] = $data['#suffix'] . '</div>';
                }
            } else {
                $data['#title'] = $this->settings['#tabs'][$data['#tab']]['#title'];
            }
        }

        if (!isset($data['#id'])) {
            $data['#id'] = '';
        }

        if (!isset($data['#html'])) {
            $data['#html'] = [];

            // Field prefix
            if (isset($data['#prefix'])) {
                $data['#html'][] = $data['#prefix'];
            }

            // Render field
            $this->_application->Form_Fields_impl($data['#type'])->formFieldRender($data, $this);

            // Field suffix
            if (isset($data['#suffix'])) {
                $data['#html'][] = $data['#suffix'];
            }
        }

        return $this;
    }

    public function renderChildFields(&$data)
    {
        if (!$this->_hasChildFields($data)) return;

        $error = $this->hasError($data['#name']) ? $this->getError($data['#name']) : null;
        $output_error = isset($error) && strlen($error) > 0;

        $wrap = !empty($data['#sortable']) || !empty($data['#group']) || $output_error || (isset($data['#title']) && strlen($data['#title']));
        if ($wrap) {
            if ($error) {
                $data['#class'] .= ' drts-form-has-error';
            }
            if (!empty($data['#sortable'])) {
                if (empty($data['#id'])) {
                    $data['#id'] = $this->getFieldId($data['#name']);
                }
                $this->settings['#js_ready'][] = '$("#' . $this->_application->H($data['#id']). '").sortable({axis:"y", containment:"#' . $this->_application->H($data['#id']). '"});';
            }
            $help_block = '';
            if (!$output_error) {
                if (isset($data['#description']) && strlen($data['#description'])) {
                    $help_block = '<div class="' . DRTS_BS_PREFIX . 'form-text drts-form-description ' . DRTS_BS_PREFIX . 'mb-4 ' . DRTS_BS_PREFIX . 'mt-0">'
                        . (empty($data['#description_no_escape']) ? $this->_application->H($data['#description']) : $data['#description']) . '</div>';
                }
            } else {
                $help_block = '<div class="' . DRTS_BS_PREFIX . 'form-text ' . DRTS_BS_PREFIX . 'text-danger drts-form-error ' . DRTS_BS_PREFIX . 'mb-4 ' . DRTS_BS_PREFIX . 'mt-0">' . $this->_application->H($error) . '</div>';
            }
            $make_children_horizontal = false;
            $has_title = isset($data['#title']) && strlen($data['#title']);
            if (!$has_title || empty($data['#horizontal'])) {
                $data['#html'][] = sprintf(
                    '<div class="%sform-group %s" id="%s" style="%s%s"%s%s>',
                    DRTS_BS_PREFIX,
                    $this->_application->H($data['#class']),
                    $this->_application->H($data['#id']),
                    empty($data['#hidden']) ? '' : 'display:none;',
                    isset($data['#attributes']['style']) ? $this->_application->H($data['#attributes']['style']) : '',
                    $this->_application->Attr($data['#attributes'], 'style'),
                    empty($data['#data']) ? '' : $this->_application->Attr($data['#data'], null, 'data-')
                );
                if ($has_title) {
                    $title = empty($data['#title_no_escape']) ? $this->_application->H($data['#title']) : $data['#title'];
                    if (!empty($data['#display_required'])) {
                        $data['#html'][] = '<label>' . $title . '<span class="drts-form-field-required">*</span></label>';
                    } else {
                        $data['#html'][] = '<label>' . $title . '</label>';
                    }
                } else {
                    if (!empty($data['#horizontal'])) {
                        $make_children_horizontal= true;
                    }
                }
                if ($help_block) {
                    $data['#html'][] = $help_block;
                }
            } else {
                $data['#html'][] = sprintf(
                    '<div class="%1$sform-group %1$sform-row %2$s" id="%3$s" style="%4$s%5$s"%6$s%7$s>',
                    DRTS_BS_PREFIX,
                    $this->_application->H($data['#class']),
                    $this->_application->H($data['#id']),
                    empty($data['#hidden']) ? '' : 'display:none;',
                    isset($data['#attributes']['style']) ? $this->_application->H($data['#attributes']['style']) : '',
                    $this->_application->Attr($data['#attributes'], 'style'),
                    empty($data['#data']) ? '' : $this->_application->Attr($data['#data'], null, 'data-')
                );
                $label_class = DRTS_BS_PREFIX . 'col-sm-3';
                if (!isset($data['#horizontal_label_padding']) || $data['#horizontal_label_padding']) {
                    $label_class .= ' ' . DRTS_BS_PREFIX . 'col-form-label';
                }
                if (!empty($data['#display_required'])) {
                    $data['#html'][] = '<div class="' . $label_class . '">'
                        . '<label>' . $data['#title']
                        . '<span class="drts-form-field-required">*</span></label>' . $help_block . '</div>';
                } else {
                    $data['#html'][] = '<div class="' . $label_class . '">'
                        . '<label>' . $data['#title'] . '</label>' . $help_block . '</div>';
                }
                $data['#html'][] = '<div class="' . DRTS_BS_PREFIX . 'col-sm-9 drts-form-field-main">';
            }
        } else {
            if (strlen($data['#id'])
                || !empty($data['#hidden'])
            ) {
                $data['#html'][] = sprintf(
                    '<div class="drts-form-wrap" id="%2$s" style="%3$s%4$s"%5$s%6$s>',
                    DRTS_BS_PREFIX,
                    strlen($data['#id']) ? $this->_application->H($data['#id']) : '',
                    empty($data['#hidden']) ? '' : 'display:none;',
                    isset($data['#attributes']['style']) ? $this->_application->H($data['#attributes']['style']) : '',
                    $this->_application->Attr($data['#attributes'], 'style'),
                    empty($data['#data']) ? '' : $this->_application->Attr($data['#data'], null, 'data-')
                );
            }
            $make_children_horizontal = !empty($data['#horizontal']);
        }

        if (!empty($data['#horizontal_children'])) $make_children_horizontal = true;

        if (!empty($data['#row'])) {
            $gutter = isset($data['#row_gutter']) ? $data['#row_gutter'] : 'sm';
            $data['#html'][] = '<div class="drts-row drts-form-row drts-gutter-' . $gutter . '">';
        }

        // Process child fields
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $ele_key) {
                $ele_data =& $data['#children'][$weight][$ele_key];
                if ($make_children_horizontal) {
                    $ele_data['#horizontal'] = true;
                }

                try {
                    $this->renderField($ele_data);
                    if (!empty($ele_data['#html'])) {
                        if (!empty($data['#row'])) {
                            if (isset($ele_data['#col'])) {
                                if (is_array($ele_data['#col'])) {
                                    $col_class = '';
                                    foreach ($ele_data['#col'] as $size => $col) {
                                        if (!strlen($size) || $size === 'xs') {
                                            $col_class .= ' drts-col-' . $col;
                                        } else {
                                            $col_class .= ' drts-col-' . $size . '-' . $col;
                                        }
                                    }
                                    $col_class = $this->_application->H(trim($col_class));
                                } else {
                                    $col_class = 'drts-col-' . (int)$ele_data['#col'];
                                }
                            } else {
                                $col_class = 'drts-col-12';
                            }
                            $data['#html'][] = '<div class="' . $col_class . '">';
                            $data['#html'][] = implode(PHP_EOL, $ele_data['#html']);
                            $data['#html'][] = '</div>';
                        } else {
                            $data['#html'][] = implode(PHP_EOL, $ele_data['#html']);
                        }
                    }
                } catch (Exception\IException $e) {
                    $this->setError($e->getMessage(), $ele_data['#name']);
                }
            }
        }

        if (!empty($data['#row'])) $data['#html'][] = '</div>';

        if ($wrap) {
            if ($has_title && !empty($data['#horizontal'])) {
                $data['#html'][] = '</div></div>';
            } else {
                $data['#html'][] = '</div>';
            }
        } else {
            if (strlen($data['#id'])) {
                $data['#html'][] = '</div>';
            }
        }

        return $this;
    }

    public function cleanupChildFields(array &$data)
    {
        if (!$this->_hasChildFields($data)) return;

        // Process child fields
        foreach (array_keys($data['#children']) as $weight) {
            if (!is_int($weight)) continue;

            foreach (array_keys($data['#children'][$weight]) as $ele_name) {
                $ele_data =& $data['#children'][$weight][$ele_name];
                try {
                    $this->_application->Form_Fields_impl($ele_data['#type'])->formFieldCleanup($ele_data, $this);
                } catch (\Exception $e) {
                    // Catch any exception that might be thrown so that all elements are cleaned up properly.
                    $this->_application->logError($e);
                }
            }
        }
    }

    public function isFieldRequired(array $data)
    {
        if (empty($data['#required'])) return false;

        if ($data['#required'] === true) return true;
        // Use callback function to determine whether the field is required at run time
        return $this->_application->CallUserFuncArray($data['#required'], array($this));
    }

    public function __toString()
    {
        $html = array($this->getHeaderHtml(), $this->getFormTag());
        if ($tabs_html = $this->getTabsHtml()) {
            $html[] = $tabs_html;
            $html[] = '<div class="' . DRTS_BS_PREFIX . 'tab-content">';
            $html[] = $this->getHtml();
            $html[] = '</div>';
        } else {
            $html[] = $this->getHtml();
        }
        $html[] = $this->getHiddenHtml();
        $html[] = '</form>';
        $html[] = $this->getJsHtml();

        return implode(PHP_EOL, $html);
    }

    public function offsetSet($offset, $value)
    {
        $this->settings[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->settings[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->settings[$offset]);
    }

    public function &offsetGet($offset)
    {
        return $this->settings[$offset];
    }
}
