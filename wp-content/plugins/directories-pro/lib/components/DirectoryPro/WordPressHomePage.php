<?php
namespace SabaiApps\Directories\Component\DirectoryPro;

use SabaiApps\Directories\Component\WordPressContent;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Platform\WordPress\Loader;

class WordPressHomePage extends WordPressContent\AbstractHomePage
{
    public function __construct(Application $application)
    {
        $methods = array(
            'directory_categories' => false,
            'directory_locations' => false,
            'directory_listings' => false,
            'directory_featured_listings' => true,
        );
        if ($application->isComponentLoaded('Review')) {
            $methods['directory_reviews'] = false;
        }
        parent::__construct($application, $methods);
    }
    
    public static function directory_categories()
    {   
        $title = __('Browse by %s', 'directories-pro');
        $settings = array(
            'mode' => 'list',
            'settings' => array(
                'list_grid' => true,
                'list_no_row' => true,
                'list_grid_gutter_width' => 'md',
                'query' => array(
                    'fields' => array('term_parent' => 0),
                ),
                'sort' => array(
                    'default' => 'term_title',
                    'options' => array('term_title'),
                ),
                'pagination' => array(
                    'no_pagination' => true,
                ),
            ),
        );
        self::_render(__FUNCTION__, 'directory_category', $settings, $title);
    }
    
    public static function directory_locations()
    {
        $title = __('Popular Locations', 'directories-pro');
        $settings = array(
            'mode' => 'masonry',
            'settings' => array(
                'query' => array(
                    'fields' => array(
                        'location_photo' => 1,
                    ),
                    'limit' => 12,
                ),
                'sort' => array(
                    'default' => 'random',
                    'options' => array('random'),
                ),
                'pagination' => array(
                    'no_pagination' => true,
                ),
            ),
        );
        self::_render(__FUNCTION__, 'location_location', $settings, $title);
    }
    
    public static function directory_listings()
    {   
        $title = __('Recent Listings', 'directories-pro');
        $settings = array(
            'mode' => 'list',
            'settings' => array(
                'list_grid' => true,
                'list_grid_cols' => ['num' => 'responsive', 'num_responsive' => ['xs' => 1, 'sm' => 2, 'lg' => 3, 'xl' => 4]],
                'list_no_row' => true,
                'list_grid_gutter_width' => '',
                'query' => array(
                    'limit' => 4,
                ),
                'sort' => array(
                    'default' => 'post_published',
                    'options' => array('post_published'),
                ),
            ),
        );
        self::_render(__FUNCTION__, 'directory__listing', $settings, $title);
    }
    
    public static function directory_featured_listings()
    {
        $settings = array(
            'mode' => 'photoslider',
            'settings' => array(
                'photoslider_image_field' => null,
                'photoslider_image_size' => 'thumbnail',
                'photoslider_caption' => true,
                'photoslider_columns' => 5,
                'photoslider_pager' => true,
                'photoslider_auto' => true,
                'photoslider_controls' => true,
                'photoslider_auto_speed' => 3000,
                'photoslider_fade' => false,
                'photoslider_center' => true,
                'photoslider_height' => 135,
                'photoslider_padding' => 5,
                'photoslider_thumbs' => false,
                'photoslider_thumbs_columns' => 5,
                'photoslider_link' => true,
                'photoslider_zoom' => false,
                'query' => array(
                    'fields' => array('entity_featured' => 1),
                    'limit' => 12,
                ),
                'sort' => array(
                    'default' => 'random',
                    'options' => array('random'),
                ),
            ),
        );
        self::_render(__FUNCTION__, 'directory__listing', $settings, null, true);
        
    }
    
    public static function directory_reviews()
    {   
        $title = __('Recent Reviews', 'directories-pro');
        $settings = array(
            'mode' => 'masonry',
            'settings' => array(
                'masonry_cols' => 'responsive',
                'masonry_cols_responsive' => array('xs' => 1, 'lg' => 2, 'xl' => 3),
                'query' => array(
                    'limit' => 6,
                ),
                'sort' => array(
                    'default' => 'post_published',
                    'options' => array('post_published'),
                ),
            ),
        );
        self::_render(__FUNCTION__, 'review_review', $settings, $title);
    }
    
    protected static function _render($methodName, $bundleType, array $settings, $title = null, $fullWidth = false)
    {
        $application = Loader::getPlatform()->getApplication();
        
        // Check if directory is specified
        if (($post_id = get_the_ID())
            && ($post_meta = get_post_meta($post_id, 'drts_directory', true))
        ) {
            $directory_name = $post_meta;
        } else {
            // Tey fetching the first directory from the database
            if (!$directory = $application->getModel('Directory', 'Directory')->fetchOne()) return;
            
            // Found one, so use the directory
            $directory_name = $directory->name;
        }
        
        // Fetch bundle
        if (!$bundle = $application->Entity_Bundle($bundleType, 'Directory', $directory_name)) return; // not supported by the directory
        
        parent::_display($application, $methodName, $bundle, $settings, $title, $fullWidth);
    }
}