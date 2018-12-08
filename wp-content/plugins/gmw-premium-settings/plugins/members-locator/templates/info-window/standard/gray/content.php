<?php 
/**
 * Standard "gray" info-window template file . 
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
 * your-theme's-or-child-theme's-folder/geo-my-wp/members-locator/info-window-templates/standard/
 * 
 * Once the template folder is in the theme's folder, you will be able to select 
 * it in the form editor.
 *
 * $gmw  - the form being used ( array )
 * $member - the member being displayed ( object )
 */
?>
<div class="gmw-info-window-inner standard">

	<?php do_action( 'gmw_info_window_start', $member, $gmw ); ?>
	
	<?php gmw_info_window_bp_avatar( $member, $gmw ); ?>	
	
	<?php do_action( 'gmw_info_window_before_title', $member, $gmw ); ?>
	
	<a class="title" href="<?php bp_member_permalink(); ?>">
		<?php bp_member_name(); ?>
	</a>
	
	<span class="last-active">
		<?php bp_member_last_active(); ?>		
	</span>

	<?php do_action( 'gmw_info_window_before_address', $member, $gmw ); ?>

	<?php gmw_info_window_address( $member, $gmw ); ?>
    
    <?php gmw_info_window_directions_link( $member, $gmw ); ?>
    
    <?php gmw_info_window_distance( $member, $gmw ); ?>
    	
    <?php do_action( 'gmw_info_window_before_xprofile_fields', $member, $gmw ); ?>
    
    <?php gmw_info_window_member_xprofile_fields( $member, $gmw ); ?>
	
    <?php do_action( 'gmw_info_window_before_location_meta', $member, $gmw ); ?>

    <?php gmw_info_window_location_meta( $member, $gmw, false ); ?>
	
    <?php do_action( 'gmw_info_window_end', $member, $gmw ); ?>
	
</div>  