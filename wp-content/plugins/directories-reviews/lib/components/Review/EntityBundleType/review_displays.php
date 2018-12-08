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
        'css' => '.drts-display--detailed .review-author-container {
  margin-top: 0.5em;
}
.drts-display--detailed .review-body,
.drts-display--detailed .review-rating-bars,
.drts-display--detailed .review-photos {
  margin-top: 1em;
}
.drts-display--detailed .review-buttons {
  margin-top: 2em;
}',
      ),
      'elements' => 
      array (
        196 => 
        array (
          'id' => 196,
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
              'css_class' => 'drts-entity-admin-buttons review-admin-buttons',
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
        197 => 
        array (
          'id' => 197,
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
              'css_class' => 'review-body',
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
          'weight' => 7,
          'system' => false,
        ),
        198 => 
        array (
          'id' => 198,
          'name' => 'entity_field_review_rating',
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
                'review_rating' => 
                array (
                  'format' => 'bars',
                  'color' => 
                  array (
                    'type' => '',
                    'value' => '',
                  ),
                  'decimals' => '1',
                  'inline' => 1,
                  'bar_height' => 5,
                ),
              ),
              'renderer' => 'review_rating',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-rating-bars',
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
        199 => 
        array (
          'id' => 199,
          'name' => 'entity_field_review_photos',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
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
                  'cols' => '6',
                  'link' => 'photo',
                  'link_image_size' => 'medium',
                  '_limit' => 0,
                  '_render_background' => false,
                  '_hover_zoom' => false,
                  '_hover_brighten' => false,
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
                  '_limit' => 0,
                ),
                'wp_gallery' => 
                array (
                  'cols' => '6',
                  'size' => 'thumbnail',
                  '_limit' => 0,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-photos',
            ),
            'visibility' => 
            array (
              'animate' => false,
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
          'weight' => 9,
          'system' => false,
        ),
        200 => 
        array (
          'id' => 200,
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
              'arrangement' => 
              array (
                0 => 'voting_bookmark',
                1 => 'voting_updown',
                2 => 'voting_updown_down',
              ),
              'buttons' => 
              array (
                'voting_updown' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-success',
                    'show_count' => 1,
                  ),
                ),
                'voting_updown_down' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-danger',
                    'show_count' => 1,
                  ),
                ),
                'voting_bookmark' => 
                array (
                  'settings' => 
                  array (
                    '_hide_label' => false,
                    '_color' => 'outline-secondary',
                    'show_count' => false,
                  ),
                ),
              ),
              'btn' => true,
              'tooltip' => true,
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-buttons',
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
          'weight' => 10,
          'system' => false,
        ),
        201 => 
        array (
          'id' => 201,
          'name' => 'wp_comments',
          'data' => 
          array (
            'settings' => 
            array (
            ),
            'heading' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Comments', 'directories-reviews'),
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'review-comments',
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
          'weight' => 11,
          'system' => false,
        ),
        202 => 
        array (
          'id' => 202,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => false,
              'separator' => '',
            ),
            'advanced' => 
            array (
              'css_class' => 'review-info',
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
            'heading' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => 'fas ',
              'label_icon_size' => '',
            ),
          ),
          'parent_id' => 0,
          'weight' => 2,
          'system' => false,
        ),
        203 => 
        array (
          'id' => 203,
          'name' => 'entity_field_review_rating',
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
                'review_rating' => 
                array (
                  'format' => 'stars',
                  'color' => 
                  array (
                    'type' => '',
                    'custom' => '',
                  ),
                  'decimals' => '1',
                  'inline' => false,
                  'bar_height' => 12,
                ),
              ),
              'renderer' => 'review_rating',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-rating',
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
          'parent_id' => 202,
          'weight' => 3,
          'system' => false,
        ),
        204 => 
        array (
          'id' => 204,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => 1,
              'separator' => ' · ',
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
              'css_class' => 'review-author-container',
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
          'parent_id' => 202,
          'weight' => 4,
          'system' => false,
        ),
        205 => 
        array (
          'id' => 205,
          'name' => 'entity_field_post_author',
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
                'entity_author' => 
                array (
                  'format' => 'link_thumb_s',
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_author',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-author',
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
          'parent_id' => 204,
          'weight' => 5,
          'system' => false,
        ),
        206 => 
        array (
          'id' => 206,
          'name' => 'entity_field_post_published',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-calendar-alt',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_published' => 
                array (
                  'format' => 'datetime',
                  'permalink' => false,
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_published',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-date',
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
          'parent_id' => 204,
          'weight' => 6,
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
        'css' => '.drts-display--summary .review-title {
  font-size: 1.2em;
}
.drts-display--summary .review-author-container,
.drts-display--summary .review-body,
.drts-display--summary .review-photos {
  margin-top: 0.5em;
}
.drts-display--summary .review-stats {
  margin-top: 1em;
  text-align: right;
}
.drts-display-rtl.drts-display--summary .review-stats {
  text-align: left;
}',
      ),
      'elements' => 
      array (
        207 => 
        array (
          'id' => 207,
          'name' => 'entity_field_post_title',
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
                  'link' => 'post',
                  'link_target' => '_self',
                  'icon' => false,
                  'icon_settings' => 
                  array (
                    'size' => 'sm',
                    'field' => 'review_photos',
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
              'css_class' => 'review-title',
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
        208 => 
        array (
          'id' => 208,
          'name' => 'entity_field_review_rating',
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
                'review_rating' => 
                array (
                  'format' => 'stars',
                  'color' => 
                  array (
                    'type' => '',
                    'custom' => '',
                  ),
                  'decimals' => '1',
                  'inline' => false,
                  'bar_height' => 12,
                ),
              ),
              'renderer' => 'review_rating',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-rating',
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
        209 => 
        array (
          'id' => 209,
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
                  'trim' => 1,
                  'trim_length' => 200,
                ),
              ),
              'renderer' => 'wp_post_content',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-body',
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
          'weight' => 7,
          'system' => false,
        ),
        210 => 
        array (
          'id' => 210,
          'name' => 'entity_field_review_photos',
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
                  'cols' => '4',
                  'link' => 'none',
                  'link_image_size' => 'medium',
                  '_limit' => 0,
                  '_render_background' => false,
                  '_hover_zoom' => false,
                  '_hover_brighten' => false,
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
                  '_limit' => 0,
                ),
                'wp_gallery' => 
                array (
                  'cols' => '6',
                  'size' => 'thumbnail',
                  '_limit' => 6,
                ),
              ),
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-photos',
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
          'weight' => 8,
          'system' => false,
        ),
        211 => 
        array (
          'id' => 211,
          'name' => 'statistics',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'wp_comments',
                1 => 'voting_updown',
                2 => 'voting_updown_down',
              ),
              'separator' => '  ',
              'hide_empty' => 1,
              'stats' => 
              array (
                'voting_updown' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_text',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'voting_updown_down' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'voting_bookmark' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'wp_comments' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_icon' => 'fas fa-comment',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
              ),
              'statistics' => 
              array (
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
              'css_class' => 'review-stats',
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
          'weight' => 9,
          'system' => false,
        ),
        212 => 
        array (
          'id' => 212,
          'name' => 'group',
          'data' => 
          array (
            'settings' => 
            array (
              'inline' => 1,
              'separator' => ' · ',
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
              'css_class' => 'review-author-container',
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
          'weight' => 3,
          'system' => false,
        ),
        213 => 
        array (
          'id' => 213,
          'name' => 'entity_field_post_author',
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
                'entity_author' => 
                array (
                  'format' => 'link_thumb_s',
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_author',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-author',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 212,
          'weight' => 4,
          'system' => false,
        ),
        214 => 
        array (
          'id' => 214,
          'name' => 'entity_field_post_published',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'icon',
              'label_custom' => '',
              'label_icon' => 'fas fa-calendar-alt',
              'label_icon_size' => '',
              'label_as_heading' => false,
              'renderer_settings' => 
              array (
                'entity_published' => 
                array (
                  'format' => 'date',
                  'permalink' => false,
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_published',
            ),
            'heading' => NULL,
            'advanced' => 
            array (
              'css_class' => 'review-date',
            ),
            'visibility' => 
            array (
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 212,
          'weight' => 5,
          'system' => false,
        ),
        215 => 
        array (
          'id' => 215,
          'name' => 'entity_parent_field_post_title',
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
                    'size' => 'sm',
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
            'advanced' => NULL,
            'visibility' => 
            array (
              'hide_on_parent' => 1,
              'wp_check_role' => false,
              'wp_roles' => 
              array (
              ),
            ),
          ),
          'parent_id' => 212,
          'weight' => 6,
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
      ),
      'elements' => 
      array (
        216 => 
        array (
          'id' => 216,
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
              'label_custom' => __('Title', 'directories-reviews'),
              'label_icon' => '',
              'label_icon_size' => '',
            ),
            'design' => NULL,
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
        217 => 
        array (
          'id' => 217,
          'name' => 'entity_field_review_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => 1,
              'renderer_settings' => 
              array (
                'review_rating' => 
                array (
                  'format' => 'stars',
                  'color' => 
                  array (
                    'type' => '',
                    'value' => '',
                  ),
                  'decimals' => '1',
                  'inline' => false,
                  'bar_height' => 12,
                ),
              ),
              'renderer' => 'review_rating',
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
          'parent_id' => 216,
          'weight' => 2,
          'system' => false,
        ),
        218 => 
        array (
          'id' => 218,
          'name' => 'entity_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'none',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => 1,
              'renderer_settings' => 
              array (
                'entity_title' => 
                array (
                  'link' => 'post',
                  'link_target' => '_self',
                  'max_chars' => 0,
                  'icon' => false,
                  'icon_settings' => 
                  array (
                    'size' => 'sm',
                    'field' => 'review_photos',
                    'fallback' => false,
                    'color' => 
                    array (
                      'type' => '',
                      'custom' => '',
                    ),
                    'is_image' => true,
                  ),
                  '_separator' => '',
                ),
              ),
              'renderer' => 'entity_title',
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
          'parent_id' => 216,
          'weight' => 3,
          'system' => false,
        ),
        219 => 
        array (
          'id' => 219,
          'name' => 'statistics',
          'data' => 
          array (
            'settings' => 
            array (
              'arrangement' => 
              array (
                0 => 'voting_updown',
                1 => 'voting_updown_down',
                2 => 'wp_comments',
              ),
              'separator' => ' · ',
              'hide_empty' => 1,
              'stats' => 
              array (
                'voting_updown' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_color' => 'success',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'voting_updown_down' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_color' => 'danger',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'voting_bookmark' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_color' => '',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
                'wp_comments' => 
                array (
                  'settings' => 
                  array (
                    '_format' => 'icon_num',
                    '_icon' => 'fas fa-comment',
                    '_color' => '',
                    '_link' => false,
                    '_link_path' => '',
                    '_link_fragment' => '',
                  ),
                ),
              ),
              'statistics' => 
              array (
              ),
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
          'parent_id' => 216,
          'weight' => 4,
          'system' => false,
        ),
        220 => 
        array (
          'id' => 220,
          'name' => 'entity_parent_field_post_title',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Listing', 'directories-reviews'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => 1,
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
          'weight' => 5,
          'system' => false,
        ),
        221 => 
        array (
          'id' => 221,
          'name' => 'entity_field_post_published',
          'data' => 
          array (
            'settings' => 
            array (
              'label' => 'custom',
              'label_custom' => __('Date', 'directories-reviews'),
              'label_icon' => '',
              'label_icon_size' => '',
              'label_as_heading' => 1,
              'renderer_settings' => 
              array (
                'entity_published' => 
                array (
                  'format' => 'date',
                  'permalink' => false,
                ),
              ),
              'renderer' => 'entity_published',
            ),
            'heading' => NULL,
            'design' => NULL,
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
        222 => 
        array (
          'id' => 222,
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
      ),
    ),
  ),
  'filters' => 
  array (
    'default' => 
    array (
      'name' => 'default',
      'type' => 'filters',
      'data' => NULL,
      'elements' => 
      array (
        223 => 
        array (
          'id' => 223,
          'name' => 'view_filter_post_content',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'post_content',
              'label' => 'custom',
              'label_custom' => __('Search reviews', 'directories-reviews'),
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'keyword',
              'field_name' => 'post_content',
              'filter_name' => 'filter_post_content',
              'filter_settings' => 
              array (
                'keyword' => 
                array (
                  'min_length' => 3,
                  'match' => 'all',
                  'placeholder' => '',
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
        224 => 
        array (
          'id' => 224,
          'name' => 'view_filter_review_rating',
          'data' => 
          array (
            'settings' => 
            array (
              'name' => 'review_rating',
              'label' => 'form',
              'label_custom' => '',
              'label_icon' => NULL,
              'label_icon_size' => '',
              'label_as_heading' => false,
              'filter' => 'review_rating',
              'field_name' => 'review_rating',
              'filter_name' => 'filter_review_rating',
              'filter_settings' => 
              array (
                'review_rating' => 
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
          'parent_id' => 0,
          'weight' => 2,
          'system' => false,
        ),
      ),
    ),
  ),
);