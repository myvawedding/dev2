<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
	
/**
 * Display xprofile fields in search results
 * 
 * @param  [type] $member [description]
 * @param  array  $gmw    [description]
 * @return [type]         [description]
 */
function gmw_search_results_member_xprofile_fields( $member, $gmw = array() ) {

	// Look for profile fields in form settings
	$total_fields = ! empty( $gmw['search_results']['xprofile_fields']['fields'] ) ? $gmw['search_results']['xprofile_fields']['fields'] : array();

	// look for date profile field in form settings
	if ( '' != $gmw['search_results']['xprofile_fields']['date_field'] ) {
		array_unshift( $total_fields, $gmw['search_results']['xprofile_fields']['date_field'] );
	}
	
	// abort if no profile fields were chosen
	if ( empty( $total_fields ) ) {
		return;
	}

	echo gmw_get_member_xprofile_fields( $member->ID, $total_fields );
}	

/**
 * Append xprofile fields to results voa action hook
 * 
 * @param  [type] $gmw    [description]
 * @param  [type] $member [description]
 * @return [type]         [description]
 */
function gmw_append_xprofile_fields_to_results( $gmw, $member ) {
	gmw_search_results_member_xprofile_fields( $member, $gmw );
}
add_action( 'gmw_fl_search_results_member_items', 'gmw_append_xprofile_fields_to_results', 20, 2 );

/**
 * Query Group Types for members locator forms only
 * 
 * @param  [type] $gmw [description]
 * @return [type]      [description]
 */
function gmw_ps_fl_bp_member_types_query( $query_args, $gmw ) {
 
	$output = gmw_ps_bp_get_object_types_query( $gmw, 'member', 'bpmt' );
	
	if ( false != $output['usage'] ) {
		$query_args[$output['usage']] = $output['types'];
	}

    return $query_args;
}
add_filter( 'gmw_fl_search_query_args', 'gmw_ps_fl_bp_member_types_query', 15, 2 );
