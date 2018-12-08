<?php
namespace SabaiApps\Directories\Component\Contact\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Type\IEntity;

class FormHelper
{
    public function help(Application $application, IEntity $entity, $formId)
    {
        if (!$application->getComponent('Contact')->isContactFormEnabled($entity)) return;
        
        if (strpos($formId, 'wpcf7-') === 0) {
            if (($parts = explode('-', $formId))
                && !empty($parts[1])
            ) {
                return do_shortcode('[contact-form-7 id="' . $this->_maybeGetTranslatedPostId($application, $parts[1], 'wpcf7_contact_form') . '"]');
            }
        } elseif (strpos($formId, 'wpforms-') === 0) {
            if (($parts = explode('-', $formId))
                && !empty($parts[1])
            ) {
                return do_shortcode('[wpforms id="' . $this->_maybeGetTranslatedPostId($application, $parts[1], 'wpforms') . '" title="false" description="false"]');
            }
        } elseif (strpos($formId, 'gform-') === 0) {
            if (($parts = explode('-', $formId))
                && !empty($parts[1])
            ) {
                return do_shortcode('[gravityform ajax="true" id="' . intval($parts[1]) . '" title="false" description="false"]');
            }
        }
    }
    
    protected function _maybeGetTranslatedPostId(Application $application, $id, $bundleName)
    {
        $id = intval($id);
        if (($lang = $application->getPlatform()->getCurrentLanguage())
            && ($translated_entity_id = $application->getPlatform()->getTranslatedId('post', $bundleName, $id, $lang))
        ) {
           $id = $translated_entity_id;  
        }
        return $id;
    }
    
    public function options(Application $application)
    {
        $options = [];
        if (defined('WPCF7_VERSION')
            && ($wpcf7_forms = get_posts(array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1)))
        ) {
            foreach ($wpcf7_forms as $form) {
                $options['wpcf7-' . $form->ID] = 'Contact Form 7 - ' . $form->post_title;
            }
        }
        
        if (function_exists('wpforms')) {
            $wpforms_forms = wpforms()->form->get('', array('orderby' => 'title'));
            if (!empty($wpforms_forms)) {
                foreach ($wpforms_forms as $form) {
                    $options['wpforms-' . $form->ID] = 'WPForms - ' . $form->post_title;
                }
            }
        }
        
        if (class_exists('\GFAPI', false)
            && ($gravity_forms = \GFAPI::get_forms())
        ) {
            foreach ($gravity_forms as $form) {
                $options['gform-' . $form['id']] = 'Gravity Form - ' . $form['title'];
            }
        }
        
        return $options;
    }
}