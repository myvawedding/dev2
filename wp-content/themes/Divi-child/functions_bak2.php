<?php
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
add_post_type_support('vendor_direc_dir_ltg', 'thumbnail');
function theme_enqueue_styles() {
wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
wp_enqueue_style( 'child-style', get_stylesheet_uri(), array( 'parent-style' ) );

}
