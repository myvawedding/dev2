<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class CommentsDisplayElement extends Display\Element\AbstractElement
{    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Comments', 'display element name', 'directories'),
            'description' => __('Comments posted for the current content', 'directories'),
            'default_settings' => [],
            'icon' => 'far fa-comments',
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity' && empty($bundle->info['is_taxonomy']);
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        // Comments may be disabled
        if (!post_type_supports($bundle->name, 'comments')
            || !comments_open()
        ) return;
        
        ob_start();
        comments_template('', true);
        return ob_get_clean();
    }
}