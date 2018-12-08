<?php
return array (
  'default' => 
  array (
    'mode' => 'masonry',
    'label' => __('Default', 'directories-pro'),
    'settings' => 
    array (
      'sort' => 
      array (
        'options' => 
        array (
          0 => 'term_title',
        ),
        'default' => 'term_title',
      ),
      'pagination' => 
      array (
        'no_pagination' => 1,
        'perpage' => 20,
        'allow_perpage' => false,
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
          'location_photo' => '1',
        ),
        'limit' => 0,
      ),
      'other' => 
      array (
        'num' => false,
      ),
      'directory_name' => 'directory',
      'bundle_name' => 'directory_loc_loc',
      'view_id' => '8',
    ),
    'default' => true,
  ),
);