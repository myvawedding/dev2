<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class IconPickerField extends AbstractField
{
    protected static $_elements = [];

    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#id'] = $form->getFieldId($name);
        if (!isset($data['#iconset'])) {
            $data['#iconset'] = 'fontawesome';
        }
        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$name] = $data['#id'];
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }

    public function formFieldRender(array &$data, Form $form)
    {
        $data['#attributes']['data-iconset'] = $data['#iconset'];
        if (isset($data['#placement'])) {
            $data['#attributes']['data-placement'] = $data['#placement'];
        }
        if (isset($data['#default_value'])) {
            $data['#attributes']['data-current'] = trim($data['#default_value']);
        }
        $html = sprintf(
            '<button name="%1$s" class="%2$sbtn %2$sbtn-outline-secondary %3$s"%4$s />',
            $data['#name'],
            DRTS_BS_PREFIX,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $this->_application->Attr($data['#attributes'], 'class')
        );

        $this->_render($html, $data, $form);
    }

    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts_iconpicker();
        $form->settings['#js_ready'][] = sprintf(
            '["#%s button"].forEach(function(val) {
    new DRTS.Form.field.iconpicker(val);
});',
            implode(' button", "#', self::$_elements[$form->settings['#id']])
        );
    }
}