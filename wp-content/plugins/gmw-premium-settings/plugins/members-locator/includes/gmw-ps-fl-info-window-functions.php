<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Info window AJAX loader
 */
if ( bp_has_members( array( 'include' => array( $location->object_id ), 'type' => 'alphabetical' ) ) ) {

	while ( bp_members() ) : bp_the_member();
	
	global $members_template;
	
	// get additional user location data
	$location_data = gmw_get_user_location( $location->object_id );
		
	$fields = array( 
		'lat',
		'lng',
		'latitude',
		'longitude',
		'street', 
		'premise', 
		'city', 
		'region_name', 
		'postcode', 
		'country_code', 
		'address', 
		'formatted_address' 
	);

	// append location to the member object
	foreach ( $fields as $field ) {
		
		if ( isset( $location_data->$field ) ) {
			$members_template->member->$field = $location_data->$field;
		}
	}

	// get location meta if needed and append it to the member.
	if ( ! empty( $gmw['info_window']['location_meta'] ) ) {
	    $members_template->member->location_meta = gmw_get_location_meta( $location->location_id, $gmw['info_window']['location_meta'] );
	}  

	// append distance + units to member
	$members_template->member->distance = $location->distance;
	$members_template->member->units    = $location->units;

	//modify member object		
	$members_template->member = $member = apply_filters( 'gmw_ps_member_before_info_window', $members_template->member, $gmw );
		
	$iw_type  = ! empty( $gmw['info_window']['iw_type'] ) ? $gmw['info_window']['iw_type'] : 'infobubble';
	$template = $gmw['info_window']['template'][$iw_type];

	// include template
	$template_data = gmw_get_info_window_template( 'members_locator', $iw_type, $template, 'premium_settings' );

	include( $template_data['content_path'] );
	
	do_action( 'gmw_ps_after_member_info_window', $member, $gmw );

	endwhile;
}