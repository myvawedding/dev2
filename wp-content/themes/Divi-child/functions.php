<?php
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
add_post_type_support('vendor_direc_dir_ltg', 'thumbnail');
function theme_enqueue_styles() {
wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
wp_enqueue_style( 'child-style', get_stylesheet_uri(), array( 'parent-style' ) );

}
// add link to image.
function gmw_custom_add_link_to_featured_image( $html, $post_id ) {
	return '<a href="' . get_the_permalink( $post_id ) . '">'.$html.'</a>';
}

// enable linking on GEO my WP forms only.
function gmw_custom_enable_link_form_featured_image(){
	add_filter( 'post_thumbnail_html', 'gmw_custom_add_link_to_featured_image', 30, 2 );
}
add_action( 'gmw_shortcode_start', 'gmw_custom_enable_link_form_featured_image' );

// disable linking when done.
function gmw_custom_disable_link_form_featured_image(){
	remove_filter( 'post_thumbnail_html', 'gmw_custom_add_link_to_featured_image', 30, 2 );
}
add_action( 'gmw_shortcode_end', 'gmw_custom_disable_link_form_featured_image' );
