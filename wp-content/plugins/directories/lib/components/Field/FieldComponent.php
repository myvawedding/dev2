<?php
namespace SabaiApps\Directories\Component\Field;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\System;

class FieldComponent extends AbstractComponent implements ITypes, IWidgets, IRenderers, IFilters, Form\IFields
{
    const VERSION = '1.2.23', PACKAGE = 'directories';

    protected $_system = true;

    public static function description()
    {
        return 'Provides API to manage, render, and filter content fields.';
    }

    public function fieldGetTypeNames()
    {
        return array('boolean', 'number', 'string', 'text', 'user', 'choice', 'url',
            'range', 'video', 'email', 'phone', 'icon', 'date', 'time', 'color');
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'boolean':
                return new Type\BooleanType($this->_application, $name);
            case 'number':
                return new Type\NumberType($this->_application, $name);
            case 'string':
                return new Type\StringType($this->_application, $name);
            case 'text':
                return new Type\TextType($this->_application, $name);
            case 'user':
                return new Type\UserType($this->_application, $name);
            case 'choice':
                return new Type\ChoiceType($this->_application, $name);
            case 'url':
                return new Type\UrlType($this->_application, $name);
            case 'range':
                return new Type\RangeType($this->_application, $name);
            case 'video':
                return new Type\VideoType($this->_application, $name);
            case 'email':
                return new Type\EmailType($this->_application, $name);
            case 'phone':
                return new Type\PhoneType($this->_application, $name);
            case 'icon':
                return new Type\IconType($this->_application, $name);
            case 'date':
                return new Type\DateType($this->_application, $name);
            case 'time':
                return new Type\TimeType($this->_application, $name);
            case 'color':
                return new Type\ColorType($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return array('textfield', 'textarea', 'select', 'radiobuttons', 'checkboxes',
            'checkbox', 'user', 'url', 'range', 'slider', 'video', 'email',
            'phone', 'icon', 'date', 'time', 'color'
        );
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'textfield':
                return new Widget\TextfieldWidget($this->_application, $name);
            case 'textarea':
                return new Widget\TextareaWidget($this->_application, $name);
            case 'select':
                return new Widget\SelectWidget($this->_application, $name);
            case 'radiobuttons':
                return new Widget\RadioButtonsWidget($this->_application, $name);
            case 'checkboxes':
                return new Widget\CheckboxesWidget($this->_application, $name);
            case 'checkbox':
                return new Widget\CheckboxWidget($this->_application, $name);
            case 'url':
                return new Widget\UrlWidget($this->_application, $name);
            case 'range':
                return new Widget\RangeWidget($this->_application, $name);
            case 'slider':
                return new Widget\SliderWidget($this->_application, $name);
            case 'video':
                return new Widget\VideoWidget($this->_application, $name);
            case 'user':
                return new Widget\UserWidget($this->_application, $name);
            case 'email':
                return new Widget\EmailWidget($this->_application, $name);
            case 'phone':
                return new Widget\PhoneWidget($this->_application, $name);
            case 'icon':
                return new Widget\IconWidget($this->_application, $name);
            case 'date':
                return new Widget\DateWidget($this->_application, $name);
            case 'time':
                return new Widget\TimeWidget($this->_application, $name);
            case 'color':
                return new Widget\ColorWidget($this->_application, $name);
        }
    }

    public function fieldGetRendererNames()
    {
        return array('string', 'number', 'choice', 'text', 'boolean', 'user', 'url', 'range',
            'video', 'phone', 'email', 'checklist', 'date', 'time', 'image', 'icon', 'color', 'whatsapp');
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'text':
                return new Renderer\TextRenderer($this->_application, $name);
            case 'video':
                return new Renderer\VideoRenderer($this->_application, $name);
            case 'checklist':
                return new Renderer\ChecklistRenderer($this->_application, $name);
            case 'image':
                return new Renderer\ImageRenderer($this->_application, $name);
            case 'icon':
                return new Renderer\IconRenderer($this->_application, $name);
            case 'color':
                return new Renderer\ColorRenderer($this->_application, $name);
            case 'whatsapp':
                return new Renderer\WhatsAppRenderer($this->_application, $name);
            case 'date':
                return new Renderer\DateRenderer($this->_application, $name);
            default:
                return new Renderer\DefaultRenderer($this->_application, $name);
        }
    }

    public function fieldGetFilterNames()
    {
        return array('option', 'keyword', 'boolean', 'number', 'range', 'time', 'date', 'daterange', 'video', 'color');
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'option':
                return new Filter\OptionFilter($this->_application, $name);
            case 'keyword':
                return new Filter\KeywordFilter($this->_application, $name);
            case 'boolean':
                return new Filter\BooleanFilter($this->_application, $name);
            case 'number':
                return new Filter\NumberFilter($this->_application, $name);
            case 'range':
                return new Filter\RangeFilter($this->_application, $name);
            case 'date':
                return new Filter\DateFilter($this->_application, $name);
            case 'daterange':
                return new Filter\DateRangeFilter($this->_application, $name);
            case 'time':
                return new Filter\TimeFilter($this->_application, $name);
            case 'video':
                return new Filter\VideoFilter($this->_application, $name);
            case 'color':
                return new Filter\ColorFilter($this->_application, $name);
        }
    }

    public function onFieldITypesInstalled(AbstractComponent $component)
    {
        $this->_application->getPlatform()->setOption('field_types', array($component->getName() => $component->fieldGetTypeNames()) + $this->_getFieldTypes());
    }

    public function onFieldITypesUninstalled(AbstractComponent $component)
    {
        $field_types = $this->_getFieldTypes();
        if (isset($field_types[$component->getName()])) {
            foreach ($field_types[$component->getName()] as $field_type_deleted) {
                $this->_application->Action('field_type_deleted', array($field_type_deleted));
            }
            unset($field_types[$component->getName()]);
            $this->_application->getPlatform()->setOption('field_types', $field_types);
        }
    }

    public function onFieldITypesUpgraded(AbstractComponent $component)
    {
        $field_types = $this->_getFieldTypes();
        if (isset($field_types[$component->getName()])) {
            foreach (array_diff($field_types[$component->getName()], $component->fieldGetTypeNames()) as $field_type_deleted) {
                $this->_application->Action('field_type_deleted', array($field_type_deleted, false));
            }
        }
        $this->_application->getPlatform()->setOption('field_types', array($component->getName() => $component->fieldGetTypeNames()) + $field_types);
    }

    protected function _getFieldTypes()
    {
        return $this->_application->getPlatform()->getOption('field_types', []);
    }

    public function formGetFieldTypes()
    {
        return array('field_query', 'field_condition');
    }

    public function formGetField($type)
    {
        switch ($type) {
            case 'field_query':
                return new FormField\QueryFormField($this->_application, $type);
            case 'field_condition':
                return new FormField\ConditionFormField($this->_application, $type);
        }
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        if (version_compare($current->version, '1.2.0', '<')) {
            $db = $this->_application->getDB();
            $sql = sprintf(
                'ALTER TABLE %1$sentity_field_date MODIFY value BIGINT SIGNED',
                $db->getResourcePrefix()
            );
            try {
                $db->exec($sql);
            } catch (\Exception $e) {}
        }
    }

    public function onSystemAdminSystemToolsFilter(&$tools)
    {
        $tools += $this->_application->Field_Tools();
    }

    public function onSystemAdminRunTool($tool, $progress, array $values = null)
    {
        switch ($tool) {
            case 'field_adjust_time':
                $this->_application->Field_Tools_adjustTime($progress, $values);
                break;
        }
    }
}
