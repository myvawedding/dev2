<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Info window AJAX loader
 */
$user = gmw_get_user_location_data( $location->object_id );

// get location meta if needed and append it to the member.
if ( ! empty( $gmw['info_window']['location_meta'] ) ) {
	$user->location_meta = gmw_get_location_meta( $location->location_id, $gmw['info_window']['location_meta'] );
}

$user->distance = $user_location->distance;
$user->units    = $user_location->units;

//modify form and member.
$user = apply_filters( 'gmw_ps_user_before_info_window', $user, $gmw );

$iw_type  = ! empty( $gmw['info_window']['iw_type'] ) ? $gmw['info_window']['iw_type'] : 'infobubble';
$template = $gmw['info_window']['template'][ $iw_type ];

// include template
$template_data = gmw_get_info_window_template( 'users_locator', $iw_type, $template, 'premium_settings' );

include( $template_data['content_path'] );

do_action( 'gmw_ps_after_user_info_window', $user, $gmw );
