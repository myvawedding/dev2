<?php
$markers = $this->Map_Marker_all(
    $bundle,
    $entities,
    $settings,
    $CONTEXT->getContainer()
);
?>
<script type="text/javascript">
<?php if (\SabaiApps\Directories\Request::isXhr()):?>
jQuery(function($) {
<?php else:?>
document.addEventListener('DOMContentLoaded', function() {
    var $ = jQuery;
<?php endif;?>
    var settings = <?php echo $this->JsonEncode($settings);?>;
    var map = DRTS.Map.api.getMap('<?php echo $CONTEXT->getContainer();?>', settings)
        .setMarkers(<?php echo $this->JsonEncode($markers);?>)
        .draw(<?php if (isset($draw_options)):?><?php echo $this->JsonEncode($draw_options);?><?php endif;?>);
});
</script>
<div class="drts-map-container">
    <div class="drts-map-map" style="height:<?php echo intval($settings['height']);?>px;"></div>
</div>