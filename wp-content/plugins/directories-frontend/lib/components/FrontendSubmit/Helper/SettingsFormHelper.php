<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class SettingsFormHelper
{
    public function help(Application $application, array $config, array $parents)
    {
        $form = [];
        $guest_field_name_prefix = $application->Form_FieldName(array_merge($parents, array('guest')));
        $form['guest'] = array(
            '#weight' => 10,
            '#title' => __('Guest Post Settings', 'directories-frontend'),
            '#tree' => true,
            'collect_name' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect guest name', 'directories-frontend'),
                '#default_value' => !isset($config['guest']['collect_name']) || !empty($config['guest']['collect_name']),
                '#weight' => 2,
                '#horizontal' => true,
            ),
            'require_name' => array(
                '#type' => 'checkbox',
                '#title' => __('Require guest name', 'directories-frontend'),
                '#default_value' => !isset($config['guest']['require_name']) || !empty($config['guest']['require_name']),
                '#weight' => 3,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_name]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'collect_email' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['collect_email']),
                '#weight' => 5,
                '#horizontal' => true,
            ),
            'require_email' => array(
                '#type' => 'checkbox',
                '#title' => __('Require e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['require_email']),
                '#weight' => 6,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'check_exists' => array(
                '#type' => 'checkbox',
                '#title' => __('Do not allow e-mail address used by registered users', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['check_exists']),
                '#weight' => 7,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
            'collect_url' => array(
                '#type' => 'checkbox',
                '#title' => __('Collect website URL', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['collect_url']),
                '#weight' => 10,
                '#horizontal' => true,
            ),
            'require_url' => array(
                '#type' => 'checkbox',
                '#title' => __('Require website URL', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['require_url']),
                '#weight' => 11,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_url]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            ),
        );
        if (Form\Field\TextField::canCheckMx()) {
            $form['guest']['check_mx'] = array(
                '#type' => 'checkbox',
                '#title' => __('Check MX record of e-mail address', 'directories-frontend'),
                '#default_value' => !empty($config['guest']['check_mx']),
                '#weight' => 8,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $guest_field_name_prefix . '[collect_email]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
                '#horizontal' => true,
            );
        }
        $form['guest']['collect_privacy'] = array(
            '#type' => 'checkbox',
            '#title' => __('Add a privacy policy consent checkbox', 'directories-frontend'),
            '#default_value' => !empty($config['guest']['collect_privacy']),
            '#weight' => 15,
            '#horizontal' => true,
        );

        $form['login'] = array(
            '#weight' => 20,
            '#title' => __('User Login/Registration Settings', 'directories-frontend'),
            '#tree' => true,
            'login_form' => array(
                '#type' => 'checkbox',
                '#title' => __('Show user login form', 'directories-frontend'),
                '#default_value' => !empty($config['login']['login_form']),
                '#horizontal' => true,
                '#weight' => 1,
            ),
            'register_form' => array(
                '#type' => 'checkbox',
                '#title' => __('Show user registration form', 'directories-frontend'),
                '#default_value' => !empty($config['login']['register_form']),
                '#horizontal' => true,
                '#weight' => 3,
            ),
            'register_privacy' => array(
                '#type' => 'checkbox',
                '#title' => __('Add a privacy policy consent checkbox', 'directories-frontend'),
                '#default_value' => !empty($config['login']['register_privacy']),
                '#weight' => 5,
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        'input[name="' . $application->Form_FieldName(array_merge($parents, array('login'))) . '[register_form]"]' => array('type' => 'checked', 'value' => true),
                    ),
                ),
            ),
        );

        return $form;
    }
}
