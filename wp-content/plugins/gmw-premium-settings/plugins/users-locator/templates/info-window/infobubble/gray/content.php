<?php 
/**
 * Infobubble "gray" info-window template file . 
 * 
 * The content of this file will be displayed in the map markers info-window.
 *
 * You can modify this file to apply custom changes. However, it is not recomended
 * to make the changes directly in this file,
 * because your changes will be overwritten with the next update of the plugin.
 * 
 * Instead, you can copy or move this template ( the folder contains this file 
 * and the "css" folder ) into the theme's or child theme's folder of your site, 
 * and apply your changes from there. 
 * 
 * The custom template folder will need to be placed under:
 * your-theme's-or-child-theme's-folder/geo-my-wp/users-locator/info-window/infobubble/
 * 
 * Once the template folder is in the theme's folder, you will be able to select 
 * it in the form editor.
 *
 * $gmw  - the form being used ( array )
 * $user - the user being displayed ( object )
 */
?>
<div class="gmw-info-window-inner infobubble">

	<?php do_action( 'gmw_info_window_start', $user, $gmw ); ?>
	
	<?php gmw_info_window_user_avatar( $user, $gmw ); ?>	
	
	<?php do_action( 'gmw_info_window_before_title', $user, $gmw ); ?>
	
	<a class="title user-name" href="<?php echo esc_url( gmw_search_results_user_permalink( $user, $gmw ) ); ?>">
		<?php echo esc_attr( $user->display_name ); ?>
	</a>

	<?php do_action( 'gmw_info_window_before_address', $user, $gmw ); ?>

	<?php gmw_info_window_address( $user, $gmw ); ?>
    
    <?php gmw_info_window_directions_link( $user, $gmw ); ?>
    
    <?php gmw_info_window_distance( $user, $gmw ); ?>
    		
    <?php do_action( 'gmw_info_window_before_location_meta', $user, $gmw ); ?>

    <?php gmw_info_window_location_meta( $user, $gmw, false ); ?>
	
    <?php do_action( 'gmw_info_window_end', $user, $gmw ); ?>
	
</div>  