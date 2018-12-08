<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Set additional settings in gmw WP_Query cache.
add_filter( 'gmw_gmapsul_search_query_args', 'gmw_ps_ul_set_gmw_args', 10, 2 );
add_filter( 'gmw_ajaxfmsul_search_query_args', 'gmw_ps_ul_set_gmw_args', 10, 2 );

// Set custom map icons via loop.
add_filter( 'gmw_ajaxfmsul_loop_object_map_icon', 'gmw_ps_ul_get_map_icon_via_loop', 15, 3 );

/**
 * Query users keywords.
 *
 * @param  [type] $clauses [description]
 * @param  [type] $gmw     [description]
 * @return [type]          [description]
 *
 * @since 2.0
 */
function gmw_ps_gmapsul_filter_keywords( $clauses, $gmw ) {

	// verify that keywords exists.
	if ( empty( $gmw['form_values']['keywords'] ) ) {
		return $clauses;
	}

	global $wpdb;

	// get keywords value from URL
	$keywords = $gmw['form_values']['keywords'];

	// support for Wordpress lower then V4.0
	$like = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( trim( $keywords ) ) : like_escape( trim( $keywords ) );
	$like = esc_sql( $like );
	$like = '%' . $like . '%';

	//search title
	$clauses->query_where .= " AND ( {$wpdb->users}.user_login LIKE '{$like}' OR {$wpdb->users}.user_nicename LIKE '{$like}' OR {$wpdb->users}.display_name LIKE '{$like}' ) ";

	return $clauses;
}
add_filter( 'gmw_gmapsul_users_query_clauses', 'gmw_ps_gmapsul_filter_keywords', 50, 2 );
add_filter( 'gmw_ajaxfmsul_users_query_clauses', 'gmw_ps_gmapsul_filter_keywords', 50, 2 );

/**
 * Set custom users map icons.
 *
 * This function set icons for "global" and "per member".
 *
 * everything is done during WP_Query.
 *
 * @param  [type] $clauses [description]
 * @param  [type] $gmw     [description]
 * @return [type]          [description]
 *
 * @since 2.0
 *
 */
function gmw_ps_gmapsul_set_map_icons_via_query( $clauses, $gmw ) {

	if ( false == ( $usage = $gmw['map_markers']['usage'] ) ) {
		$usage = 'global';
	}

	// abort if not the right usage
	if ( ! in_array( $usage, array( 'global', 'per_user' ) ) ) {
		return $clauses;
	}

	global $wpdb;

	// get icons url
	$icons     = gmw_get_icons();
	$icons_url = $icons['ul_map_icons']['url'];

	// get default marker. If no icon provided or using the _default.png,
	// we than pass blank value, to use Google's default red marker.
	if ( ! empty( $gmw['map_markers']['default_marker'] ) && $gmw['map_markers']['default_marker'] != '_default.png' ) {
		$default_icon = $icons_url.$gmw['map_markers']['default_marker'];
	} else {
		$default_icon = '';
	}

	// if global icon.
	if ( 'global' === $usage ) {

		$clauses->query_fields .= $wpdb->prepare( ', %s as map_icon', $default_icon );

		return $clauses;
	}

	// if per post, get the icon from locations table
	if ( 'per_user' === $usage ) {

		$clauses->query_fields .= $wpdb->prepare( ", IF ( gmw_locations.map_icon IS NOT NULL AND gmw_locations.map_icon != '_default.png', CONCAT( %s, gmw_locations.map_icon ), %s ) as map_icon", $icons_url, $default_icon );

		return $clauses;
	}

	return $clauses;
}
add_filter( 'gmw_gmapsul_users_query_clauses', 'gmw_ps_gmapsul_set_map_icons_via_query', 12, 2 );
add_filter( 'gmw_ajaxfmsul_users_query_clauses', 'gmw_ps_gmapsul_set_map_icons_via_query', 12, 2 );

/**
 * Generate custom map icons via members loop.
 *
 * For member avatar map icons.
 *
 * @since 2.0
 *
 * @param  [type] $map_icon [description]
 * @param  [type] $post     [description]
 * @param  [type] $gmw      [description]
 * @return [type]           [description]
 */
function gmw_ps_gmapsul_set_map_icons_via_loop( $query, $gmw ) {

	// abort if not set to featured image or categories
	if ( 'avatar' !== $gmw['map_markers']['usage'] || empty( $query->results ) ) {
		return $query;
	}

	$temp  = array();
	$users = $query->results;

	foreach ( $users as $user ) {

		$user->map_icon = gmw_ps_ul_get_map_icon_via_loop( '', $user, $gmw );

		$temp[] = $user;
	}

	$query->results = $temp;

	return $query;
}
add_filter( 'gmw_gmapsul_cached_users_query', 'gmw_ps_gmapsul_set_map_icons_via_loop', 10, 3 );
add_filter( 'gmw_gmapsul_users_query', 'gmw_ps_gmapsul_set_map_icons_via_loop', 10, 2 );
