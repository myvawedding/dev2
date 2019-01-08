<?php
namespace SabaiApps\Directories\Component\WordPressContent\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PostContentFieldRenderer extends Field\Renderer\TextRenderer
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
        if (!$post = $entity->post()) return ''; // this should not happen

        setup_postdata($post);
        $GLOBALS['post'] = $post;
        $value = str_replace(']]>', ']]&gt;', apply_filters('the_content', get_the_content()));
        wp_reset_postdata();

        return $value;
    }

    protected function _getTrimmedContent($value, $length, $marker, $link, array $settings, Entity\Type\IEntity $entity)
    {
        if (!$post = $entity->post()) return ''; // this should not happen

        $value = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes($post->post_content);
        // Add WordPress trim marker
        setup_postdata($post);
        $GLOBALS['post'] = $post;
        $marker = apply_filters('excerpt_more', ' ' . '[&hellip;]');
        wp_reset_postdata();

        return parent::_getTrimmedContent($value, $length, $marker, $link, $settings, $entity);
    }
    
    protected function _fieldRendererReadableSettings(Field\IField $field, array $settings)
    {
        $ret = (array)parent::_fieldRendererReadableSettings($field, $settings);
        unset($ret['trim_marker'], $ret['trim_link']);
        return $ret;
    }
}
