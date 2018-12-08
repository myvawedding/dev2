<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Premium Settings Helper class
 *
 */
class GMW_PS_Template_Functions_Helper {

	/**
	 * Radius slider
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public static function get_radius_slider( $args = array() ) {

		$defaults = array(
			'id'            => 0,
			'default_value' => '50',
			'max_value'     => '200',
			'min_value'     => '0',
			'label'         => 'Miles',
			'steps'         => '1',
		);

		$args   = wp_parse_args( $args, $defaults );
		$args   = apply_filters( 'gmw_search_forms_range_slider_args', $args );
		$url_px = gmw_get_url_prefix();

		$id            = absint( $args['id'] );
		$default_value = ( '' !== $args['default_value'] ) ? $args['default_value'] : '50';
		$default_value = isset( $_GET[ $url_px . 'distance' ] ) ? esc_attr( $_GET[ $url_px . 'distance' ] ) : esc_attr( $default_value );
		$max_value     = ( '' !== $args['max_value'] ) ? $args['max_value'] : '200';
		$min_value     = ( '' !== $args['min_value'] ) ? $args['min_value'] : '0';

		$output  = '<input type="range" class="gmw-range-slider" min="' . esc_attr( $min_value ) . '" max="' . esc_attr( $max_value ) . '" step="' . esc_attr( $args['steps'] ) . '" value="' . $default_value . '" name="' . $url_px . 'distance" />';
		$output .= '<span class="gmw-radius-range-output"><output>' . $default_value . '</output> ' . esc_html( $args['label'] ) . '</span>';

		return $output;
	}
}
