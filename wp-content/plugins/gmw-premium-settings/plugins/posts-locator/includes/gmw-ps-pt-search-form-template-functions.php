<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomies checkboxes
 * 
 * @param  array  $tax_output [description]
 * @param  [type] $taxonomy   [description]
 * @param  [type] $values     [description]
 * @param  [type] $args       [description]
 * @param  [type] $tag        [description]
 * @return [type]             [description]
 */
function gmw_ps_search_form_checkbox_taxonomies( $output, $args, $taxonomy ) {
 	
    $output = '<ul class="gmw-checkbox-level-top '.esc_attr( $taxonomy->name ).'">';

    $terms = gmw_get_terms( $taxonomy->name, $args );

    //new walker
    $walker = new GMW_Post_Category_Walker;

    //run the category walker
    $output .= $walker->walk( $terms, $args['depth'], $args );

    $output .= '</ul>';

    return $output;
}
add_filter( 'gmw_generate_checkbox_taxonomy', 'gmw_ps_search_form_checkbox_taxonomies', 5, 3 );

/**
 * Taxonomies smartbox
 * 
 * @param  array  $tax_output [description]
 * @param  [type] $taxonomy   [description]
 * @param  [type] $values     [description]
 * @param  [type] $args       [description]
 * @param  [type] $tag        [description]
 * @return [type]             [description]
 */
function gmw_ps_search_form_smartbox_taxonomies( $output, $args, $taxonomy ) {
 	
    $tax_name = esc_attr( $taxonomy->name );

    $multiple = $args['usage'] == 'smartbox_multiple' ? 'multiple ' : '';

    $smartbox_data = $multiple . 'data-placeholder="'.esc_attr( $args['placeholder'] ).'" data-no_results_text="'.esc_attr( $args['no_results_text'] ).'"';
    
    $output = "<select name=\"tax[{$tax_name}][]\" id=\"{$tax_name}-taxonomy-{$args['gmw_form_id']}\" class=\"gmw-form-field gmw-taxonomy {$tax_name} gmw-smartbox\" {$smartbox_data}>";
  
    if ( $multiple == '' && ! empty( $args['show_option_all'] ) ) {
        $output .= '<option value="0" selected="selected">'.esc_attr( $args['show_option_all'] ).'</option>';
    }

    $terms = gmw_get_terms( $taxonomy->name, $args );

    //new walker
    $walker = new GMW_Post_Category_Walker;

    //run the category walker
    $output .= $walker->walk( $terms, $args['depth'], $args );

    $output .= '</select>';
    
    // enqueue smartbox
   	gmw_ps_enqueue_smartbox();
  
    return $output;
}
add_filter( 'gmw_generate_smartbox_taxonomy', 'gmw_ps_search_form_smartbox_taxonomies', 10, 5 );
add_filter( 'gmw_generate_smartbox_multiple_taxonomy', 'gmw_ps_search_form_smartbox_taxonomies', 10, 5 );

/**
 * Get single search form custom field element
 * 
 * @param  array  $args [description]
 * @return [type]       [description]
 */
function gmw_get_search_form_custom_field( $args = array(), $gmw = array() ) {

	$defaults = array(
		'id'	      => 0,
		'default'     => '',
		'label'	      => '',
		'placeholder' => '',
		'name' 	      => '',
		'type'	      => 'CHAR',
		'compare'     => '=',
		'date_type'   => '',
		'double'	  => 0
	);

	$args   = wp_parse_args( $args, $defaults );
	$args   = apply_filters( 'gmw_search_forms_units_args', $args, $gmw );
	$url_px = gmw_get_url_prefix();

	$id          = absint( $args['id'] );
	$fname 		 = esc_attr( str_replace( ' ', '_', $args['name'] ) );
	$label 		 = is_array( $args['label'] ) ? $args['label'] : explode( ',', $args['label'] );
	$ftype 		 = esc_attr( $args['type'] );
	$placeholder = is_array( $args['placeholder'] ) ? $args['placeholder'] : explode( ',', $args['placeholder'] );
	
	$picker_enabled = false;

	// if date field
	if ( $ftype == "DATE" ) {
		
		$picker_enabled = true; 
		$picker_class   = ' gmw-date-field'; 
		$picker_type    = 'data-date_type="'.esc_attr( $args['date_type'] ).'"';
	
	} else if ( $ftype == "TIME" ) {
		
		$picker_enabled = true;
		$picker_class   = ' gmw-time-field'; 
		$picker_type    = '';

	} else {

		$datetime = '';
		$picker_class = '';
		$picker_type  = '';
	}

	$label[0]       = isset( $label[0] ) ? esc_attr( $label[0] ) : '';
	$placeholder[0] = isset( $placeholder[0] ) ? 'placeholder="'.esc_attr( $placeholder[0] ).'"' : '';

	$output = '';

	// if compare is between we create 2 fields
	if ( $args['double'] ) {

		$default_1 = $default_2 = '';

		if ( is_array( $args['default'] ) ) {
			$default_1 = $args['default'][0];
			$default_2 = $args['default'][1];
		} else {
			$default_1 = $default_2 = $args['default'];
		}

		// get values
		$value_1 = isset( $_GET['cf'][$fname][0] ) ? $_GET['cf'][$fname][0] : $default_1;
		$value_2 = isset( $_GET['cf'][$fname][1] ) ? $_GET['cf'][$fname][1] : $default_2;

		$value_1 = esc_attr( stripslashes( $value_1 ) );
		$value_2 = esc_attr( stripslashes( $value_2 ) );

 		$label[1] = isset( $label[1] ) ? esc_attr( $label[1] ) : '';
		$placeholder[1] = isset( $placeholder[1] ) ? 'placeholder="'. $placeholder[1] .'"' : '';

		$output .= '<div class="custom-field-inner first">';

			// generate label
			if ( $label[0] != '' ) {
				$output .= "<label class='gmw-field-label' for='gmw-cf-{$fname}-{$id}'>{$label[0]}</label>";
			}
			$output .= "<input id='gmw-cf-{$fname}-{$id}' class='gmw-form-field gmw-custom-field {$fname}{$picker_class}' type='text' name='cf[{$fname}][]' value='{$value_1}' {$placeholder[0]} {$picker_type}/>";
		$output .= '</div>';

		$output .= '<div class="custom-field-inner last">';

			if ( $label[1] != '' ) {
				$output .= "<label class='gmw-field-label' for='gmw-cf-{$fname}-second-{$id}'>{$label[1]}</label>";
			}
			$output .= "<input id='gmw-cf-{$fname}-second-{$id}' class='gmw-form-field gmw-custom-field {$fname}{$picker_class}' type='text' name='cf[{$fname}][]' value='{$value_2}' {$placeholder[1]} {$picker_type}/>";

		$output .= '</div>';

	} else {

		$value   = isset( $_GET['cf'][$fname] ) ? $_GET['cf'][$fname] : $args['default'];
		$value   = esc_attr( sanitize_text_field( $value ) );

		if ( $label[0] != '' ) {
			$output .= "<label class='gmw-field-label' for='gmw-cf-{$fname}-{$id}'>{$label[0]}</label>";
		}
		$output .= "<input id='gmw-cf-{$fname}-{$id}' class='gmw-form-field gmw-custom-field {$fname}{$picker_class}' type='text' name='cf[{$fname}]' value='{$value}' {$placeholder[0]} {$picker_type}/>";
	}

	// enqueue date picker styles and scripts
	if ( $picker_enabled && ! wp_script_is( 'datetime-picker', 'enqueued' ) ) {
		wp_enqueue_script( 'datetime-picker' );
		wp_enqueue_style( 'datetime-picker' );
	}

	return $output;
}

/**
 * Get all custom fields to display in search form
 * 
 * @version 1.0
 * 
 * @author Eyal Fitoussi
 */
function gmw_get_search_form_custom_fields( $gmw = array() ) {

	if ( empty( $gmw['search_form']['custom_fields'] ) ) {
		return;
	}

	$picker_enabled = false;
	$output = '';

	foreach ( $gmw['search_form']['custom_fields'] as $value ) {

		$fdouble = 0;
		$fdouble_class = '';

		if ( $value['compare'] == 'BETWEEN' || $value['compare'] == 'NOT BETWEEN' ) {
			$fdouble = true;
			$fdouble_class = ' double';
		}

		$args = array(
			'id'	      => $gmw['ID'], 
			'name' 	      => $value['name'],
			'type'	      => $value['type'],
			'date_type'   => $value['date_type'],
			'compare'     => $value['compare'],
			'label'	      => isset( $value['label'] ) ? $value['label'] : '',
			'placeholder' => isset( $value['placeholder'] ) ? $value['placeholder'] : '',
			'double'	  => $fdouble
		);

		$output .= '<div class="gmw-form-field-wrapper custom-field '.esc_attr( $value['name'] ).' '.esc_attr( $value['type'] ).$fdouble_class.'">';
		$output .= gmw_get_search_form_custom_field( $args, $gmw );
		$output .= '</div>';
	} 

	return $output;
}
	
	/**
	 * Append custom fields to the search form via action hook
	 * 
	 * @param  [type] $gmw [description]
	 * @return [type]      [description]
	 */
	function gmw_search_form_custom_fields( $gmw ) {
		echo gmw_get_search_form_custom_fields( $gmw );
	}
	add_action( 'gmw_search_form_before_distance', 'gmw_search_form_custom_fields', 10, 1 );
