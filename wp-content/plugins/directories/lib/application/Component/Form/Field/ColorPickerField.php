<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;

class ColorPickerField extends AbstractField
{ 
    protected static $_elements = [];
    
    public function formFieldInit($name, array &$data, Form $form)
    {
        $data['#id'] = $form->getFieldId($name);
        $data['#js_ready'] = 'DRTS.Form.field.colorpicker("#' . $data['#id'] . '");';
        if (!isset(self::$_elements[$form->settings['#id']])) {
            self::$_elements[$form->settings['#id']] = [];
        }
        self::$_elements[$form->settings['#id']][$name] = $data['#id'];
        $form->settings['#pre_render'][__CLASS__] = array($this, 'preRenderCallback');
    }
    
    public function formFieldRender(array &$data, Form $form)
    {
        if (!empty($data['#hide_input'])) {
            $data['#attributes']['data-static-open'] = 1;
        }
        $add_clear = empty($data['#hide_input']) && (!isset($data['#add_clear']) || $data['#add_clear']);
        $html = sprintf(
            '<input class="%1$sform-control %2$s%9$s"%3$s name="%4$s" value="%5$s" style="max-width:200px" type="%6$s" placeholder="%7$s" />%8$s',
            DRTS_BS_PREFIX,
            isset($data['#attributes']['class']) ? $this->_application->H($data['#attributes']['class']) : '',
            $this->_application->Attr($data['#attributes'], 'class'),
            $data['#name'],
            $this->_application->H($data['#default_value']),
            empty($data['#hide_input']) ? 'text' :  'hidden',
            $this->_application->H(__('Select a color', 'directories')),
            $add_clear ? '<i class="drts-clear fas fa-times-circle"></i>' : '',
            $add_clear ? ' drts-form-type-textfield-with-clear' : ''
        );
        
        $this->_render($html, $data, $form);
    }
    
    public function preRenderCallback($form)
    {
        $this->_application->Form_Scripts(array('colorpicker'));
    }
}