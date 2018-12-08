<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Modify the users query
 *
 * @param  [type] $gmw        [description]
 * @return [type]             [description]
 */
function gmw_ps_filter_users_query( $query_args, $gmw ) {

	/****** keywords ******/
	if ( isset( $gmw['form_values']['keywords'] ) && '' !== $gmw['form_values']['keywords'] ) {
		// adding * to performe wild search
		$query_args['search']         = '*' . $gmw['form_values']['keywords'] . '*';
		$query_args['search_columns'] = array( 'user_login', 'user_nicename', 'display_name' );
	}

	/****** orderby ******/
	$orderby = gmw_ps_get_orderby_value( $gmw );

	// abort if no value found
	if ( '' !== $orderby && 'distance' !== $orderby ) {
		$query_args['orderby'] = $orderby;
	}

	return $query_args;
}
add_filter( 'gmw_ul_search_query_args', 'gmw_ps_filter_users_query', 50, 2 );

/**
 * Set additional arguments in gmw WP_Query cache.
 *
 * @param  [type] $gmw [description]
 * @return [type]      [description]
 *
 * @since 2.0
 *
 */
function gmw_ps_ul_set_gmw_args( $query_args, $gmw ) {

	/**
	 * Set map icons settings in gmw query cache.
	 *
	 * The icons are being generated in the WP_Query,
	 *
	 * we need to make sure the query cache udpates when icons changes.
	 *
	 * @param  [type] $gmw [description]
	 * @return [type]      [description]
	 */
	$query_args['gmw_args']['map_icons']['usage']          = $gmw['map_markers']['usage'];
	$query_args['gmw_args']['map_icons']['default_marker'] = $gmw['map_markers']['default_marker'];

	return $query_args;
}

/**
 * Modify user map icon
 *
 * @param  [type] $members_template [description]
 * @param  [type] $gmw              [description]
 * @param  [type] $settings         [description]
 * @return [type]                   [description]
 */
function gmw_ps_modify_user_map_icon( $args, $user, $gmw ) {

	$map_icon = '_default.png';

	//set default map icon usage if not exist
	if ( ! isset( $gmw['map_markers']['usage'] ) ) {
		$gmw['map_markers']['usage'] = 'global';
	}

	$usage       = isset( $gmw['map_markers']['usage'] ) ? $gmw['map_markers']['usage'] : 'global';
	$global_icon = isset( $gmw['map_markers']['default_marker'] ) ? $gmw['map_markers']['default_marker'] : '_default.png';

	//if same global map icon
	if ( 'global' === $usage ) {

		$map_icon = $gmw['map_markers']['default_marker'];

		//per member map icon
	} elseif ( 'per_user' === $usage && isset( $user->map_icon ) ) {

		$map_icon = $user->map_icon;

		//avatar map icon
	} elseif ( 'avatar' === $usage ) {

		$map_icon = array(
			'url'        => get_avatar_url( $user->ID, array(
				'height' => 30,
				'width'  => 30,
			) ),
			'scaledSize' => array(
				'height' => 30,
				'width'  => 30,
			),
		);

		//oterwise, default map icon
	} else {
		$map_icon = '_default.png';
	}

	//generate the map icon
	if ( 'avatar' !== $usage ) {

		if ( '_default.png' === $map_icon ) {
			$map_icon = 'https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=' . $user->location_count . '|FF776B|000000';
		} else {
			$icons    = gmw_get_icons();
			$map_icon = $icons['ul_map_icons']['url'] . $map_icon;
		}
	}

	$args['map_icon'] = $map_icon;

	return $args;
}
add_action( 'gmw_ul_form_map_location_args', 'gmw_ps_modify_user_map_icon', 20, 3 );

/**
 * Generate custom map icons via groups loop.
 *
 * For group avatar map icons.
 *
 * @since 2.0
 *
 * @param  [type] $map_icon [description]
 * @param  [type] $group    [description]
 * @param  [type] $gmw      [description]
 * @return [type]           [description]
 */
function gmw_ps_ul_get_map_icon_via_loop( $map_icon, $user, $gmw ) {

	// abort if not set to featured image or categories
	if ( isset( $gmw['map_markers']['usage'] ) && 'avatar' === $gmw['map_markers']['usage'] ) {

		$user_id = isset( $user->ID ) ? $user->ID : $user->object_id;

		$map_icon = get_avatar_url(
			$user_id,
			array(
				'height' => 30,
				'width'  => 30,
			)
		);

		if ( empty( $map_icon ) ) {
			$map_icon = GMW_PS_URL . '/assets/map-icons/_no_image.png';
		}
	}

	return $map_icon;
}
