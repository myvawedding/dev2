<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class TermDescriptionFieldRenderer extends Field\Renderer\TextRenderer
{    
    protected function _fieldRendererInfo()
    {
        $ret = parent::_fieldRendererInfo();
        $ret['field_types'] = array($this->_name);
        $ret['separatable'] = false;
        return $ret;
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $ret = parent::_fieldRendererSettingsForm($field, $settings, $parents);
        unset($ret['trim_marker'], $ret['trim_link']);
        return $ret;
    }
    
    protected function _getContent($value, array $settings, Entity\Type\IEntity $entity)
    {
        return parent::_getContent($this->_getTermDescription($entity), $settings, $entity);
    }
    
    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        $value = strip_shortcodes($this->_getTermDescription($entity));
        // Add WordPress trim marker
        $marker = apply_filters('excerpt_more', ' ' . '[&hellip;]');

        return parent::_getTrimmedContent($value, $length, $marker, $link, $settings, $entity);
    }

    protected function _getTermDescription(Entity\Type\IEntity $entity)
    {
        return term_description($entity->getId(), $entity->getBundleName());
    }
}
