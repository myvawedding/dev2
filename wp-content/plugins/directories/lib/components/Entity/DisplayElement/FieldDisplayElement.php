<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;

class FieldDisplayElement extends Display\Element\AbstractElement
{
    protected $_field = [];
    
    protected function _getField($bundle)
    {
        if (!$field = $this->_doGetField($bundle)) {
            throw new Exception\RuntimeException(sprintf('Invalid field for element %s, bundle %s', $this->_name, $bundle->name));
        }
        return $field;
    }
    
    protected function _doGetField($bundle)
    {
        $field_name = substr($this->_name, 13); // remove entity_field_ part
        return $this->_application->Entity_Field($bundle, $field_name);
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity';
    }
        
    protected function _displayElementSupportsAmp(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        foreach (array_keys($this->_getRenderers($bundle)) as $renderer) {
            if ($this->_application->Field_Renderers_impl($renderer)->fieldRendererSupportsAmp($bundle)) {
                return true;
            }
        }
        return false;
    }
    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        $field = $this->_getField($bundle);
        $label = $field->getFieldLabel();
        if (!strlen($label)) {
            $label = $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('label');
        }
        return array(
            'type' => 'field',
            'label' => $label,
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'default_settings' => array(
                'label' => 'none',
                'label_custom' => null,
                'label_icon' => null,
                'label_icon_size' => null,
                'label_as_heading' => false,
                'renderer' => null,
                'renderer_settings' => [],
            ),
            'alignable' => true,
            'positionable' => true,
            'pre_render' => true,
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
            'cacheable' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('cacheable'),
        );
    }
    
    protected function _getRenderers(Entity\Model\Bundle $bundle)
    {
        $field_types = $this->_application->Field_Types();
        $field = $this->_getField($bundle);
        $renderers = (array)@$field_types[$field->getFieldType()]['renderers'];
        foreach (array_keys($renderers) as $renderer) {
            if ((!$field_renderer = $this->_application->Field_Renderers_impl($renderer, true))
                || !$field_renderer->fieldRendererSupports($field)
            ) {
                unset($renderers[$renderer]);
                continue;
            }
        }
        return $renderers;
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        if (!$renderers = $this->_getRenderers($bundle)) return;
        
        $field = $this->_getField($bundle);

        $form = $this->_application->Display_ElementLabelSettingsForm($settings, $parents) + array(
            'label_as_heading' => array(
                '#title' => __('Show label as heading', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['label_as_heading']),
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[label]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'none'),
                    ),
                ),
                '#weight' => -2,
            ),
            'renderer_settings' => array(
                '#tree' => true,
            ),
        );
        if (count($renderers) === 1) {
            $form['renderer'] = array(
                '#type' => 'hidden',
                '#value' => current(array_keys($renderers)),
            );
        } else {
            if (isset($settings['renderer'])) {
                $renderer = $settings['renderer'];
            } else {
                $field_types = $this->_application->Field_Types();
                $field_type = $field->getFieldType();
                $renderer = isset($field_types[$field_type]['default_renderer']) ? $field_types[$field_type]['default_renderer'] : null;
            }
            $form['renderer'] = array(
                '#type' => 'select',
                '#title' => __('Field renderer', 'directories'),
                '#description' => __('A field renderer determines how the value of a field will be displayed.', 'directories'),
                '#options' => $renderers,
                '#weight' => -1,
                '#default_value' => $renderer,
                '#horizontal' => true,
                '#option_no_escape' => true,
            );
        }
        foreach (array_keys($renderers) as $renderer) {
            $field_renderer = $this->_application->Field_Renderers_impl($renderer);
            $renderer_settings = (array)@$settings['renderer_settings'][$renderer] + (array)$field_renderer->fieldRendererInfo('default_settings');
            $renderer_settings_parents = $parents;
            $renderer_settings_parents[] = 'renderer_settings';
            $renderer_settings_parents[] = $renderer;
            if ($display->isAmp()) {
                $renderer_settings_form = $field_renderer->fieldRendererAmpSettingsForm($field, $renderer_settings, $renderer_settings_parents);
            } else {
                $renderer_settings_form = $field_renderer->fieldRendererSettingsForm($field, $renderer_settings, $renderer_settings_parents);
            }
            if ($renderer_settings_form) {          
                $form['renderer_settings'][$renderer] = $renderer_settings_form;
                foreach (array_keys($form['renderer_settings'][$renderer]) as $key) {
                    if (false === strpos($key, '#')) {
                        $form['renderer_settings'][$renderer][$key]['#horizontal'] = true;
                    }
                }
                $form['renderer_settings'][$renderer]['#states']['visible'] = array(
                    sprintf('[name="%s[renderer]"]', $this->_application->Form_FieldName($parents)) => array('value' => $renderer),
                );
            } else {
                $form['renderer_settings'][$renderer] = [];
            }
            if ($field_renderer instanceof \SabaiApps\Directories\Component\Field\Renderer\ImageRenderer) {
                $form['renderer_settings'][$renderer]['_render_background'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Render as background image', 'directories'),
                    '#default_value' => !empty($renderer_settings['_render_background']),
                    '#horizontal' => true,
                    '#weight' => 250,
                );
                $form['renderer_settings'][$renderer]['_hover_zoom'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Zoom on hover', 'directories'),
                    '#default_value' => !empty($renderer_settings['_hover_zoom']),
                    '#horizontal' => true,
                    '#weight' => 251,
                );
                $form['renderer_settings'][$renderer]['_hover_brighten'] = [
                    '#type' => 'checkbox',
                    '#title' => __('Brighten on hover', 'directories'),
                    '#default_value' => !empty($renderer_settings['_hover_brighten']),
                    '#horizontal' => true,
                    '#weight' => 252,
                ];
            }
            if ($emptiable = $field_renderer->fieldRendererInfo('emptiable')) {
                $form['renderer_settings'][$renderer]['_render_empty'] = array(
                    '#type' => 'hidden',
                    '#value' => true,
                    //'#title' => is_string($emptiable) ? $emptiable : __('Render empty value', 'directories'),
                    //'#default_value' => !empty($renderer_settings['_render_empty']),
                    //'#horizontal' => true,
                    //'#weight' => 300,
                );
            } elseif ($field_renderer->fieldRendererInfo('no_imageable')) {
                $form['renderer_settings'][$renderer]['_render_empty'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Display "No Image" image if nothing to display', 'directories'),
                    '#default_value' => !empty($renderer_settings['_render_empty']),
                    '#horizontal' => true,
                    '#weight' => 300,
                );
            }
        }
        
        return $form;
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $field = $this->_getField($bundle);
        if ($field->getFieldData('disabled')) return '';
        
        // Render field
        $settings = $element['settings'];
        $renderer_settings = isset($settings['renderer_settings'][$settings['renderer']]) ? $settings['renderer_settings'][$settings['renderer']] : [];
        $values = $var->getFieldValue($field->getFieldName());
        if (empty($values)) {
            if (empty($renderer_settings['_render_empty'])) return '';
            
            $values = [];
        }
        $html = $this->_application->callHelper(
            'Entity_Field_renderBySettingsReference',
            [$var, $field->getFieldName(), $settings['renderer'], &$renderer_settings, $values]
        );

        // Nothing to show
        if ($html === '') return '';
        
        if (!is_array($html)) {
            $html = ['html' => $html, 'class' => ''];
        } else {
            if (!isset($html['class'])) $html['class'] = '';
        }

        // Style content according to display element settings
        if (!empty($renderer_settings['_hover_zoom'])
            || !empty($renderer_settings['_hover_brighten'])
        ) {
            $html['class'] .= ' drts-display-element-hover-effect';
            if (!empty($renderer_settings['_hover_zoom'])) {
                $html['class'] .= ' drts-display-element-hover-zoom';
            }
            if (!empty($renderer_settings['_hover_brighten'])) {
                $html['class'] .= ' drts-display-element-hover-brighten';
            }
        }
        if (!empty($renderer_settings['_render_background'])) {
            if (empty($html['html'])) {
                $backgorund_image_url = $this->_application->NoImage(true);
                // cancel hover effect
                $html['class'] = 'drts-display-element-with-background drts-display-element-with-background-no-image';
            } else {
                $html['class'] .= ' drts-display-element-with-background';
                $backgorund_image_url = $html['html'];
            }
            $html['html'] = ' ';
            $html['style'] = 'background-image:url(' . $this->_application->H($backgorund_image_url) . ');';
            if (!empty($renderer_settings['height'])) {
                $html['style'] .= 'min-height:' . intval($renderer_settings['height']) . 'px;';
            }
        }
        
        // Link image?
        if (isset($html['url'])) {
            if (isset($html['target'])
                && $html['target'] === '_blank'
            ) {
                $html['attr']['onclick'] = 'window.open().location.href=\'' . $html['url'] . '\'; return false;';
            } else {
                $html['attr']['onclick'] = 'location.href=\'' . $html['url'] . '\'; return false;';
            }
            unset($html['url'], $html['target']);
            $html['class'] .= ' drts-display-element-with-link';
        }

        $label = $this->_application->Display_ElementLabelSettingsForm_label($settings, $this->displayElementStringId('label', $element['element_id']), $field->getFieldLabel(true));
        if (!strlen($label)) return $html;

        $label_class = 'drts-entity-field-label drts-entity-field-label-type-' . $settings['label'];
        if (!empty($settings['label_as_heading'])) {
            $label_class .= ' drts-display-element-header';
            $label = '<span>' . $label . '</span>';
        }
        $html['html'] = '<div class="' . $label_class . '">' . $label . '</div>'
            . '<div class="drts-entity-field-value">' . $html['html'] . '</div>';
        
        return $html;
    }
    
    public function displayElementIsInlineable(Entity\Model\Bundle $bundle, array $settings)
    {
        if ($renderer = $this->_application->Field_Renderers_impl($settings['renderer'], true)) {
            return (bool)$renderer->fieldRendererInfo('inlineable');
        }
        parent::displayElementIsInlineable($bundle, $settings);
    }
    
    public function displayElementAdminTitle(Entity\Model\Bundle $bundle, array $element)
    {
        $field = $this->_getField($bundle);
        return $this->_application->Display_ElementLabelSettingsForm_label($element['settings'], null, $field->getFieldLabel());
    }
    
    public function displayElementIsPreRenderable(Entity\Model\Bundle $bundle, array &$element, $displayType)
    {
        $renderer = $element['settings']['renderer'];     
        if (!$renderer_impl = $this->_application->Field_Renderers_impl($renderer, true)) return false;
        
        $field = $this->_getField($bundle);
        $renderer_settings = isset($element['settings']['renderer_settings'][$renderer]) ? $element['settings']['renderer_settings'][$renderer] : [];  
        return $renderer_impl->fieldRendererIsPreRenderable($field, $renderer_settings);
    }
    
    public function displayElementPreRender(Entity\Model\Bundle $bundle, array $element, $displayType, &$var)
    {
        $renderer = $element['settings']['renderer'];    
        if (!$renderer_impl = $this->_application->Field_Renderers_impl($renderer, true)) return;
        
        $field = $this->_getField($bundle);
        $renderer_settings = isset($element['settings']['renderer_settings'][$renderer]) ? $element['settings']['renderer_settings'][$renderer] : [];    
        $renderer_impl->fieldRendererPreRender($field, $renderer_settings, $var['entities']);
    }
    
    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        if (isset($element->data['settings']['label'])
            && in_array($element->data['settings']['label'], array('custom', 'custom_icon'))
        ) {
            $this->_registerString($element->data['settings']['label_custom'], 'label', $element->id);
        } else {
            $this->_unregisterString('label', $element->id);
        }
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $field = $this->_getField($bundle);
        $ret = [
            'field' => [
                'label' => __('Field name', 'directories'),
                'value' => $field->getFieldName(),
            ],
        ];
        if (isset($settings['renderer'])) {
            $renderers = $this->_getRenderers($bundle);
            if (isset($renderers[$settings['renderer']])) {
                $ret['field_renderer'] = [
                    'label' => __('Field renderer', 'directories'),
                    'value' => $renderers[$settings['renderer']] . ' (' . $settings['renderer'] . ')',
                ];
                if (isset($settings['renderer_settings'][$settings['renderer']])) {
                    $renderer_settings = $settings['renderer_settings'][$settings['renderer']];
                    if ($renderer = $this->_application->Field_Renderers_impl($settings['renderer'])) {
                        if ($default_settings = $renderer->fieldRendererInfo('default_settings')) {
                            $renderer_settings += $default_settings;
                        }
                        if ($readable_settings = $renderer->fieldRendererReadableSettings($field, $renderer_settings)) {
                            $ret += $readable_settings;
                        }
                    }
                }
            }
        }
        return [
            'settings' => ['value' => $ret],
        ];
    }
}
