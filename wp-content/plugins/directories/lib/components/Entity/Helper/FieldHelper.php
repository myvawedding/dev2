<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Field\IField;

class FieldHelper
{
    private $_fields = [];

    /**
     * Returns a field object of an entity
     * @param Application $application
     * @param \SabaiApps\Directories\Component\Entity\Type\IEntity|\SabaiApps\Directories\Component\Entity\Model\Bundle|string $entityOrBundle
     * @param string $fieldName
     */
    public function help(Application $application, $entityOrBundle, $fieldName = null, $componentName = null, $group = '', $throwException = false)
    {
        if ($entityOrBundle instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            $bundle_name = $entityOrBundle->getBundleName();
        } elseif ($entityOrBundle instanceof \SabaiApps\Directories\Component\Entity\Model\Bundle) {
            $bundle_name = $entityOrBundle->name;
        } elseif (!is_string($entityOrBundle)) {
            if (!$throwException) return false;

            throw new Exception\InvalidArgumentException();
        } else {
            if (isset($componentName)) {
                if (!$bundle = $application->Entity_Bundle($entityOrBundle, $componentName, $group, $throwException)) {
                    return false;
                }
                $bundle_name = $bundle->name;
            } else {
                $bundle_name = $entityOrBundle;
            }
        }
        // Check if fields for the Entity_Field_optionsentity are already loaded
        if (!isset($this->_fields[$bundle_name])) {
            // Load fields
            $this->_fields[$bundle_name] = [];
            foreach ($application->Entity_Bundle($bundle_name)->with('Fields', 'FieldConfig')->Fields as $field) {
                $this->_fields[$bundle_name][$field->getFieldName()] = $field;
            }
        }
        if (isset($fieldName)) {
            return isset($this->_fields[$bundle_name][$fieldName]) ? $this->_fields[$bundle_name][$fieldName] : null;
        }

        return $this->_fields[$bundle_name];
    }

    protected function _getEntity(Application $application, $entity)
    {
        if (!$entity instanceof \SabaiApps\Directories\Component\Entity\Type\IEntity) {
            if (is_array($entity)) {
                $entity_id = $entity[0];
                $entity_type = $entity[1];
            } else {
                $entity_id = $entity;
                $entity_type = 'post';
            }
            if (!$entity = $application->Entity_Entity($entity_type, $entity_id)) {
                return;
            }
        }
        return $entity;
    }

    public function options(Application $application, $entityOrBundle, array $options = [])
    {
        $options += [
            'type' => null,
            'interface' => null,
            'interface_exclude' => null,
            'empty_value' => null,
            'prefix' => '',
            'name_prefix' => '',
        ];
        $fields = [];
        if (isset($options['type'])) settype($options['type'], 'array');
        if (isset($options['interface'])) $options['interface'] = '\SabaiApps\Directories\Component\\' . $options['interface'];
        if (isset($options['interface_exclude'])) $options['interface_exclude'] = '\SabaiApps\Directories\Component\\' . $options['interface_exclude'];
        foreach ($this->help($application, $entityOrBundle) as $field_name => $field) {
            if (!empty($options['type'])
                && !in_array($field->getFieldType(), $options['type'])
            ) continue;

            if (!$field_type = $application->Field_Type($field->getFieldType(), true)) continue;

            if (isset($options['interface'])
                && !$field_type instanceof $options['interface']
            ) continue;

            if (isset($options['interface_exclude'])
                && $field_type instanceof $options['interface_exclude']
            ) continue;

            $fields[(string)$options['name_prefix'] . $field_name] = (string)$options['prefix'] . $field->getFieldLabel() . ' (' . $field_name . ')';
        }
        if (!empty($fields)) {
            asort($fields);
            if (isset($options['empty_value'])) {
                $fields = array($options['empty_value'] => __('— Select —', 'directories')) + $fields;
            }
        }

        return $fields;
    }

    public function render(Application $application, $entity, $fieldName, $rendererName, array $settings, array $values = null, $index = null)
    {
        return $this->renderBySettingsReference($application, $entity, $fieldName, $rendererName, $settings, $values, $index);
    }

    public function renderBySettingsReference(Application $application, $entity, $fieldName, $rendererName, array &$settings, array $values = null, $index = null)
    {
        if (!$entity = $this->_getEntity($application, $entity)) {
            $application->logError('Invalid entity ' . $entity);
            return '';
        }
        if (!$field = $this->help($application, $entity, $fieldName)) {
            $application->logError('Invalid field ' . $fieldName);
            return '';
        }
        if (!isset($values)
            && (!$values = $entity->getFieldValue($field->getFieldName()))
        ) {
            return '';
        }

        if (isset($index)) {
            if (!array_key_exists($index, $values)) return '';

            $values = [$values[$index]];
        }
        try {
            $renderer = $application->Field_Renderers_impl($rendererName);
            if ($default_settings = $renderer->fieldRendererInfo('default_settings')) {
                $settings += $default_settings;
            }
            $html = $renderer->fieldRendererRenderField($field, $settings, $entity, $values);
            if (!is_array($html) && !strlen($html)) return '';
        } catch (Exception\IException $e) {
            $application->logError($e);
            return '';
        }

        return $application->Filter('entity_field_render', $html, [$entity, $field, $rendererName, $settings, $values]);
    }
}
