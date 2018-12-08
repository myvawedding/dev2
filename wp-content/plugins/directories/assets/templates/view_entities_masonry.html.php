<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$pre_rendered = $this->Entity_Display_preRender($entities, $settings['display']);
if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);
if (isset($settings['masonry_cols'])) {
    if ($settings['masonry_cols'] === 'responsive') {
        if (!empty($settings['masonry_cols_responsive'])) {
            $_masonry_cols = $settings['masonry_cols_responsive'];
        }
    } else {
        $_masonry_cols = $settings['masonry_cols'];
    }
}
if (!isset($_masonry_cols)
    && (!$_masonry_cols = $this->Entity_BundleTypeInfo($bundle, 'view_masonry_cols'))
) {
    $_masonry_cols = empty($bundle->info['is_taxonomy']) ? ['sm' => 1, 'md' => 2, 'lg' => 3, 'xl' => 4] : ['xs' => 2, 'lg' => 3, 'xl' => 4];
}
?>
<div class="dw <?php echo $this->H($view->getGridClass($_masonry_cols, true));?>">
<?php foreach ($pre_rendered['entities'] as $entity):?>
    <div class="dw-panel">
        <div class="dw-panel__content">
            <?php $this->Entity_Display($entity, $settings['display'], $vars, ['is_caching' => $CONTEXT->isCaching()]);?>
        </div>
    </div>
<?php endforeach;?>
</div>