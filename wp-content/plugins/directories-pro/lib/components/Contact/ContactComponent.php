<?php
namespace SabaiApps\Directories\Component\Contact;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class ContactComponent extends AbstractComponent implements Display\IElements
{
    const VERSION = '1.2.17', PACKAGE = 'directories-pro';
    
    public static function interfaces()
    {
        return array('Payment\IFeatures');
    }
    
    public static function description()
    {
        return 'Allows visitors to send messages to content authors/owners through a contact form.';
    }
    
    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        if (!empty($bundle->info['is_taxonomy'])
            || !empty($bundle->info['parent'])
        ) return [];
        
        return array('contact_form');
    }
    
    public function displayGetElement($name)
    {
        return new DisplayElement\FormDisplayElement($this->_application, $name);
    }
    
    public function paymentGetFeatureNames()
    {
        return array('contact_form');
    }
    
    public function paymentGetFeature($name)
    {
        return new PaymentFeature\FormPaymentFeature($this->_application, $name);
    }
        
    public function onCorePlatformWordPressInit()
    {
        // Contact Form 7
        if (defined('WPCF7_VERSION')) {
            add_filter('wpcf7_form_hidden_fields', array($this, 'wpcf7FormHiddenFieldsFilter'));
            add_filter('wpcf7_mail_components', array($this, 'wpcf7MailComponentsFilter'));
        }
        
        // WPForms
        if (defined('WPFORMS_VERSION')) {
            add_action('wpforms_frontend_output', array($this, 'wpformsFrontendOutputAction'));
            add_filter('wpforms_entry_email_atts', array($this, 'wpformsEntryEmailAttsFilter'));
        }
        
        // Gravity Forms
        if (class_exists('\GFForms', false)) {
            add_filter('gform_pre_render', array($this, 'gformPreRenderFilter'));
            add_filter('gform_replace_merge_tags', array($this, 'gformReplaceMergeTagsFilter'));
        }
    }
    
    public function gformPreRenderFilter($form)
    {
        if ($entity = $this->_isDisplaying()) {
            array_push($form['fields'], \GF_Fields::create(array(
                'type' => 'html',
                'content' => '<input type="hidden" name="_drts_entity_id" value="' . intval($entity->getId()) . '" />',
            )));   
        }
        return $form;
    }
    
    public function gformReplaceMergeTagsFilter($text)
    {
        $tag = '{drts_contact_recipients}';
        if (strpos($text, $tag) !== false) {        
            $text = str_replace($tag, ($recipients = $this->_isSending()) ? implode(',', $recipients) : '', $text);
        }
        return $text;
    }
    
    public function wpformsFrontendOutputAction()
    {
        if ($entity = $this->_isDisplaying()) {
            echo '<input type="hidden" name="_drts_entity_id" value="' . intval($entity->getId()) . '" />';
        }
    }
    
    public function wpformsEntryEmailAttsFilter($email)
    {
        if ($recipients = $this->_isSending()) {
            $email['address'] = $recipients;
        }
        return $email;
    }
        
    public function wpcf7FormHiddenFieldsFilter($fields)
    {
        if ($entity = $this->_isDisplaying()) {
            $fields['_drts_entity_id'] = $entity->getId();
        }
        return $fields;
    }
    
    public function wpcf7MailComponentsFilter($components)
    {
        if ($recipients = $this->_isSending()) {
            $components['recipient'] = implode(',', $recipients);
        }
        return $components;
    }
    
    protected function _isDisplaying()
    {
        return (isset($GLOBALS['drts_entity'])
            && !$GLOBALS['drts_entity']->isTaxonomyTerm()
            && Display\Helper\RenderHelper::isRendering($GLOBALS['drts_entity']->getBundleName(), 'detailed')
            && $this->isContactFormEnabled($GLOBALS['drts_entity'])
        ) ? $GLOBALS['drts_entity'] : false;
    }
    
    protected function _isSending()
    {
        return (!empty($_POST['_drts_entity_id'])
            && ($entity = $this->_application->Entity_Entity('post', $_POST['_drts_entity_id']))
            && !$entity->isTaxonomyTerm()
            && ($recipients = $this->_getRecipients($entity))
        ) ? $recipients : false;
    }
    
    protected function _getRecipients(Entity\Type\IEntity $entity)
    {
        $recipients = [];
        if (!$this->_application->isComponentLoaded('Payment')
            || (!$bundle = $this->_application->Entity_Bundle($entity))
            || empty($bundle->info['payment_enable'])
        ) {
            // Payment is not enabled

            if (!isset($bundle)) $bundle = $this->_application->Entity_Bundle($entity);
            if (!$bundle
                || !isset($bundle->info['contact_form']['recipients'])
            ) {
                // Invalid bundle or no contact form settings, send to author
                if (($author = $this->_application->Entity_Author($entity))
                    && $author->email
                ) {
                    $recipients['author'] = $author->email;
                }
            } else {
                $recipients = $this->_getRecipientsFromSettings($bundle->info['contact_form']['recipients'], $entity);
            }
        } else {
            // Payment is enabled
            
            if ($this->_application->Payment_Plan_hasFeature($entity, 'contact_form')
                && ($features = $this->_application->Payment_Plan_features($entity))
            ) {
                if (!empty($features[0]['contact_form']['recipients'])) {
                    $recipients = $this->_getRecipientsFromSettings($features[0]['contact_form']['recipients'], $entity);
                } elseif (!empty($features[1]['contact_form']['recipients'])) {
                    $recipients = $this->_getRecipientsFromSettings($features[1]['contact_form']['recipients'], $entity);
                }
            }
        }
        if ($recipients = $this->_application->Filter('contact_email_recipients', $recipients, [$entity])) {
            $recipients = array_unique($recipients);
        }
        
        return $recipients;
    }
    
    protected function _getRecipientsFromSettings(array $settings, Entity\Type\IEntity $entity)
    {
        $recipients = [];
        foreach ($settings as $recipient) {
            if ($recipient === 'author') {
                if (($author = $this->_application->Entity_Author($entity))
                    && $author->email
                ) {
                    $recipients[$recipient] = $author->email;
                }
            } elseif ($recipient === 'site') {
                if ($site_email = $this->_application->SiteInfo('email')) {
                    $recipients[$recipient] = $site_email;
                }
            } else {
                if (($field = $this->_application->Entity_Field($entity, $recipient))
                    && ($field_type = $this->_application->Field_Type($field->getFieldType(), true))
                    && $field_type instanceof \SabaiApps\Directories\Component\Field\Type\IEmail
                    && ($field_email = $field_type->fieldEmailAddress($field, $entity))
                ) {
                    $recipients[$recipient] = $field_email;
                }
            }
        }
        return $recipients;
    }
    
    public function onEntityBundleSettingsFormFilter(&$form, $bundle, $submitValues)
    {
        if (!empty($bundle->info['is_taxonomy'])
            || !empty($bundle->info['parent'])
            || !empty($bundle->info['is_user'])
            || !empty($bundle->info['internal'])
        ) return;
        
        if ($this->_application->isComponentLoaded('Payment')
            && !empty($bundle->info['payment_enable'])
        ) return; // the setting is integrated with payment feature settings

        $value = empty($bundle->info['contact_form']) ? [] : $bundle->info['contact_form'];
        $form['contact_form'] = [
            '#title' => __('Contact Form', 'directories-pro'),
            '#tree' => true,
            '#weight' => 99,
            '#tab' => 'general',
            'recipients' => [
                '#title' => __('Contact form recipients', 'directories-pro'),
                '#type' => 'checkboxes',
                '#options' => $this->_application->getComponent('Contact')->getRecipientOptions($bundle),
                '#default_value' => isset($value['recipients']) ? $value['recipients'] : ['author'],
                '#horizontal' => true,
                '#columns' => 1,
            ],
        ];
    }
    
    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'contact_form';
    }
    
    public function getRecipientOptions(Entity\Model\Bundle $bundle)
    {
        $options = [
            'author' => __('Post Author', 'directories-pro'),
            'site' => __('Site E-mail', 'directories-pro') . ' - ' . $this->_application->SiteInfo('email'),
        ];
        $options += $this->_application->Entity_Field_options(
            $bundle,
            [
                'interface' => 'Field\Type\IEmail',
                'prefix' => __('Field - ', 'directories-pro'),
            ]
        );
        
        return $options;
    }
    
    public function isContactFormEnabled(Entity\Type\IEntity $entity)
    {
        if ($entity->isTaxonomyTerm()) return false;
        
        if (!$this->_application->isComponentLoaded('Payment')) return true;
        
        if ((!$bundle = $this->_application->Entity_Bundle($entity))
            || !empty($bundle->info['parent'])
        ) return false;
            
        return empty($bundle->info['payment_enable'])
            || $this->_application->Payment_Plan_hasFeature($entity, 'contact_form');
    }
}
