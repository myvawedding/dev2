<?php
return array (
  'default' => 
  array (
    'mode' => 'masonry',
    'label' => __('Default', 'directories-reviews'),
    'settings' => 
    array (
      'masonry_cols' => 'responsive',
      'masonry_cols_responsive' => 
      array (
        'xs' => '2',
        'sm' => 'inherit',
        'md' => 'inherit',
        'lg' => 'inherit',
        'xl' => '3',
      ),
      'sort' => 
      array (
        'options' => 
        array (
          0 => 'post_published',
          1 => 'post_published,asc',
          2 => 'voting_updown',
        ),
        'default' => 'voting_updown',
      ),
      'pagination' => 
      array (
        'no_pagination' => false,
        'perpage' => 18,
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
        ),
        'limit' => 0,
      ),
      'filter' => 
      array (
        'show' => 1,
      ),
      'other' => 
      array (
        'num' => 1,
      ),
      'directory_name' => 'directory',
      'bundle_name' => 'directory_rev_rev',
      'view_id' => '7',
    ),
    'default' => true,
  ),
);