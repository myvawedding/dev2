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
        'css' => '.drts-display--detailed .directory-category-description {
  margin-bottom: 1em;
}',
      ),
      'elements' => 
      array (
        237 => 
        array (
          'id' => 237,
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
              'css_class' => 'directory-category-description',
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
        238 => 
        array (
          'id' => 238,
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
                'field' => 'directory_icon',
                'fallback' => false,
                'color' => 
                array (
                  'type' => 'directory_color',
                  'custom' => '',
                ),
                'is_image' => false,
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
              'css_class' => 'directory-category-child-terms',
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
          'weight' => 2,
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
        'css' => '.drts-display--summary .directory-category-title {
  font-size: 1.1em;
  margin-bottom: 0.5em;
}',
      ),
      'elements' => 
      array (
        239 => 
        array (
          'id' => 239,
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
                    'size' => '',
                    'field' => 'directory_icon',
                    'fallback' => 1,
                    'color' => 
                    array (
                      'type' => 'directory_color',
                      'custom' => '',
                    ),
                    'is_image' => false,
                  ),
                  'show_count' => false,
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
              'css_class' => 'directory-category-title',
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
        240 => 
        array (
          'id' => 240,
          'name' => 'entity_child_terms',
          'data' => 
          array (
            'settings' => 
            array (
              'child_count' => '0',
              'columns' => '1',
              'inline' => false,
              'separator' => ', ',
              'hide_empty' => false,
              'show_count' => 1,
              'icon' => false,
              'icon_settings' => 
              array (
                'size' => 'sm',
                'field' => 'directory_icon',
                'fallback' => false,
                'color' => 
                array (
                  'type' => '',
                  'custom' => '',
                ),
                'is_image' => false,
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
              'css_class' => 'directory-category-child-terms',
              'cache' => '3600',
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
          'weight' => 2,
          'system' => false,
        ),
      ),
    ),
  ),
);