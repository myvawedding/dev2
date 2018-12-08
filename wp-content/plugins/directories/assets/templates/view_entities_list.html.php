<?php
$vars = $CONTEXT->getAttributes();
if (empty($entities)) {
    $this->display('view_entities_none', $vars);
    return;
}
$pre_rendered = $this->Entity_Display_preRender($entities, $settings['display']);
if (!empty($pre_rendered['html'])) echo implode(PHP_EOL, $pre_rendered['html']);
if (empty($settings['list_grid'])) {
    $layout = 'row';
} else {
    if (empty($settings['list_no_row'])) {
        if (!empty($settings['list_layout_switch_cookie'])
            && ($cookie = $this->Cookie($settings['list_layout_switch_cookie']))
        ) {
            $layout = $cookie === 'grid' ? 'grid' : 'row';
        } else {
            $layout = empty($settings['list_grid_default']) ? 'row' : 'grid';
        }
    } else {
        $layout = 'grid';
    }
    if (isset($settings['list_grid_cols']['num'])) {
        if ($settings['list_grid_cols']['num'] === 'responsive') {
            if (!empty($settings['list_grid_cols']['num_responsive'])) {
                $_list_grid_cols = $settings['list_grid_cols']['num_responsive'];
            }
        } else {
            $_list_grid_cols = $settings['list_grid_cols']['num'];
        }
    }
    if (!isset($_list_grid_cols)
        && (!$_list_grid_cols = $this->Entity_BundleTypeInfo($bundle, 'view_list_grid_cols'))
    ) {
        $_list_grid_cols = ['xs' => 2, 'lg' => 3, 'xl' => 4];
    }
}
?>
<div class="drts-view-entities-list-<?php echo $layout;?>">
    <div class="drts-row<?php if ($settings['list_grid_gutter_width']):?> drts-gutter-<?php echo $this->H($settings['list_grid_gutter_width']);?><?php endif;?>">
<?php   foreach ($pre_rendered['entities'] as $entity):?>
        <div<?php if (isset($_list_grid_cols)):?> class="<?php echo $this->H($view->getGridClass($_list_grid_cols));?>"<?php endif;?>>
            <?php $this->Entity_Display($entity, $settings['display'], $vars, ['is_caching' => $CONTEXT->isCaching()]);?>
        </div>
<?php endforeach;?>
    </div>
</div>