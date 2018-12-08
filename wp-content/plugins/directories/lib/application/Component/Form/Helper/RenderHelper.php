<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class RenderHelper
{
    public function help(Application $application, $form)
    {        
        if ($form instanceof \SabaiApps\Directories\Component\Form\Form) {
            $settings = $form->settings;
            $use_cache = !$form->rebuild;
            $values = $form->values;
            $errors = $form->getError();
        } elseif (is_array($form)) {
            $settings = $form;
            $use_cache = true;
            $values = null;
            $errors = [];
        } else {
            return '';
        }
        
        return $application->Form_Build($settings, $use_cache, $values, $errors)->render();
    }
}