<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;

class AcfDisplayElement extends Display\Element\AbstractElement
{
    protected static $_fieldGroups = [], $_initialized = false;

    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return [
            'type' => 'content',
            'label' => _x('ACF Fields', 'ACF', 'directories'),
            'description' => _x('Adds ACF fields to frontend form', 'ACF', 'directories'),
            'default_settings' => [
                'field_group' => null,
            ],
            'icon' => 'far fa-list-alt',
        ];
    }

    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return class_exists('ACF', false)
            && $display->type === 'form'
            && empty($bundle->info['is_taxonomy']);
    }

    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $options = [];
        if ($field_groups = acf_get_field_groups(['post_type' => $bundle->name])) {
            foreach ($field_groups as $field_group) {
                $options[$field_group['ID']] = $field_group['title'];
            }
        }
        return [
            'field_group' => [
                '#type' => 'select',
                '#title' => _x('Field group', 'ACF', 'directories'),
                '#options' => $options,
                '#required' => true,
                '#horizontal' => true,
            ],
        ];
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        if (!class_exists('ACF', false)
            || $this->_application->getPlatform()->isAdmin()
        ) return;

        if (!self::$_initialized) {
            acf_enqueue_scripts(); // do not use acf_form_head() to prevent form submission on display
            // Populate field values from $_POST if any
            if (!empty($_POST['_acf_form'])
                && !empty($_POST['acf'])
            ) {
                add_filter('acf/pre_render_fields', function ($fields) {
                    foreach (array_keys($fields) as $k) {
                        $key = $fields[$k]['key'];
                        if (isset($_POST['acf'][$key])) {
                            $fields[$k]['value'] = $_POST['acf'][$key];
                        }
                    }
                    return $fields;
                });
            }
            self::$_initialized = true;
        }

        $settings = $element['settings'];
        if (empty($settings['field_group'])) return;

        // Allow filed group to be rendered 1 time per post type
        if (!empty(self::$_fieldGroups[$bundle->name][$settings['field_group']])) return;

        self::$_fieldGroups[$bundle->name][$settings['field_group']] = true;
        $args = [
            'form' => false, // do not output <form> tag and submit button
            'field_groups' => [$settings['field_group']],
            'post_id' => isset($var['#entity']) && ($post_id = $var['#entity']->getId()) ? $post_id : false,
            'honeypot' => false,
            'return' => false, // no redirect
        ];
        ob_start();
        acf_form($args);
        return ob_get_clean();
    }

    public static function entityFormSubmitCallback(Form\Form $form)
    {
        if (empty($_POST['_acf_form'])) return;

        // Get form
        if (!acf_verify_nonce('acf_form')
            || (!$acf_form = json_decode(acf_decrypt($_POST['_acf_form']), true))
        ) {
            $form->setError(__('An error occurred while processing the form.', 'directories'));
            return;
        }

        // kses
        if ($acf_form['kses']
            && isset($_POST['acf'])
        ) {
            $_POST['acf'] = wp_kses_post_deep($_POST['acf']);
        }

        // Validate
        if (!acf_validate_save_post()
            && ($errors = acf_get_validation_errors())
        ) {
            foreach ($errors as $error) {
                if (strpos($error['input'], 'acf[') === 0
                    && ($field = acf_get_field(substr($error['input'], 4, -1)))
                ) {
                    $label = $field['label'];
                } else {
                    $label = '';
                }
                $form->setError($error['message'], $label);
            }
            return;
        }

        // Submit
        acf()->form_front->submit_form($acf_form);
    }
}