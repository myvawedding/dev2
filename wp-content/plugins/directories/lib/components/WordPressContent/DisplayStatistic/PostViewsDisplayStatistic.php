<?php
namespace SabaiApps\Directories\Component\WordPressContent\DisplayStatistic;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Entity;

class PostViewsDisplayStatistic extends Display\Statistic\AbstractStatistic
{   
    protected function _displayStatisticInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'label' => __('Post view count', 'directories'),
            'default_settings' => array(
                '_icon' =>  'fas fa-eye'
            ),
        );
    }
    
    public function displayStatisticRender(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings)
    {
        if (!$func = self::getFunc()) return;
        
        return array(
            'number' => $count = $func($entity->getId()),
            'format' => _n('%d view', '%d views', $count, 'directories'),
        );
    }
    
    public static function getFunc()
    {
        if (function_exists('wpp_get_views')) { // WordPress Popular Posts
            return function ($postId) { return wpp_get_views($postId); };
        } elseif (function_exists('pvc_get_post_views')) { // Post Views Counter
            return function ($postId) { return pvc_get_post_views($postId); };
        }
    }
}