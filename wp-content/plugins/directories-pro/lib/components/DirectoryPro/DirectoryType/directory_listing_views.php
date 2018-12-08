<?php
return array (
  'default' =>
  array (
    'mode' => 'list',
    'label' => __('Default', 'directories-pro'),
    'settings' =>
    array (
      'list_grid' => 1,
      'list_no_row' => false,
      'list_grid_default' => false,
      'list_grid_cols' =>
      array (
        'num' => 'responsive',
        'num_responsive' =>
        array (
          'xs' => '1',
          'sm' => '2',
          'md' => 'inherit',
          'lg' => '3',
          'xl' => 'inherit',
        ),
      ),
      'list_grid_gutter_width' => '',
      'map' =>
      array (
        'show' => 1,
        'position' => 'side',
        'span' => 4,
        'height' => 400,
        'style' => '',
        'scroll_to_item' => 1,
        'sticky' => 1,
        'fullscreen' => 1,
        'fullscreen_span' => 5,
        'infobox' => 1,
        'infobox_width' => 240,
        'trigger_infobox' => false,
      ),
      'sort' =>
      array (
        'options' =>
        array (
          0 => 'post_published',
          1 => 'post_published,asc',
          2 => 'post_title',
          3 => 'post_title,desc',
          4 => 'location_address',
          5 => 'voting_rating',
          6 => 'random',
          7 => 'entity_child_count,review_review',
          8 => 'review_ratings',
          9 => 'voting_bookmark',
        ),
        'default' => 'post_published',
        'stick_featured' => false,
      ),
      'pagination' =>
      array (
        'no_pagination' => false,
        'perpage' => 20,
        'allow_perpage' => 1,
        'perpages' =>
        array (
          0 => 10,
          1 => 20,
          2 => 50,
        ),
      ),
      'query' =>
      array (
        'fields' =>
        array (
        ),
        'limit' => 0,
      ),
      'filter' =>
      array (
        'show' => false,
        'show_modal' => false,
      ),
      'other' =>
      array (
        'num' => 1,
        'add' => ['show' => false],
      ),
      'directory_name' => 'directory',
      'bundle_name' => 'directory_dir_ltg',
      'view_id' => '6',
    ),
    'default' => true,
  ),
);
