<div class="drts-location-entities-map-container drts-location-entities-with-map drts-location-entities-with-map-<?php echo $settings['map']['position'];?> <?php echo DRTS_BS_PREFIX;?>row <?php echo DRTS_BS_PREFIX;?>no-gutters"<?php if (!empty($settings['map']['fullscreen_offset'])):?> data-fullscreen-offset="<?php echo $this->H($settings['map']['fullscreen_offset']);?>"<?php endif;?>>
    <div class="<?php echo DRTS_BS_PREFIX;?>col-sm-<?php echo 12 - $settings['map']['span'];?> drts-location-entities-container <?php echo DRTS_BS_PREFIX;?>mb-3">
        <div class="drts-view-entities drts-location-entities">
            <?php $this->display($settings['map']['template'], $CONTEXT->getAttributes());?>
        </div>
    </div>
    <div class="<?php echo DRTS_BS_PREFIX;?>col-sm-<?php echo intval($settings['map']['span']);?><?php if ($settings['map']['position'] !== 'top'):?> <?php echo DRTS_BS_PREFIX;?>d-none <?php echo DRTS_BS_PREFIX;?>d-sm-block<?php endif;?> <?php echo DRTS_BS_PREFIX;?>mb-3 drts-location-map-container-container" data-span="<?php echo intval($settings['map']['span']);?>" data-fullscreen-span="<?php echo intval($settings['map']['fullscreen_span']);?>" data-position="<?php echo $settings['map']['position'];?>"<?php if (!empty($settings['map']['sticky_offset'])):?> data-sticky-scroll-top="<?php echo intval($settings['map']['sticky_offset']);?>"<?php endif;?><?php if (!empty($settings['map']['sticky_offset_selector'])):?> data-sticky-scroll-top-selector="<?php echo $this->H($settings['map']['sticky_offset_selector']);?>"<?php endif;?> style="height:<?php echo intval($settings['map']['height']);?>px;">
        <?php $this->display(
            $this->Platform()->getAssetsDir('directories-pro') . '/templates/map_map',
            [
                'settings' => $settings['map'] + ['coordinates_field' => 'location_address'],
                'draw_options' => $this->Location_DrawMapOptions([], $CONTEXT)
            ] + $CONTEXT->getAttributes()
        );?>
    </div>
</div>
<div class="drts-location-sticky-scroll-stopper"></div>