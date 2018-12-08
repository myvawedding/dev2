<?php
namespace SabaiApps\Directories\Component\View\Mode;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Context;

abstract class AbstractMode implements IMode
{
    protected $_application, $_name, $_info;

    public function __construct(Application $application, $name)
    {
        $this->_application = $application;
        $this->_name = $name;
    }
    
    public function __toString()
    {
        return $this->_name;
    }

    public function viewModeInfo($key = null)
    {
        if (!isset($this->_info)) {
            $this->_info = (array)$this->_viewModeInfo();
        }

        return isset($key) ? (isset($this->_info[$key]) ? $this->_info[$key] : null) : $this->_info;
    }
    
    public function viewModeSupports(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['internal']) && !empty($bundle->info['public']);
    }
    
    public function viewModeSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = []){}
    
    public function viewModeNav(Entity\Model\Bundle $bundle, array $settings)
    {
        return [];
    }

    public function viewModeOnView(Entity\Model\Bundle $bundle, Entity\Type\Query $query, Context $context) {}
    
    public function imageSettingsForm(Entity\Model\Bundle $bundle, $prefix, array $settings, $required = false, array $parents = [], $weight = null)
    {
        if (!$fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\IImage', 'empty_value' => ''])) return [];

        return [
            $prefix . 'image_field' => [
                '#type' => 'select',
                '#title' => __('Image field', 'directories'),
                '#description' => __('Select the field used to display the image for each list item.', 'directories'),
                '#horizontal' => true,
                '#options' => $fields,
                '#default_value' => isset($settings[$prefix . 'image_field']) ? $settings[$prefix . 'image_field'] : null,
                '#weight' => isset($weight) ? $weight : null,
                '#required' => $required,
            ],
            $prefix . 'image_size' => [
                '#title' => __('Image size', 'directories'),
                '#type' => 'select',
                '#options' => [
                    'thumbnail' => __('Thumbnail', 'directories'),
                    'thumbnail_scaled' => __('Thumbnail (scaled)', 'directories'),
                    'medium' => __('Medium size', 'directories'),
                    'large' => __('Large size', 'directories'),
                ],
                '#default_value' => $settings[$prefix . 'image_size'],
                '#weight' => isset($weight) ? $weight + 1 : null,
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, [$prefix . 'image_field']))) => ['type' => '!value',  'value' => ''],
                    ],
                ],
            ],
        ];
    }

    abstract protected function _viewModeInfo();
}
