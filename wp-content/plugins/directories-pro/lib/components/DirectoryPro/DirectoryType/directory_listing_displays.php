<?php
return array (
  'entity' => 
  array (
    'detailed' => 
    array (
      'name' => 'detailed',
      'type' => 'entity',
      'data' => 
      array (
        'css' => '%class% .directory-listing-rating,
%class% .directory-listing-labels,
%class% .directory-listing-terms {
  margin-bottom: 0.5em;
}
%class% .directory-listing-info {
  margin-bottom: 1em;
}
%class% .directory-listing-description {
  margin-bottom: 2em;
}
%class% .directory-listing-buttons {
  margin-top: 2em;
}
%class% .directory-listing-review-rating {
  font-size:1.2em; 
  margin-bottom:1em;
}
%class% .directory-listing-review-ratings {
  margin-bottom:1.5em;
}',
      ),
      'elements' => 
      array (
        309 => 
        array (
          'id' => 309,
          'name' => 'button',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'dropdown' => 1,
              'dropdown_icon' => 'fas fa-cog',
              'dropdown_label' => '',
              'dropdown_right' => 1,
              'separate' => 1,
              'arrangement' => 
              array (
                0 => 'dashboard_posts_edit',
                1 => 'dashboard_posts_delete',
              ),
              'buttons' => 
              array (
                'dashboard_posts_edit' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-edit',
                    '_color' => 'outline-secondary',
                  ),
                ),
                'dashboard_posts_delete' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-trash-alt',
                    '_color' => 'danger',
                  ),
                ),
              ),
              'btn' => true,
              'tooltip' => true,
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'drts-entity-admin-buttons directory-listing-admin-buttons',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'fade-left',
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 1,
          'system' => false,
        ),
        310 => 
        array (
          'id' => 310,
          'name' => 'button',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'dropdown' => false,
              'dropdown_icon' => 'fas fa-cog',
              'dropdown_label' => '',
              'dropdown_right' => false,
              'separate' => 1,
              'tooltip' => 1,
              'arrangement' => 
              array (
                0 => 'voting_bookmark',
                1 => 'frontendsubmit_add_review_review',
                2 => 'claiming_claim',
              ),
              'buttons' => 
              array (
                'voting_bookmark' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-secondary',
                    '_link_color' => '',
                    'show_count' => false,
                  ),
                ),
                'claiming_claim' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-warning',
                    '_link_color' => '',
                  ),
                ),
                'frontendsubmit_add_review_review' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-primary',
                    '_link_color' => '',
                  ),
                ),
              ),
              'btn' => true,
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-buttons',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 9,
          'system' => false,
        ),
        311 => 
        array (
          'id' => 311,
          'name' => 'columns',
          'data' => 
          array (
            'settings' => 
            array (
              'gutter_width' => 'md',
              'columns' => 3,
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Contact Information', 'directories-pro'),
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-contact-info-container',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'fade-up',
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 10,
          'system' => false,
        ),
        312 => 
        array (
          'id' => 312,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                ),
                'sm' => 
                array (
                  'width' => NULL,
                ),
                'md' => 
                array (
                  'width' => '6',
                ),
                'lg' => 
                array (
                  'width' => NULL,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                ),
                'grow' => false,
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 311,
          'weight' => 11,
          'system' => false,
        ),
        313 => 
        array (
          'id' => 313,
          'name' => 'entity_fieldlist',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'no_border' => false,
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-contact-info',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 312,
          'weight' => 12,
          'system' => false,
        ),
        314 => 
        array (
          'id' => 314,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                ),
                'sm' => 
                array (
                  'width' => NULL,
                ),
                'md' => 
                array (
                  'width' => '6',
                ),
                'lg' => 
                array (
                  'width' => NULL,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                ),
                'grow' => false,
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => array(
                'css_class' => 'drts-display-element-overflow-visible',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 311,
          'weight' => 19,
          'system' => false,
        ),
        315 => 
        array (
          'id' => 315,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Detailed Information', 'directories-pro'),
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-detailed-info-container',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'fade-up',
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 21,
          'system' => false,
        ),
        316 => 
        array (
          'id' => 316,
          'name' => 'entity_field_post_content',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'wp_post_content' => 
                array (
                  'trim' => false,
                  'trim_length' => 200,
                ),
              ),
              'renderer' => 'wp_post_content',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-description',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 315,
          'weight' => 22,
          'system' => false,
        ),
        317 => 
        array (
          'id' => 317,
          'name' => 'entity_fieldlist',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'no_border' => 1,
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-extra-info',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 315,
          'weight' => 23,
          'system' => false,
        ),
        318 => 
        array (
          'id' => 318,
          'name' => 'entity_field_location_address',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-map-marker-alt',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'location_address',
              'renderer_settings' => 
              array (
                'map_map' => 
                array (
                  'height' => 300,
                  'zoom_control' => 1,
                  'map_type_control' => 1,
                  'fullscreen_control' => 1,
                ),
                'location_address' => 
                array (
                  'custom_format' => '1',
                  'format' => '{street}, {city}, {province} {zip}',
                  'link' => false,
                  '_separator' => '<br />',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 13,
          'system' => false,
        ),
        319 => 
        array (
          'id' => 319,
          'name' => 'entity_field_field_phone',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-phone',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'phone' => 
                array (
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'phone',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 14,
          'system' => false,
        ),
        320 => 
        array (
          'id' => 320,
          'name' => 'entity_field_field_fax',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-fax',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'phone' => 
                array (
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'phone',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 15,
          'system' => false,
        ),
        321 => 
        array (
          'id' => 321,
          'name' => 'entity_field_field_email',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-envelope',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'email' => 
                array (
                  'type' => 'default',
                  'label' => 'custom label',
                  'target' => '_self',
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'email',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 16,
          'system' => false,
        ),
        322 => 
        array (
          'id' => 322,
          'name' => 'entity_field_field_website',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-globe',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'url' => 
                array (
                  'type' => 'default',
                  'label' => '',
                  'max_len' => '40',
                  'target' => '_blank',
                  'rel' => 
                  array (
                    0 => 'nofollow',
                    1 => 'external',
                  ),
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'url',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 17,
          'system' => false,
        ),
        323 => 
        array (
          'id' => 323,
          'name' => 'entity_field_field_social_accounts',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'social_accounts',
              'renderer_settings' => 
              array (
                'social_accounts' => 
                array (
                  'size' => 'fa-lg',
                  'target' => '_blank',
                  'rel' => 
                  array (
                    0 => 'nofollow',
                    1 => 'external',
                  ),
                  '_limit' => 0,
                  '_separator' => ' ',
                ),
                'social_twitter_feed' => 
                array (
                  'height' => 600,
                  '_limit' => 0,
                  '_separator' => '',
                ),
                'social_facebook_page' => 
                array (
                  'height' => 600,
                  '_limit' => 0,
                  '_separator' => '',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 313,
          'weight' => 18,
          'system' => false,
        ),
        324 => 
        array (
          'id' => 324,
          'name' => 'entity_field_field_opening_hours',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'form',
              'label_custom' => '',
              'label_icon' => 'fas fa-clock',
              'label_icon_size' => '',
              'label_as_heading' => 1,
              'renderer' => 'directory_opening_hours',
              'renderer_settings' => 
              array (
                'time' => 
                array (
                  '_limit' => 0,
                  '_separator' => ', ',
                ),
                'directory_opening_hours' => 
                array (
                  'show_closed' => 1,
                  'closed' => 'Closed',
                  '_limit' => 0,
                  '_separator' => ', ',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-opening-hours',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 26,
          'system' => false,
        ),
        325 => 
        array (
          'id' => 325,
          'name' => 'entity_field_directory_photos',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'photoslider',
              'renderer_settings' => 
              array (
                'image' => 
                array (
                  'size' => 'thumbnail',
                  'width' => 100,
                  'height' => 0,
                  'cols' => '4',
                  'link' => 'photo',
                  'link_image_size' => 'medium',
                  '_limit' => 0,
                  '_render_background' => false,
                  '_hover_zoom' => false,
                  '_hover_brighten' => false,
                  '_render_empty' => false,
                ),
                'photoslider' => 
                array (
                  'size' => 'large',
                  'show_thumbs' => 1,
                  'thumbs_columns' => '6',
                  'effect' => 'slide',
                  'pager' => false,
                  'auto' => false,
                  'zoom' => 1,
                  'controls' => 1,
                  'show_videos' => 1,
                  'video_field' => 'field_videos',
                  'prepend_videos' => 1,
                  'num_videos' => '1',
                  '_limit' => 0,
                ),
                'wp_gallery' => 
                array (
                  'cols' => '4',
                  'size' => 'thumbnail',
                  '_limit' => 0,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-photos',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 8,
          'system' => false,
        ),
        326 => 
        array (
          'id' => 326,
          'name' => 'entity_field_field_price_range',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'form_icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-dollar-sign',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'range' => 
                array (
                  'dec_point' => '.',
                  'thousands_sep' => ',',
                  'range_sep' => ' to ',
                  '_separator' => ' ',
                ),
              ),
              'renderer' => 'range',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 317,
          'weight' => 25,
          'system' => false,
        ),
        327 => 
        array (
          'id' => 327,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Reviews', 'directories-pro'),
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-review-container',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'fade-up',
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 27,
          'system' => false,
        ),
        328 => 
        array (
          'id' => 328,
          'name' => 'columns',
          'data' => 
          array (
            'settings' => 
            array (
              'gutter_width' => 'lg',
              'columns' => 3,
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-review-ratings',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 327,
          'weight' => 29,
          'system' => false,
        ),
        329 => 
        array (
          'id' => 329,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                ),
                'sm' => 
                array (
                  'width' => NULL,
                ),
                'md' => 
                array (
                  'width' => '4',
                ),
                'lg' => 
                array (
                  'width' => NULL,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                ),
                'grow' => false,
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 328,
          'weight' => 30,
          'system' => false,
        ),
        330 => 
        array (
          'id' => 330,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                  'grow' => 1,
                ),
                'sm' => 
                array (
                  'width' => NULL,
                  'grow' => 1,
                ),
                'md' => 
                array (
                  'width' => '8',
                  'grow' => 1,
                ),
                'lg' => 
                array (
                  'width' => NULL,
                  'grow' => 1,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                  'grow' => 1,
                ),
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 328,
          'weight' => 32,
          'system' => false,
        ),
        331 => 
        array (
          'id' => 331,
          'name' => 'view_child_entities_review_review',
          'data' => 
          array (
            'settings' => 
            array (
              'view' => 'default',
              'cache' => '',
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-reviews',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 327,
          'weight' => 34,
          'system' => false,
        ),
        332 => 
        array (
          'id' => 332,
          'name' => 'entity_field_field_date_established',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'form_icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-calendar-alt',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'date' => 
                array (
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'date',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 317,
          'weight' => 24,
          'system' => false,
        ),
        333 => 
        array (
          'id' => 333,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-info',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 2,
          'system' => false,
        ),
        334 => 
        array (
          'id' => 334,
          'name' => 'labels',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'entity_featured',
                1 => 'directory_open_now',
                2 => 'payment_plan',
              ),
              'labels' => 
              array (
                'entity_featured' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Featured',
                  ),
                ),
                'directory_open_now' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Open Now',
                    'field' => 'field_opening_hours',
                  ),
                ),
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-labels',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 333,
          'weight' => 3,
          'system' => false,
        ),
        335 => 
        array (
          'id' => 335,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => 1,
              'separator' => '  ',
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-terms',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 333,
          'weight' => 5,
          'system' => false,
        ),
        336 => 
        array (
          'id' => 336,
          'name' => 'entity_field_location_location',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_terms' => 
                array (
                  'icon' => 1,
                  'icon_size' => 'sm',
                  '_limit' => 0,
                  '_separator' => '  ',
                ),
              ),
              'renderer' => 'entity_terms',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 335,
          'weight' => 7,
          'system' => false,
        ),
        337 => 
        array (
          'id' => 337,
          'name' => 'entity_field_directory_category',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_terms' => 
                array (
                  'icon' => 1,
                  'icon_size' => 'sm',
                  '_limit' => 0,
                  '_separator' => ' ',
                ),
              ),
              'renderer' => 'entity_terms',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 335,
          'weight' => 6,
          'system' => false,
        ),
        338 => 
        array (
          'id' => 338,
          'name' => 'entity_field_location_address',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'map_map',
              'renderer_settings' => 
              array (
                'map_map' => 
                array (
                  'height' => 200,
                  'view_marker_icon' => 'image',
                  'directions' => 1,
                ),
                'location_address' => 
                array (
                  'format' => '{street}, {city}, {province} {zip}, {country}',
                  'link' => false,
                  '_limit' => 0,
                  '_separator' => '<br />',
                  'custom_format' => '1',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-map',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 314,
          'weight' => 20,
          'system' => false,
        ),
        339 => 
        array (
          'id' => 339,
          'name' => 'entity_field_voting_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'voting_rating' => 
                array (
                  'hide_empty' => false,
                  'hide_count' => false,
                  'read_only' => false,
                  '_separator' => '',
                  '_render_empty' => '1',
                ),
              ),
              'renderer' => 'voting_rating',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-rating',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 333,
          'weight' => 4,
          'system' => false,
        ),
        340 => 
        array (
          'id' => 340,
          'name' => 'entity_field_review_ratings',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'review_ratings' => 
                array (
                  'format' => 'bars',
                  'color' => 
                  array (
                    'type' => '',
                    'value' => '',
                  ),
                  'decimals' => '2',
                  'inline' => false,
                  'bar_height' => 5,
                  'show_count' => false,
                ),
              ),
              'renderer' => 'review_ratings',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 330,
          'weight' => 33,
          'system' => false,
        ),
        341 => 
        array (
          'id' => 341,
          'name' => 'entity_field_review_ratings',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'review_ratings' => 
                array (
                  'format' => 'stars',
                  'color' => 
                  array (
                    'type' => '',
                    'custom' => '',
                  ),
                  'decimals' => '1',
                  'inline' => false,
                  'bar_height' => 10,
                  'show_count' => 1,
                ),
              ),
              'renderer' => 'review_ratings',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-review-rating',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 327,
          'weight' => 28,
          'system' => false,
        ),
        342 => 
        array (
          'id' => 342,
          'name' => 'entity_field_review_ratings',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'review_ratings' => 
                array (
                  'format' => 'bars_level',
                  'color' => 
                  array (
                    'type' => '',
                    'value' => '',
                  ),
                  'decimals' => '1',
                  'inline' => 1,
                  'bar_height' => 18,
                  'show_count' => false,
                ),
              ),
              'renderer' => 'review_ratings',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'globalize' => false,
              'globalize_remove' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 329,
          'weight' => 31,
          'system' => false,
        ),
      ),
    ),
    'dashboard_row' => 
    array (
      'name' => 'dashboard_row',
      'type' => 'entity',
      'data' => 
      array (
        'css' => '%class% .directory-listing-title {
    font-size: 1em;
    font-weight: bold;
    margin-bottom: 0.5em;
}
%class% .directory-listing-title a {
    white-space: normal;
}',
      ),
      'elements' => 
      array (
        343 => 
        array (
          'id' => 343,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => 1,
              'separator' => ' ',
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Title', 'directories-pro'),
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-title-container',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 1,
          'system' => false,
        ),
        344 => 
        array (
          'id' => 344,
          'name' => 'entity_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_title' => 
                array (
                  'link' => 'post',
                  'link_field' => 'field_website,value',
                  'link_target' => '_self',
                  'max_chars' => 0,
                  'icon' => 1,
                  'icon_settings' => 
                  array (
                    'size' => '',
                    'field' => 'directory_photos',
                    'fallback' => false,
                    'color' => 
                    array (
                      'type' => '',
                      'custom' => '',
                    ),
                    'is_image' => true,
                  ),
                  '_separator' => '',
                  'link_rel' => 
                  array (
                  ),
                ),
              ),
              'renderer' => 'entity_title',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-title',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 343,
          'weight' => 2,
          'system' => false,
        ),
        345 => 
        array (
          'id' => 345,
          'name' => 'labels',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'entity_featured',
                1 => 'payment_plan',
              ),
              'labels' => 
              array (
                'entity_featured' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Featured',
                  ),
                ),
                'directory_open_now' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Open Now',
                    'field' => 'field_opening_hours',
                  ),
                ),
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-labels',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 343,
          'weight' => 3,
          'system' => false,
        ),
        346 => 
        array (
          'id' => 346,
          'name' => 'labels',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'entity_status',
              ),
              'labels' => 
              array (
                'entity_featured' => 
                array (
                  'settings' => 
                  array (
                    '_color' => 'warning',
                  ),
                ),
                'payment_plan' => 
                array (
                  'settings' => 
                  array (
                    '_color' => 'secondary',
                  ),
                ),
              ),
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Status', 'directories-pro'),
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-status',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 5,
          'system' => false,
        ),
        347 => 
        array (
          'id' => 347,
          'name' => 'button',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'dropdown' => 1,
              'dropdown_icon' => 'fas fa-cog',
              'dropdown_label' => '',
              'dropdown_right' => 1,
              'separate' => 1,
              'arrangement' => 
              array (
                0 => 'dashboard_posts_edit',
                1 => 'dashboard_posts_delete',
                2 => 'dashboard_posts_submit',
                3 => 'payment_renew',
                4 => 'payment_upgrade',
                5 => 'payment_order_addon',
              ),
              'buttons' => 
              array (
                'payment_renew' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-sync',
                    '_color' => 'outline-secondary',
                  ),
                ),
                'payment_upgrade' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-arrows-alt-v',
                    '_color' => 'outline-secondary',
                  ),
                ),
                'payment_order_addon' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-cart-plus',
                    '_color' => 
                    array (
                    ),
                  ),
                ),
                'dashboard_posts_edit' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-edit',
                    '_color' => 'outline-secondary',
                  ),
                ),
                'dashboard_posts_delete' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-trash-alt',
                    '_color' => 'danger',
                  ),
                ),
                'dashboard_posts_submit' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_icon' => 'fas fa-plus',
                    '_color' => 'outline-secondary',
                  ),
                ),
              ),
              'btn' => true,
              'tooltip' => true,
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-admin-buttons',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 6,
          'system' => false,
        ),
        348 => 
        array (
          'id' => 348,
          'name' => 'entity_field_voting_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'voting_rating' => 
                array (
                  'hide_empty' => 1,
                  'hide_count' => false,
                  'read_only' => 1,
                  '_separator' => '',
                  '_render_empty' => '1',
                ),
              ),
              'renderer' => 'voting_rating',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
            'heading' => NULL,
          ),
          'parent_id' => 343,
          'weight' => 4,
          'system' => false,
        ),
      ),
    ),
    'summary' => 
    array (
      'name' => 'summary',
      'type' => 'entity',
      'data' => 
      array (
        'css' => '%class% .directory-listing-main {
  padding: 0.8em 1em;
}
%class% .directory-listing-title {
  font-size: 1.2em;
}
%class% .directory-listing-labels {
  position: absolute;
  top: 5px;
  left: 10px;
}
%class% .directory-listing-buttons {
  position: absolute;
  bottom: 0;
  width: 100%;
}
%class% .directory-listing-info,
%class% .directory-listing-contact-info {
  font-size: 0.9em; 
  margin: 0.5em 0 0;
}',
      ),
      'elements' => 
      array (
        349 => 
        array (
          'id' => 349,
          'name' => 'columns',
          'data' => 
          array (
            'settings' => 
            array (
              'gutter_width' => 'none',
              'columns' => 3,
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 1,
          'system' => false,
        ),
        350 => 
        array (
          'id' => 350,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                ),
                'sm' => 
                array (
                  'width' => '4',
                ),
                'md' => 
                array (
                  'width' => NULL,
                ),
                'lg' => 
                array (
                  'width' => NULL,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                ),
                'grow' => false,
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-aside',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 349,
          'weight' => 2,
          'system' => false,
        ),
        351 => 
        array (
          'id' => 351,
          'name' => 'column',
          'data' => 
          array (
            'settings' => 
            array (
              'width' => 'responsive',
              'responsive' => 
              array (
                'xs' => 
                array (
                  'width' => '12',
                ),
                'sm' => 
                array (
                  'width' => '8',
                ),
                'md' => 
                array (
                  'width' => NULL,
                ),
                'lg' => 
                array (
                  'width' => NULL,
                ),
                'xl' => 
                array (
                  'width' => NULL,
                ),
                'grow' => false,
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-main',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 349,
          'weight' => 6,
          'system' => false,
        ),
        352 => 
        array (
          'id' => 352,
          'name' => 'entity_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_title' => 
                array (
                  'link' => 'post',
                  'link_field' => 'field_website,value',
                  'link_rel' => 
                  array (
                    0 => 'nofollow',
                    1 => 'external',
                  ),
                  'link_target' => '_self',
                  'max_chars' => 50,
                  'icon' => false,
                  'icon_settings' => 
                  array (
                    'size' => 'sm',
                    'field' => 'directory_photos',
                    'fallback' => false,
                    'is_image' => true,
                  ),
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_title',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-title',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 351,
          'weight' => 7,
          'system' => false,
        ),
        353 => 
        array (
          'id' => 353,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => 1,
              'separator' => '·',
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-info',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 351,
          'weight' => 9,
          'system' => false,
        ),
        354 => 
        array (
          'id' => 354,
          'name' => 'entity_fieldlist',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => 'sm',
              'no_border' => false,
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-contact-info',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 351,
          'weight' => 11,
          'system' => false,
        ),
        355 => 
        array (
          'id' => 355,
          'name' => 'entity_field_directory_photos',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'image',
              'renderer_settings' => 
              array (
                'image' => 
                array (
                  'size' => 'thumbnail',
                  'width' => 100,
                  'height' => 0,
                  'cols' => '1',
                  'link' => 'page',
                  'link_image_size' => 'medium',
                  '_limit' => 1,
                  '_render_background' => 1,
                  '_hover_zoom' => 1,
                  '_hover_brighten' => false,
                  '_render_empty' => 1,
                  '_no_image' => 'thumbnail',
                ),
                'photoslider' => 
                array (
                  'size' => 'large',
                  'show_thumbs' => 1,
                  'thumbs_columns' => '6',
                  'effect' => 'slide',
                  'pager' => false,
                  'auto' => false,
                  'zoom' => 1,
                  'controls' => 1,
                  'show_videos' => false,
                  'video_field' => '',
                  'prepend_videos' => false,
                  '_limit' => 0,
                ),
                'wp_gallery' => 
                array (
                  'cols' => '4',
                  'size' => 'thumbnail',
                  '_limit' => 0,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-photo',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 350,
          'weight' => 3,
          'system' => false,
        ),
        356 => 
        array (
          'id' => 356,
          'name' => 'entity_field_location_address',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-map-marker-alt',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer' => 'location_address',
              'renderer_settings' => 
              array (
                'map_map' => 
                array (
                  'height' => 300,
                  'zoom_control' => 1,
                  'map_type_control' => 1,
                ),
                'location_address' => 
                array (
                  'custom_format' => '1',
                  'format' => '{street}, {city}, {province} {zip}',
                  'link' => false,
                  '_separator' => '<br />',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 354,
          'weight' => 12,
          'system' => false,
        ),
        357 => 
        array (
          'id' => 357,
          'name' => 'entity_field_field_phone',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-phone',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'phone' => 
                array (
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'phone',
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 354,
          'weight' => 13,
          'system' => false,
        ),
        358 => 
        array (
          'id' => 358,
          'name' => 'entity_field_directory_category',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_terms' => 
                array (
                  'icon' => false,
                  'icon_size' => 24,
                  '_limit' => 0,
                  '_separator' => ', ',
                ),
              ),
              'renderer' => 'entity_terms',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
            'heading' => NULL,
          ),
          'parent_id' => 353,
          'weight' => 10,
          'system' => false,
        ),
        359 => 
        array (
          'id' => 359,
          'name' => 'labels',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'entity_featured',
              ),
              'labels' => 
              array (
                'entity_featured' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Featured',
                  ),
                ),
                'directory_open_now' => 
                array (
                  'settings' => 
                  array (
                    '_label' => 'Open Now',
                    'field' => 'field_opening_hours',
                  ),
                ),
              ),
            ),
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'directory-listing-labels',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 350,
          'weight' => 4,
          'system' => false,
        ),
        360 => 
        array (
          'id' => 360,
          'name' => 'entity_field_voting_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'voting_rating' => 
                array (
                  'hide_empty' => 1,
                  'hide_count' => false,
                  'read_only' => 1,
                  '_separator' => '',
                ),
              ),
              'renderer' => 'voting_rating',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-rating',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 351,
          'weight' => 8,
          'system' => false,
        ),
        361 => 
        array (
          'id' => 361,
          'name' => 'button',
          'data' => 
          array (
            'settings' => 
            array (
              'size' => '',
              'dropdown' => false,
              'dropdown_icon' => 'fas fa-cog',
              'dropdown_label' => '',
              'dropdown_right' => false,
              'separate' => 1,
              'tooltip' => false,
              'arrangement' => 
              array (
                0 => 'voting_bookmark',
              ),
              'buttons' => 
              array (
                'voting_bookmark' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => 1,
                    '_color' => 'link',
                    '_link_color' => '#fff',
                    'show_count' => false,
                  ),
                ),
              ),
              'btn' => true,
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-listing-buttons',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 350,
          'weight' => 5,
          'system' => false,
        ),
      ),
    ),
  ),
  'filters' => 
  array (
    'default' => 
    array (
      'name' => 'default',
      'type' => 'filters',
      'data' => 
      array (
      ),
      'elements' => 
      array (
        362 => 
        array (
          'id' => 362,
          'name' => 'view_filter_directory_category',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'directory_category',
              'label' => 'form_icon',
              'label_custom' => __('Categories', 'directories-pro'),
              'label_icon' => 'fas fa-folder',
              'label_icon_size' => '',
              'label_as_heading' => true,
              'filter' => 'view_term_list',
              'field_name' => 'directory_category',
              'filter_name' => 'filter_directory_category',
              'filter_settings' => 
              array (
                'view_term_list' => 
                array (
                  'depth' => 0,
                  'hide_empty' => false,
                  'hide_count' => false,
                  'andor' => 'OR',
                  'visible_count' => 10,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 1,
          'system' => false,
        ),
        363 => 
        array (
          'id' => 363,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'custom_icon',
              'label_custom' => __('Business Info', 'directories-pro'),
              'label_icon' => 'fas fa-info-circle',
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 7,
          'system' => false,
        ),
        364 => 
        array (
          'id' => 364,
          'name' => 'view_filter_field_price_range',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'field_price_range',
              'label' => 'form',
              'label_custom' => __('Price Range', 'directories-pro'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'range',
              'field_name' => 'field_price_range',
              'filter_name' => 'filter_field_price_range',
              'filter_settings' => 
              array (
                'range' => 
                array (
                  'step' => '0.01',
                  'ignore_min_max' => 1,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 363,
          'weight' => 9,
          'system' => false,
        ),
        365 => 
        array (
          'id' => 365,
          'name' => 'view_filter_field_date_established',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'field_date_established',
              'label' => 'form',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'daterange',
              'field_name' => 'field_date_established',
              'filter_name' => 'filter_field_date_established',
              'filter_settings' => 
              array (
                'daterange' => 
                array (
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 363,
          'weight' => 8,
          'system' => false,
        ),
        366 => 
        array (
          'id' => 366,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'custom_icon',
              'label_custom' => __('Others', 'directories-pro'),
              'label_icon' => 'fas fa-ellipsis-v',
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 11,
          'system' => false,
        ),
        367 => 
        array (
          'id' => 367,
          'name' => 'view_filter_field_opening_hours',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'field_opening_hours',
              'label' => 'form',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'time',
              'field_name' => 'field_opening_hours',
              'filter_name' => 'filter_field_opening_hours',
              'filter_settings' => 
              array (
                'time' => 
                array (
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 366,
          'weight' => 12,
          'system' => false,
        ),
        368 => 
        array (
          'id' => 368,
          'name' => 'view_filter_field_videos',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'field_videos',
              'label' => 'form',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'video',
              'field_name' => 'field_videos',
              'filter_name' => 'filter_field_videos',
              'filter_settings' => 
              array (
                'video' => 
                array (
                  'checkbox_label' => 'Show with video only',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 366,
          'weight' => 13,
          'system' => false,
        ),
        369 => 
        array (
          'id' => 369,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'heading' => 
            array (
              'label' => 'custom_icon',
              'label_custom' => __('Locations', 'directories-pro'),
              'label_icon' => 'fas fa-map-marker-alt',
              'label_icon_size' => '',
            ),
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 2,
          'system' => false,
        ),
        370 => 
        array (
          'id' => 370,
          'name' => 'view_filter_location_location',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'location_location',
              'label' => 'form',
              'label_custom' => __('Locations', 'directories-pro'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'view_term_list',
              'field_name' => 'location_location',
              'filter_name' => 'filter_location_location',
              'filter_settings' => 
              array (
                'view_term_list' => 
                array (
                  'depth' => 2,
                  'hide_empty' => false,
                  'hide_count' => false,
                  'icon' => 1,
                  'icon_size' => 'sm',
                  'andor' => 'OR',
                  'visible_count' => 10,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 369,
          'weight' => 3,
          'system' => false,
        ),
        371 => 
        array (
          'id' => 371,
          'name' => 'view_filter_location_address',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'location_address',
              'label' => 'form',
              'label_custom' => __('Location', 'directories-pro'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'location_address',
              'field_name' => 'location_address',
              'filter_name' => 'filter_location_address',
              'filter_settings' => 
              array (
                'location_address' => 
                array (
                  'disable_input' => 1,
                  'radius' => '0',
                  'disable_radius' => false,
                  'placeholder' => '',
                  'search_this_area' => 1,
                  'search_my_loc' => 1,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 369,
          'weight' => 4,
          'system' => false,
        ),
        372 => 
        array (
          'id' => 372,
          'name' => 'view_filter_voting_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'voting_rating',
              'label' => 'form_icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-star',
              'label_icon_size' => '',
              'label_as_heading' => true,
              'filter' => 'voting_rating',
              'field_name' => 'voting_rating',
              'filter_name' => 'filter_voting_rating',
              'filter_settings' => 
              array (
                'voting_rating' => 
                array (
                  'inline' => false,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => NULL,
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 0,
          'weight' => 6,
          'system' => false,
        ),
      ),
    ),
  ),
);