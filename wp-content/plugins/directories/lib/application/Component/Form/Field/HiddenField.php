<?php
namespace SabaiApps\Directories\Component\Form\Field;

use SabaiApps\Directories\Component\Form\Form;
use SabaiApps\Directories\Exception;

class HiddenField extends AbstractField
{
    public function formFieldRender(array &$data, Form $form)
    {
        $classes = [$data['#class']];
        if (isset($data['#attributes']['class'])) {
            $classes[] = $data['#attributes']['class'];
        }
        $rendered = sprintf(
            '<input type="hidden" name="%s" class="%s" id="%s" value="%s"%s>',
            $data['#name'],
            implode(' ', $classes),
            $data['#id'],
            isset($data['#default_value']) ? $this->_application->H($data['#default_value']) : '',
            $this->_application->Attr($data['#attributes'], 'class')
        );
        if (!empty($data['#render_hidden_inline'])) {
            $data['#html'][] = $rendered;
        } else {
            // Moves to bottom of the form
            $form->settings['#rendered_hiddens'][] = $rendered;
        }
    }
}