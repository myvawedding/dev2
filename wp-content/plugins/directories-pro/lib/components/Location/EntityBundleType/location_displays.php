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
        'css' => '.drts-display--detailed .location-photo,
.drts-display--detailed .location-description,
.drts-display--detailed .location-child-terms {
  margin-bottom: 1em;
}',
      ),
      'elements' => 
      array (
        230 => 
        array (
          'id' => 230,
          'name' => 'entity_field_term_content',
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
                'wp_term_description' => 
                array (
                  'trim' => false,
                  'trim_length' => 200,
                  'shortcode' => 1,
                ),
              ),
              'renderer' => 'wp_term_description',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'location-description',
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
        231 => 
        array (
          'id' => 231,
          'name' => 'entity_child_terms',
          'data' => 
          array (
            'settings' => 
            array (
              'child_count' => '0',
              'columns' => '2',
              'inline' => false,
              'separator' => ', ',
              'hide_empty' => false,
              'show_count' => 1,
              'icon' => 1,
              'icon_settings' => 
              array (
                'size' => 'sm',
                'field' => 'location_photo',
                'fallback' => false,
                'color' => 
                array (
                  'type' => '',
                  'custom' => '',
                ),
                'is_image' => true,
              ),
              'content_bundle_type' => 'directory__listing',
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
              'css_class' => 'location-child-terms',
              'cache' => '3600',
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
          'weight' => 3,
          'system' => false,
        ),
        232 => 
        array (
          'id' => 232,
          'name' => 'entity_field_location_photo',
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
                  'size' => 'large',
                  'width' => 100,
                  'height' => 0,
                  'link' => 'none',
                  'link_image_size' => 'large',
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
                ),
                'wp_gallery' => 
                array (
                  'cols' => '4',
                  'size' => 'thumbnail',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'location-photo',
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
          'weight' => 1,
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
        'css' => '.drts-display--summary .location-content-count {
  font-size: 0.8em;
}',
      ),
      'elements' => 
      array (
        233 => 
        array (
          'id' => 233,
          'name' => 'entity_field_location_photo',
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
                  'size' => 'medium',
                  'width' => 100,
                  'height' => 0,
                  'link' => 'page',
                  'link_image_size' => 'large',
                  '_render_background' => false,
                  '_hover_zoom' => 1,
                  '_hover_brighten' => 1,
                  '_render_empty' => false,
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
                ),
                'wp_gallery' => 
                array (
                  'cols' => '4',
                  'size' => 'thumbnail',
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'location-photo',
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
        234 => 
        array (
          'id' => 234,
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
              'css_class' => 'drts-display-element-overlay drts-display-element-overlay-center',
            ),
            'visibility' => 
            array (
              'animate' => false,
              'animation' => 'fade-down',
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
        235 => 
        array (
          'id' => 235,
          'name' => 'entity_field_term_title',
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
                'entity_title' => 
                array (
                  'content_bundle_type' => '',
                  '_separator' => '',
                  'link' => 'post',
                  'link_target' => '_self',
                  'show_count' => false,
                  'show_count_label' => false,
                ),
              ),
              'renderer' => 'entity_title',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'location-title',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'slide-down',
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 234,
          'weight' => 3,
          'system' => false,
        ),
        236 => 
        array (
          'id' => 236,
          'name' => 'entity_field_entity_term_content_count',
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
                'entity_term_content_count' => 
                array (
                  'content_bundle_type' => 'directory__listing',
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_term_content_count',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'location-content-count',
            ),
            'visibility' => 
            array (
              'animate' => 1,
              'animation' => 'fade-up',
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 234,
          'weight' => 4,
          'system' => false,
        ),
      ),
    ),
  ),
);