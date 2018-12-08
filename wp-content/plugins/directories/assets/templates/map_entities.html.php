<?php $this->display(
    $this->Platform()->getAssetsDir('directories') . '/templates/map_map',
    [
        'settings' => $settings,
        'field' => $settings['coordinates_field']
    ] + $CONTEXT->getAttributes()
);?>