<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Display\Model\Display;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Exception\RuntimeException;
use SabaiApps\Directories\Component\Display\Model\Display as DisplayModel;

class AddDisplay extends Form\Controller
{
    protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $this->_ajaxSubmit = true;
        $this->_ajaxOnSuccessRedirect = true;
        $this->_submitButtons[] = [
            '#btn_label' => __('Add Display', 'directories'),
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ];
        $display_type = $context->getRequest()->asStr('display_type');
        $display_name = $context->getRequest()->asStr('display_name');
        return [
            'method' => [
                '#type' => 'select',
                '#title' => __('Create or copy', 'directories'),
                '#horizontal' => true,
                '#options' => [
                    '' => _x('Create empty', 'create empty display', 'directories'),
                    'template' => __('Copy from template', 'directories'),
                    'existing' => __('Copy from existing', 'directories'),
                ],
                '#default_value' => '',
                '#required' => true,
            ],
            'template' => [
                '#type' => 'select',
                '#options' => ['' => __('Default', 'directories')],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        '[name="method"]' => ['value' => 'template'],
                    ],
                ],
            ],
            'existing' => [
                '#type' => 'select',
                '#options' => self::existingDisplays($this->_application, $context->bundle->name, $display_name, $display_type),
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        '[name="method"]' => ['value' => 'existing'],
                    ],
                ],
            ],
            'name' => [
                '#type' => 'textfield',
                '#title' => __('Display name', 'directories'),
                '#description' => __('Enter a unique name so that that it can be easily referenced. Only lowercase alphanumeric characters and underscores are allowed.', 'directories'),
                '#field_prefix' => $name_prefix = $display_name . '-',
                '#max_length' => 100 - strlen($name_prefix),
                '#required' => true,
                '#regex' => '/^[a-z0-9_]+$/',
                '#horizontal' => true,
                '#element_validate' => [
                    [[$this, '_validateName'], [$context->bundle, $display_type, $name_prefix]],
                ],
            ],
            'display_type' => [
                '#type' => 'hidden',
                '#value' => $context->getRequest()->asStr('display_type'),
            ],
            'display_name' => [
                '#type' => 'hidden',
                '#value' => $context->getRequest()->asStr('display_name'),
            ],
        ];
    }

    public function _validateName(Form\Form $form, &$value, $element, $bundle, $type, $prefix)
    {
        $query = $this->getModel('Display', 'Display')
            ->bundleName_is($bundle->name)
            ->type_is($type)
            ->name_is($prefix . $value);
        if ($query->count()) {
            $form->setError(__('The name is already taken.', 'directories'), $element);
        }
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $display_type = $context->getRequest()->asStr('display_type');
        $display_name = $context->getRequest()->asStr('display_name');
        $new_display_name = $display_name . '-' . $form->values['name'];

        // Fetch display settings
        $display = [];
        switch ($form->values['method']) {
            case 'template':
                if ($form->values['template'] === '') {
                    $bundles_info = $this->Filter(
                        'entity_bundle_info',
                        [$context->bundle->type => $this->Entity_BundleTypeInfo($context->bundle->type, null, false)],
                        [$context->bundle->component, $context->bundle->group]
                    );
                    $bundle_info = $bundles_info[$context->bundle->type];
                    unset($bundles_info);
                    if (!empty($bundle_info['displays'])) {
                        if (is_string($bundle_info['displays'])) {
                            if (file_exists($bundle_info['displays'])) {
                                $display_settings = include $bundle_info['displays'];
                            }
                        } else {
                            $display_settings = $bundle_info['displays'];
                        }
                        if (!empty($display_settings[$display_type][$display_name])) {
                            $display = $display_settings[$display_type][$display_name];
                        }
                    }
                }
                break;
            case 'existing':
                $existing_display_name = $form->values['existing'];
                $existing_display = $this->getModel('Display', 'Display')
                    ->bundleName_is($context->bundle->name)
                    ->type_is($display_type)
                    ->name_is($existing_display_name)
                    ->fetchOne();
                if (!$existing_display) {
                    throw new RuntimeException('Invalid display: ' . $existing_display_name);
                }
                $display = $this->Display_Display_export($existing_display);
                if (isset($display['data']['css'])
                    && strlen($display['data']['css'])
                ) {
                    // Replace CSS class specific to the existing display with the CSS class of the new display
                    $display['data']['css'] = str_replace(
                        '.' . $existing_display->getCssClasses()[0],
                        '.' . Display::cssClass($new_display_name),
                        $display['data']['css']
                    );
                }
                break;
            default:
        }

        // Create display
        $this->Display_Create($context->bundle, $display_type, $new_display_name, $display);

        // Send success
        $admin_path = strtr($this->_application->Entity_BundleTypeInfo($context->bundle, 'admin_path'), [
            ':bundle_name' => $context->bundle->name,
            ':directory_name' => $context->bundle->group,
            ':bundle_group' => $context->bundle->group,
        ]);
        $context->setSuccess($this->Url($admin_path . '/displays', [], 'drts-display-tab2-' . $new_display_name));
    }

    public static function existingDisplays(Application $application, $bundleName, $defaultDisplayName, $type = 'entity')
    {
        $ret = [$defaultDisplayName => __('Default', 'directories')];
        $prefix = $defaultDisplayName . '-';
        $displays = $application->getModel('Display', 'Display')
            ->bundleName_is($bundleName)
            ->type_is($type)
            ->name_startsWith($prefix)
            ->fetch(0, 0, 'name');
        foreach ($displays as $display) {
            $ret[$display->name] = substr($display->name, strlen($prefix));
        }

        return $ret;
    }
}