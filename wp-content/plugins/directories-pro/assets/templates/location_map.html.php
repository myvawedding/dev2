<div class="drts-location-entities-map-container"<?php if (!empty($settings['map']['fullscreen_offset'])):?> data-fullscreen-offset="<?php echo $this->H($settings['map']['fullscreen_offset']);?>"<?php endif;?>>
<?php $this->display(
    $this->Platform()->getAssetsDir('directories-pro') . '/templates/map_map',
    [
        'settings' => $settings['map'],
        'field' => $settings['map']['coordinates_field'],
        'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT)
    ] + $CONTEXT->getAttributes()
);?>
</div>
