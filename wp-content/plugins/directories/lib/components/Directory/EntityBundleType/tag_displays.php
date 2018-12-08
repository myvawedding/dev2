<?php
return array (
  'entity' => 
  array (
    'detailed' => 
    array (
      'name' => 'detailed',
      'type' => 'entity',
      'data' => false,
      'elements' => 
      array (
        241 => 
        array (
          'id' => 241,
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
              'css_class' => 'directory-tag-description',
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
      'data' => false,
      'elements' => 
      array (
        242 => 
        array (
          'id' => 242,
          'name' => 'entity_field_term_title',
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
                  'link_target' => '_self',
                  'max_chars' => 0,
                  'icon' => 1,
                  'icon_settings' => 
                  array (
                    'size' => 'sm',
                    'fallback' => false,
                    'color' => 
                    array (
                      'type' => '',
                      'custom' => '',
                    ),
                    'field' => '',
                    'is_image' => false,
                  ),
                  'show_count' => 1,
                  'show_count_label' => false,
                  'content_bundle_type' => 'directory__listing',
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_title',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'directory-tag-title',
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
      ),
    ),
  ),
);