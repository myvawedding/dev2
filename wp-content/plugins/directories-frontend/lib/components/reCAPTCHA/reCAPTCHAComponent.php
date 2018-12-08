<?php
namespace SabaiApps\Directories\Component\reCAPTCHA;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class reCAPTCHAComponent extends AbstractComponent implements Display\IElements
{
    const VERSION = '1.2.12', PACKAGE = 'directories-frontend';

    public static function description()
    {
        return 'Adds a CAPTCHA field to forms using Google reCAPTCHA API.';
    }

    public function getDefaultConfig()
    {
        return array('sitekey' => '', 'secret' => '', 'theme' => 'light', 'type' => 'image', 'size' => 'normal');
    }

    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        return array('recaptcha_captcha');
    }

    public function displayGetElement($name)
    {
        return new DisplayElement\CaptchaDisplayElement($this->_application, $name);
    }

    public function onFormBuildFrontendSubmitLoginOrRegister(array &$form)
    {
        if (!$this->_application->getComponent('FrontendSubmit')->getConfig('login', 'recaptcha')) return;

        $options = array(
            'size' => $this->_config['size'],
            'type' => $this->_config['type'],
            'theme' => $this->_config['theme'],
            'weight' => 9,
        );
        $form['login']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'login', 'trigger' => 'login[login][submit]'));
        if (isset($form['register']['register']['submit'])) {
            $form['register']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'register', 'trigger' => 'register[register][submit]'));
        }
        if (isset($form['guest']['continue'])) {
            $form['guest']['recaptcha'] = $this->_application->reCAPTCHA_Captcha($options + array('name' => 'guest', 'trigger' => 'guest[continue]'));
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['#tabs'][$this->_name] = array(
            '#title' => __('reCAPTCHA', 'directories-frontend'),
            '#weight' => 17,
        );
        $form[$this->_name] = array(
            '#tree' => true,
            '#component' => $this->_name,
            '#tab' => $this->_name,
            '#title' => __('reCAPTCHA API Settings', 'directories-frontend'),
            'sitekey' => array(
                '#title' => __('reCAPTCHA API site key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['sitekey'],
                '#horizontal' => true,
            ),
            'secret' => array(
                '#title' => __('reCAPTCHA API secret key', 'directories-frontend'),
                '#type' => 'textfield',
                '#default_value' => $this->_config['secret'],
                '#horizontal' => true,
            ),
            'size' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA size', 'directories-frontend'),
                '#options' => array(
                    'normal' => __('Normal', 'directories-frontend'),
                    'compact' => __('Compact', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['size']) ? $this->_config['size'] : 'normal',
                '#horizontal' => true,
            ),
            'type' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA type', 'directories-frontend'),
                '#options' => array(
                    'image' => __('Image', 'directories-frontend'),
                    'audio' => __('Audio', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['type']) ? $this->_config['type'] : 'image',
                '#horizontal' => true,
            ),
            'theme' => array(
                '#type' => 'select',
                '#title' => __('reCAPTCHA theme', 'directories-frontend'),
                '#options' => array(
                    'light' => __('Light', 'directories-frontend'),
                    'dark' => __('Dark', 'directories-frontend'),
                ),
                '#default_value' => isset($this->_config['theme']) ? $this->_config['theme'] : 'light',
                '#states' => array(
                    'visible' => array(
                        'select[name="' . $this->_name . '[size]"]' => array('type' => 'one', 'value' => ['normal', 'compact']),
                    ),
                ),
                '#horizontal' => true,
            ),
        );
        $form['FrontendSubmit']['login']['recaptcha'] = array(
            '#type' => 'checkbox',
            '#title' => __('Add reCAPTCHA field', 'directories-frontend'),
            '#default_value' => $this->_application->getComponent('FrontendSubmit')->getConfig('login', 'recaptcha'),
            '#horizontal' => true,
            '#weight' => 10,
            '#states' => array(
                'visible_or' => array(
                    'input[name="FrontendSubmit[login][login_form]"]' => array('type' => 'checked', 'value' => true),
                    'input[name="FrontendSubmit[login][register_form]"]' => array('type' => 'checked', 'value' => true),
                ),
            ),
        );
    }

    public static function events()
    {
        return array(
            // Make sure the callback is called after FrontendSubmit component
            'directoryadminsettingsformfilter' => 99,
        );
    }
}
