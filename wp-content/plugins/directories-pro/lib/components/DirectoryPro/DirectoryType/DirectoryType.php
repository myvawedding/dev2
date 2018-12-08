<?php
namespace SabaiApps\Directories\Component\DirectoryPro\DirectoryType;

use SabaiApps\Directories\Component\Directory;

class DirectoryType extends Directory\Type\DirectoryType
{   
    public function directoryContentTypeInfo($contentType)
    {
        switch ($contentType) {
            case 'listing':
                return array(
                    'component' => 'DirectoryPro',
                    'fields' => 'directory_listing_fields.php',
                    'entity_image' => 'directory_photos',
                    'search_fields' => array (
                        'keyword' => array (
                            'disabled' => false,
                            'settings' => array (
                                'min_length' => 2,
                                'match' => 'all',
                                'taxonomies' => array ('directory_category', 'directory_tag'),
                                'suggest' => array (
                                    'enable' => true,
                                    'settings' => array (
                                        'min_length' => 1,
                                        'post_jump' => false,
                                        'post_num' => 5,
                                    ),
                                    'directory_category' => true,
                                    'directory_category_jump' => false,
                                    'directory_category_num' => 5,
                                    'directory_category_hide_empty' => false,
                                    'directory_category_hide_count' => false,
                                    'directory_category_depth' => 0,
                                    'directory_category_inc_parents' => 1,
                                    'directory_tag' => false,
                                    'location_location' => false,
                                ),
                                'form' => array (
                                    'icon' => 'fas fa-search',
                                    'placeholder' => 'Search for...',
                                    'order' => 1,
                                ),
                                'child_bundle_types' => array (),
                            ),
                        ),
                        'location_address' => array (
                            'disabled' => false,
                            'settings' => array (
                                'suggest_place_country' => '',
                                'radius' => '0',
                                'geolocation' => 1,
                                'suggest' => array (
                                    'enable' => 1,
                                    'settings' => array (
                                        'depth' => 0,
                                        'hide_empty' => false,
                                        'hide_count' => false,
                                        'inc_parents' => 1,
                                    ),
                                ),
                                'form' => array (
                                    'icon' => 'fas fa-map-marker-alt',
                                    'placeholder' => 'Near...',
                                    'order' => 2,
                                ),
                            ),
                        ),
                        'term_location_location' => array (
                            'disabled' => 1,
                        ),
                        'term_directory_category' => array (
                            'disabled' => 1,
                        ),
                    ),
                    'location_enable' => true,
                    'location_field' => array('weight' => 3),
                    'location_marker_taxonomy' => 'directory_category',
                    'claiming_enable' => true,
                    'contact_enable' => true,
                    'payment_enable' => true,
                    'review_enable' => true,
                ) + parent::directoryContentTypeInfo($contentType);
        }
    }
}